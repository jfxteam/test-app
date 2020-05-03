<?
namespace API;

use \Modules\DB;

class Todo {
  public static function get($data){
    $id = $data['id'];
    
    $DB = new DB();
    $todo = $DB->request('select', [
      'table' => 'todos',
      'where' => [
        'id' => (int) $id
      ],
      'limit' => 1
    ]);

    return $todo;
  }
  
  public static function list(){
    $DB = new DB();
    $todos = $DB->request('select', [
      'table' => 'todos'
    ]);
    
    return $todos;
  }
  
  public static function set($data){
    $DB = new DB();
    $todo = $DB->request('set', [
      'table' => 'todos',
      'keys' => array_keys($data),
      'values' => [array_values($data)]
    ]);
    
    return ['id' => $todo];
  }
  
  public static function delete($data){
    $id = $data['id'];
    
    $DB = new DB();
    $todo = $DB->request('delete', [
      'table' => 'todos',
      'where' => [
        'id' => $id
      ]
    ]);
    
    return ['id' => $todo];
  }
}
?>