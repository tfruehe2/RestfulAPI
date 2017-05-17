<?php

class Request
{
  public $method,
         $object_id=null,
         $sub_object='',
         $args=array(),
         $file=null;

  public function __construct()
  {
    $this->args = explode('/', rtrim($_REQUEST['request'], '/'));

    if(array_key_exists(0, $this->args) && is_numeric($this->args[0]))
    {
      $this->object_id = array_shift($this->args);
    }

    if (array_key_exists(0, $this->args) && !is_numeric($this->args[0]))
    {
      if(file_exists("./model/" . rtrim($this->args[0], "s") . "s.php"))
      {
        $class_name = ucfirst(rtrim(array_shift($this->args),"s")) . "s";
        $this->sub_object= new $class_name();
      }
    }

    $this->method = $_SERVER['REQUEST_METHOD'];

    if($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD',
                                                                $_SERVER))
    {
      if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE')
      {
        $this->method = 'DELETE';
      }
      elseif ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT')
      {
        $this->method = 'PUT';
      }
      else
      {
        throw new Exception("Unexpected Header");
      }
    }

    switch($this->method)
    {
      case 'DELETE':
            break;
      case 'POST':
            $this->request_array = $this->_cleanInputs($_POST);
            if (!empty($_FILES['file_upload'])) {
              $this->request_array['file']=$this
                                        ->sanitizeFile($_FILES['file_upload']);
            }
            break;
      case 'GET':
            $this->request_array = $this->_cleanInputs($_GET);
            break;
      case 'PUT':
            $this->request_array = $this->_cleanInputs($_PUT);
            $this->file = file_get_contents("php://input");
            break;
      default:
            $this->_response('Invalid Method', 405);
            break;

    }

  }

  private function sanitizeFile($file) {
    //TO BE IMPLEMENTED LATER
    return $file;
  }

  private function _cleanInputs($data)
  {
    $clean_input = array();
    if(is_array($data))
    {
      foreach ($data as $k => $v)
      {
        if ($k !== 'request')
        {
          $clean_input[$k] = $this->_cleanInputs($v);
        }
      }
    }
    else
    {
      $clean_input = trim(strip_tags($data));
    }
    return $clean_input;
  }


}
