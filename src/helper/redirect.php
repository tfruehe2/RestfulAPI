<?php

class Redirect
{
  public static function to($location=null)
  {
    if($location)
    {
      if(is_numeric($location))
      {
        switch($location)
        {
          case 404:
            header('HTTP/1.0 404 Page Not Found');
            include 'includes/errors/404.php';
            break;

          case 403:
            header('HTTP/1.0 403 Page Forbidden');
            include 'includes/errors/403.php';
            break;
        }
      }
      header('Location: ' . $location);
      exit();
    }
  }
}
