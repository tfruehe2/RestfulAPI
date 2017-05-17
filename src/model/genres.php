<?php

class Genres extends ModelBase
{
  protected static $table_name;
  public static $fields=array();
  public static $required_fields=array();

  public function __construct()
  {
    parent::__construct();
  }



  public function fetchByName($genre_name)
  {
    $where = array('name', '=', $genre_name);
    return $this->get($where);
  }


}
