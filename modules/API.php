<?
namespace Modules;

class API {
  public function __construct(){
    parse_str($_SERVER['QUERY_STRING'], $request);
    
    $className = (new \ReflectionClass($this))->getShortName() . "\\" . ucfirst($request['object']);
    $methodName = $request['action'];

    try {
      if(!class_exists($className))
        throw new \Exception("Class \"{$className}\" is not exist!");
      
      if(!method_exists($className, $methodName))
        throw new \Exception("Method \"{$methodName}\" of class \"{$className}\" is not exist!");
      
      $data = json_decode(file_get_contents('php://input'), true);
        
      $result = $className::$methodName($data);
      
      echo json_encode($result);
    } catch(\Exception $e){
      echo $e->getMessage();
    }
  }
}
?>