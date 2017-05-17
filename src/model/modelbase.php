<?php

abstract class ModelBase
{
  private $_db,
          $_data;

  protected static $table_name;
  public static $fields=array();
  public static $required_fields=array();

  public function __construct()
  {
    $this->_db = DB::getInstance();
    static::$table_name = strtolower(get_called_class());
    static::$fields=$this->_db->fetchTableColumns(static::$table_name);
    static::$required_fields =
              $this->_db->fetchRequiredTableColumns(static::$table_name);
  }

  public function findAll()
  {
    return $this->get(null, array("title", "ASC"));
  }

  public function findById($id)
  {
    return $this->get(array('id', '=', $id));
  }

  public function findByName($name)
  {
    if (in_array('name', static::$fields))
    {
      $where = array('name', '=', $name);
      return $this->get($where);
    }
    elseif (in_array('title', static::$fields))
    {
      $where = array('title', '=', $name);
      return $this->get($where);
    }
    return false;
  }

  public function findByUserID($user_id)
  {
    if (in_array("user_id", static::$fields))
    {
      $where = array("user_id", "=", $user_id);
      return $this->get($where);
    } elseif (in_array("author_id", static::$fields))
    {
      $where = array("author_id", "=", $user_id);
      return $this->get($where);
    }
    return false;
  }

  //Used for testing purposes only
  public function select($fields, $where, $table=null, $order, $limit)
  {
    if (!$table)
    {
      $table = static::tableName();
    }
    $stmt = "SELECT ";
    if (!empty($fields))
    {
      foreach($fields as $field)
      {
        if (in_array($field, static::$fields))
        {
          $stmt .= $field;
          if($x < count($fields))
          {
            $stmt .= ', ';
          }
          else
          {
            $stmt .= " ";
          }
        }
        $x++;
      }
    }
    else
    {
      $stmt .= "* ";
    }

    $stmt .= "FROM " . $table . " ";
    if (count($where) === 3)
    {
      $operators = array('=', '>', '<', '>=', '<=', "IN");
      $field    = $where[0];
      $operator = $where[1];
      $values[] = $where[2];
      if(in_array($operator, $operators))
      {
        $stmt .= "WHERE {$field} {$operator} ? ";
      }
    }
    if (count($order)===2)
    {
      $by = array('ASC', "DESC");
      if (in_array($order[1], $by))
      {
        $values[] = $order[0];
        $stmt .= "ORDER BY ? {$order[1]} ";
      }
    }

    if (count($limit)===2)
    {
      if (is_numeric($limit[0]) && is_numeric($limit[1]))
      {
        $values[] = $limit[0];
        $values[] = $limit[1];
        $stmt .= "LIMIT ? OFFSET ?";
      }
    } elseif (count($limit)===1)
    {
      if (is_numeric($limit[0]))
      {
        $values[] = $limit[0];
        $stmt .= "LIMIT ?";
      }
    }
    $stmt .= ";";
    return $stmt;
  }

  public function get($where=false)
  {
    return json_encode($this->_db->get(static::tableName(),$where)->results());
  }

  protected function findBySQL($sql, $params=array())
  {
    return json_encode($this->_db->query($sql, $params)->results());
  }

  public function save($params=array(), $id=null)
  {
    try
    {
      return isset($id) ? static::update($id, $params) : static::create($params);
    }
    catch (Exception $e)
    {
      echo json_encode(Array('error' => $e->getMessage()));
    }

  }

  public function create($params=array())
  {
    $this->_data=static::filterAttributes($params);
    if(static::hasRequiredAttributes($this->_data))
    {
      if ($obj_id=$this->_db->insert(static::tableName(), $this->_data))
      {
        return $obj_id;
      }
    }
    else
    {
      print_r(array_diff(static::$required_fields, array_keys($this->_data)));
      throw new Exception("Failed To Create: " . get_called_class());
    }
  }

  public function update($id, $params=array())
  {
    return $this->_db->update(static::tableName(), $id, $params);
  }

  public function delete($id)
  {
    return $this->_db->delete(static::tableName(), array('id', '=', $id))->error()
                              ? false : true;
  }

  public static function tableName()
  {
    return static::$table_name;
  }

  protected function data()
  {
    return $this->_data;
  }



  public static function filterAttributes($params=array())
  {
    $attributes=array();
    echo "from filter attr " . get_called_class() . "\n";
    print_r($params);
    echo "\n";
    print_r(get_called_class()::$fields);
    foreach ($params as $key => $val)
    {
      if (in_array($key, static::$fields))
      {
        $attributes[$key] = $val;
      }
    }
    return $attributes;
  }

  public static function hasRequiredAttributes($params=array())
  {
    echo "from required attr " . get_called_class() . "\n";
    print_r($params);
    return empty(array_diff(static::$required_fields, array_keys($params))) ? true : false;
  }



}
