<?php

class API
{
  public $method='',
         $endpoint='',
         $controller,
         $sub_object='',
         $object_id='',
         $args=array(),
         $file=null,
         $request;

  public function __construct($endpoint)
  {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: *");
    header("Content-Type: application/json");

    $this->request=new Request();

    $this->endpoint = $endpoint;
    if(file_exists("./controller/" . rtrim($this->endpoint, "s") . ".php"))
    {
      $class_name = ucfirst(rtrim($this->endpoint, "s"));
      $this->controller = new $class_name();
    }
  }

  public function processAPI()
  {
    // if(method_exists($this, $this->endpoint)) {
    //   return $this->_response($this->{$this->endpoint}($this->args));
    // }
    // return $this->_response("No Endpoint: $this->endpoint", 404);
    if ($this->controller)
    {
      return $this->controller->handleRequest($this->request);
    }
    return $this->_response("No Endpoint: $this->endpoint", 404);

  }

  private function _response($data, $status=200)
  {
    header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
    return json_encode($data);
  }



  private function _requestStatus($code)
  {
    $status = array(
      200 => 'Ok',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      500 => 'Internal Server Error',
    );
    return ($status[$code]) ? $status[$code] : $status[500];
  }

}
