<?php
class DB {
  private static $_instance = null;
  private $_pdo,
          $_query,
          $_error = false,
          $_results,
          $_count = 0;

  private function __construct()
  {
    try
    {
      $this->_pdo = new PDO('mysql:dbname=' . Config::get('mysql/db') .
                            ';host=' . Config::get('mysql/host'),
                            Config::get('mysql/username'),
                            Config::get('mysql/password'));
      $this->_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }
    catch (PDOException $e)
    {
      die($e->getMessage());
    }
  }

  public static function getInstance()
  {
    if(!isset(self::$instance))
    {
      self::$_instance = new DB();
    }
    return self::$_instance;
  }

  public function fetchTableColumns($table_name)
  {
    $sql = "DESCRIBE {$table_name}";
    $this->_query = $this->_pdo->prepare($sql);
    $this->_query->execute();
    return $this->_query->fetchAll(PDO::FETCH_COLUMN);
  }

  public function fetchRequiredTableColumns($table_name)
  {
    $sql = "DESCRIBE {$table_name}";
    $this->_query = $this->_pdo->prepare($sql);
    $this->_query->execute();
    $fields = array();
    foreach ($this->_query->fetchAll(PDO::FETCH_ASSOC) as $key => $val)
    {
      if ($val['Null'] === "NO" && !isset($val['Default'])
                                && $val['Field']!== 'id')
      {
        $fields[] = $val["Field"];
      }
    }
    return $fields;
  }


  public function query($sql, $params = array())
  {
    $this->_error = true;
    if($this->_query = $this->_pdo->prepare($sql))
    {
      if(count($params))
      {
        foreach(array_values($params) as $i => $param)
        {
          $this->_query->bindValue($i+1, $param);
        }
      }
      if($this->_query->execute())
      {
        $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
        $this->_count = $this->_query->rowCount();
        $this->_error = false;
      }
      else

      {
        throw new Exception("Failed DB Query: " . $this->db_errors());
      }
    }
    return $this;
  }

  private function action($action, $table, $where=array())
  {
    $values = array();
    if(!$where)
    {
      $sql = "{$action} FROM {$table}";

    }
    elseif (count($where) === 3)
    {
      $operators = array('=', '>', '<', '>=', '<=', "IN");
      $field    = $where[0];
      $operator = $where[1];
      $values[] = $where[2];

      if(in_array($operator, $operators))
      {
        $sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";
      }

    }


    if(!$this->query($sql, $values)->error())
    {
      return $this;
    }
    return false;
  }


  public function get($table, $where)
  {
    return $this->action('SELECT *', $table, $where);
  }


  public function delete($table, $where)
  {
    return $this->action('DELETE', $table, $where);
  }

  public function insert($table, $fields = array())
  {
    if (count($fields))
    {
      $keys = array_keys($fields);
      $values = "";
      $x = 1;

      foreach($fields as $field)
      {
        $values .= "?";
        if($x < count($fields))
        {
          $values .= ', ';
        }
        $x++;
      }

      $sql = "INSERT INTO `{$table}` (" . implode(', ', $keys);
      $sql .= ") VALUES ({$values})";

      if(!$this->query($sql, $fields)->error())
      {
        return $this->_pdo->lastInsertId();
      }
    }

    return false;
  }

  public function update($table, $id, $fields)
  {
    $set = '';
    $x = 1;
    foreach($fields as $name => $value)
    {
      $set .= "{$name} = ?";
      if($x < count($fields))
      {
        $set .= ', ';
      }
      $x++;
    }

    $sql = "UPDATE {$table} SET {$set} WHERE id={$id}";

    if(!$this->query($sql, $fields)->error())
    {
      return true;
    }

    return false;
  }

  public function results()
  {
    return $this->_results;
  }

  public function first()
  {
    return $this->_results()[0];
  }

  public function error()
  {
    return $this->_error;
  }

  public function count()
  {
    return $this->_count;
  }

  public function db_errors()
  {
    if($this->_query->errorInfo())
    {
      return $this->_query->errorInfo();
    }
    return false;
  }


}
