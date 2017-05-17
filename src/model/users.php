<?php

class Users extends ModelBase
{
  protected static $table_name;
  public static $fields=array();
  public static $required_fields=array();

  public function __construct()
  {
    parent::__construct();
  }

  public function findByUsername($un)
  {
    return $this->get('username', '=', $un);
  }

  public function create($params=array())
  {
    if(isset($params['password']))
    {
      $params['hashed_password']=password_hash($params['password']);
      unset($params['password']);
    }
    parent::create($params=array());

  }

  public function authenticate($username, $password)
  {
    if ($user=json_decode($this->findByUsername($username)))
    {
      if (password_verify($password, $user->hashed_password))
      {
        return $user;
      }
    }

    return false;
  }

  public function getPermission($id)
  {
    $sql = "SELECT name, permission from permission_groups WHERE
              permission_groups.id = (SELECT group_id FROM users WHERE
              users.id=? LIMIT 1)";
    return $this->findBySQL($sql, array($id));
  }

}
