<?php

class Feeds extends ModelBase
{
  private $_data;
  protected static $table_name;
  public static $fields=array();
  public static $required_fields=array();

  public function __construct()
  {
    parent::__construct();
  }

  public function create($params=array())
  {
    $params['slug'] = slugify($params['title']);
    return parent::create($params);
  }
