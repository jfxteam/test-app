<?
namespace Modules;

class DB extends \MySQLi {
  public $table_prefix = '';

  public function __construct($db_host = DB_HOST, $db_user = DB_USER, $db_pass = DB_PASS, $db_name = DB_NAME){
    parent::__construct($db_host, $db_user, $db_pass, $db_name);
    $this->set_charset("utf8");
    if($this->connect_errno)
      App::return(['status' => 'error', 'response' => $this->connect_error]);
    $this->connected = true;
  }

  public function  __call($method, $parameters){
    if(!$this->connected)
      $this->__construct();
      if(method_exists($this, $method))
        return call_user_func_array(array($this, $method), $parameters);
    }

  public function tablePrefix($prefix){
    $this->table_prefix = $prefix;
  }
  
  public function customRequest($query){
    if(!$result = $this->query($query))
      App::return(['status' => 'error', 'response' => $this->error.' in query '.$query]);
    return $result;
  }

  private function request($type, $params, $destroy = false){
    $result = [];

    switch($type){
      case 'set':
        if(!isset($params['table']))
          App::return(['status' => 'error', 'response' => "Table is necessary for {$type} query type!"]);
        if(!isset($params['keys']))
          App::return(['status' => 'error', 'response' => "Keys is necessary for {$type} query type!"]);
        if(!isset($params['values']))
          App::return(['status' => 'error', 'response' => "Values is necessary for {$type} query type!"]);

        $query_parts[] = "INSERT INTO";
        $query_parts[] = '`'.$this->table_prefix.$params['table'].'`';
        $query_parts[] = "(`".implode("`,`", $params['keys'])."`)";
        $query_parts[] = "VALUES";
        $query_parts[] = implode(",", (array_map(function($values){
          return "('".implode("','", $values)."')";
        }, $params['values'])));
        $query_parts[] = "ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),";
        $query_parts[] = implode(",", array_map(function($key){
          return "`{$key}` = VALUES(`{$key}`)";
        }, $params['keys']));

        $this->query_from_parts($query_parts);
        $result = $this->insert_id;
        break;
        
      case 'delete':
        if(!isset($params['table']))
          App::return(['status' => 'error', 'response' => "Table is necessary for {$type} query!"]);
        if(!isset($params['where']))
          App::return(['status' => 'error', 'response' => "WHERE is necessary for {$type} query!"]);
        $query_parts[] = "DELETE FROM";
        $query_parts[] = '`'.$this->table_prefix.$params['table'].'`';
        $query_parts[] = "WHERE";
        $query_parts[] = $this->apply_filters($params['where']);
        
        $result = $this->query_from_parts($query_parts);
        
        break;


      case 'update':
        if(!isset($params['table']))
          App::return(['status' => 'error', 'response' => "Table is necessary for {$type} query!"]);
        if(!isset($params['set']))
          App::return(['status' => 'error', 'response' => "SET is necessary for {$type} query!"]);

        $query_parts[] = "UPDATE";
        $query_parts[] = '`'.$this->table_prefix.$params['table'].'`';
        $query_parts[] = "SET";
        $query_parts[] = implode(",", array_map(function($key, $value){
          if(is_array($value)){
            if(!isset($value['cases']))$value['cases'] = [];
            $cases[] = 'CASE';
            $cases[] = implode(' ', array_map(function($case){
              $when = implode(" AND ", array_map(function($key, $value){
                return "`{$key}` = '{$value}'";
              }, array_keys($case['when']), $case['when']));
              $then = $case['then'];
              return "WHEN {$when} THEN {$then}";
            }, $value['cases']));
            $cases[] = isset($value['default']) ? "ELSE {$value['default']}" : "";
            $cases[] = 'END';
            $value = implode(' ', $cases);
          } else {
            $value = "'{$value}'";
          }
          return "`{$key}` = {$value}";
        }, array_keys($params['set']), $params['set']));
        if(isset($params['where'])){
          $query_parts[] = "WHERE";
          $query_parts[] = implode(" AND ", array_map(function($value, $key){
            return "`{$key}` = '{$value}'";
          }, $params['where'], array_keys($params['where'])));
        }

        $result = $this->query_from_parts($query_parts);
        break;


      case 'select':
        if(!isset($params['table']))
          App::return(['status' => 'error', 'response' => "Table is necessary for {$type} query type!"]);
        $base_table = "`{$this->table_prefix}{$params['table']}`";
        $join_tables = [];
        $join_values = [];
        if(isset($params['join']) && is_array($params['join'])){
          foreach($params['join'] as $join){
            if(!isset($join['table']))
              App::return(['status' => 'error', 'response' => "Join table is necessary if join is defined!"]);
            if(!isset($join['on']))
              App::return(['status' => 'error', 'response' => "Join on is necessary!"]);
            $join_table = "`{$this->table_prefix}{$join['table']}`";
            $join_table_base_table = "`{$this->table_prefix}{$join['base_table']}`";
            $join_tables[$join_table] = ['base_table' => $join_table_base_table, 'on' => $join['on']];
            $join_values = array_merge(
              $join_values, 
              isset($join['values']) ? array_map(function($key, $value) use ($join_table) {
                if(is_numeric($key)){
                  return "{$join_table}.`{$value}`";
                } else {
                  return "{$join_table}.`{$key}` as `{$value}`";
                }
              }, array_keys($join['values']), $join['values']) : ["{$join_table}.*"]
            );
          }
        }
        $values = !empty($params['values']) ? array_map(function($row) use ($base_table) {return "{$base_table}.`{$row}`";}, $params['values']) : [($join_tables ? "{$base_table}." : '').'*'];
        
        $query_parts[] = "SELECT";
        $query_parts[] = implode(",", array_merge($values, $join_values));
        $query_parts[] = "FROM";
        $query_parts[] = $base_table;
        if($join_tables)
          $query_parts[] = implode(" ", array_map(function($join_table, $join_data) use ($base_table) {
            $base_table_field = $join_data['on']['base_table'];
            $join_table_field = $join_data['on']['join_table'];
            return "LEFT JOIN {$join_table} ON {$join_table}.`{$join_table_field}` = {$join_data['base_table']}.`{$base_table_field}`";
          }, array_keys($join_tables), $join_tables));
        if(isset($params['where'])){
          $query_parts[] = "WHERE";
          if($join_tables){
            $query_parts[] = "(" . implode(") AND (", array_map(function($join_table, $join_data){
              return $this->apply_filters($join_data, $join_table);
            }, array_keys($params['where']), $params['where'])) . ")";
          } else {
            $query_parts[] = $this->apply_filters($params['where']);
          }
        }
        if(!empty($params['limit'])){
          $query_parts[] = "LIMIT";
          $query_parts[] = $params['limit'];
        }
        $query_result = $this->query_from_parts($query_parts);
        while($row = $query_result->fetch_assoc())
          $result[] = $row;
        if(!empty($params['limit']) && $params['limit'] == 1)
          $result = $result[0];
        break;
    }

    if($destroy){
      $this->close();
      $this->connected = false;
    }

    return $result;
  }

  public function __destruct(){
    $this->table_prefix = '';
  }
  
  private function apply_filters($filters, $table = ''){
    return "(" . implode(") OR (", array_map(function($value, $key) use ($table){
      if(is_array($value)){
        return "(" . implode(") AND (", array_map(function($key, $value) use ($table){
          $field_name = $this->get_join_table_field_name($table, $key);
          return is_array($value) ? $field_name . " IN ('".implode("','", $value)."')" : $field_name . " = '{$value}'";
        }, array_keys($value), $value)) . ")";
      } 
      return $this->get_join_table_field_name($table, $key) . " = '{$value}'";
    }, $filters, array_keys($filters))) . ")";
  }
  
  private function get_join_table_field_name($table, $field_name){
    return implode('.', array_filter([$table, "`{$field_name}`"]));
  }
  
  private function query_from_parts($query_parts){
    $query = implode(' ', $query_parts);
    if(!$result = $this->query($query)){
      die(json_encode(['status' => false, 'message' => $this->error.' in query '.$query]));
    }
    return $result;
  }
}
?>