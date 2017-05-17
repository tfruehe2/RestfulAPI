<?php

class Users extends ModelBase
{
  private $_session_name,
          $_cookie_name,
          $_is_logged_in = false;
  protected static $table_name;


  public function __construct($user=null)
  {
    parent::__construct();
    $this->_session_name = Config::get('session/session_name');
    $this->_cookie_name = Config::get('remember/cookie_name');
    if (!$user)
    {
      if(Session::exists($this->_session_name))
      {
        $user = Session::get($this->_session_name);

        if($this->find($user))
        {
          $this->_is_logged_in = true;
        }
        else
        {
          //process logout
        }
      }
    }
    else
    {
      $this->find($user);
    }
  }

  public function isLoggedIn()
  {
    return $this->_is_logged_in;
  }

  public function create($fields = array())
  {
    if(!$this->_db->insert($this->tableName(), $fields))
    {
      throw new Exception("There was a problem creating an account.");
    }
  }

  public function update($fields=array(), $id=null)
  {
    if(!$id && $this->isLoggedIn())
    {
      $id = $this->data()->id;
    }
    if(!$this->_db->update($this->tableName(), $id, $fields))
    {
      throw new Exception("There was a problem updating.");
    }
  }

  public function find($user=null)
  {
    if($user)
    {
      $field = (is_numeric($user)) ? 'id' : 'username';
      $data = $this->_db->get($this->tableName(), array($field, '=', $user));

      if($data->count())
      {
        $this->_data = $data->first();
        return true;
      }
    }
  }

  public function hasPermission($key)
  {
    $group = $this->_db->get('groups', array('id', '=', $this->data()->id));

    if($group->count())
    {
      $permissions = json_decode($group->first()->permissions, true);

      if($permissions[$key])
      {
        return true;
      }
    }

    return false;
  }

  public function exists()
  {
    return (!empty($this->_data)) ? true : false;
  }

  public function logout()
  {
    $this->_db->delete('sessions', array('user_id', '=', $this->data()->id));
    Session::delete($this->_session_name);
    Cookie::delete($this->_cookie_name);
  }

  public function login($username=null, $password=null, $remember=false)
  {
    if(!$username && !$password && $this->exist())
    {
      Session::put($this->_session_name, $this->data()->id);
      return true;
    }
    else
    {
      $user = $this->find($username);

      if($user)
      {
        if($this->data()->password === Hash::make($password, $this->data()->salt))
        {
          Session::put($this->_session_name, $this->data()->id);

          if($remember)
          {
            $hash_check = $this->_db->get('sessions', array('user_id', '=',
                                                      $this->data()->id));

            if(!$hash_check->count())
            {
              $hash = Hash::unique();
              $this->_db->insert('sessions', array(
                'user_id' => $this->data()->id,
                'token' => $hash
              ));

            }
            else
            {
              $hash = $hash_check->first()->hash;
            }

            Cookie::put($this->_cookie_name, $hash, Config::get('remember/cookie_expiry'));
          }

          return true;
        }
      }
      return false;
    }
  }

}
