<?php
session_start();
require_once "core/api.php";
require_once "core/controller.php";
require_once "includes/functions.php";



define('ROOT', $_SERVER['DOCUMENT_ROOT']);
define("STATIC_PATH", '/Users/Tfruehe-mac/Sites/RESTfulAPI/public');

$GLOBALS['config'] = array(
  'mysql' => array(
    'host' => '127.0.0.1',
    'username' => 'root',
    'password' => 'yUTztz5K',
    'db' => 'mil_music'
  ),
  'remember' => array(
    'cookie_name' => 'hash',
    'cookie_expiry' => 604800
  ),
  'session' => array(
    'session_name' => 'user',
    'token_name' => 'csrf_token'
  )
);

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

if(Cookie::exists(Config::get('remember/cookie_name')) &&
  !Session::exists(Config::get('session/session_name')))
  {
    $hash = Cookie::get(Config::get('remember/cookie_name'));
    $hash_check = DB::getInstance()->get('user_session', array('token', '=', $hash));

    if($hash_check->count())
    {
      echo "here";
      $user = new User($hash_check->first()->user_id);
      $user->login();
    }
  }
