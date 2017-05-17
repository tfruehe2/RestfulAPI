<?php
require_once "core/api.php";
require_once "core/controller.php";
require_once "includes/functions.php";



define('ROOT', $_SERVER['DOCUMENT_ROOT']);
define("STATIC_PATH", '/Users/Tfruehe-mac/Sites/RESTfulAPI/public');


spl_autoload_register(function($class)
{
  if(file_exists(__DIR__  . '/' . 'model/' . strtolower($class) . '.php'))
  {
    require_once 'model/' .strtolower($class) . '.php';
  }
  elseif(file_exists(__DIR__  . '/' . 'helper/' . strtolower($class) . '.php'))
  {
    require_once 'helper/'.strtolower($class) . '.php';
  }
  elseif (file_exists(__DIR__  .'/'. 'controller/'. strtolower($class).'.php'))
  {
    require_once 'controller/'.strtolower($class).'.php';
  }
});
