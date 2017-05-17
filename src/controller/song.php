<?php

class Song extends Controller
{

  public function __construct()
  {
    parent::__construct();
  }

  protected function _Get($request)
  {
    return static::$model->findAll();
  }


}
