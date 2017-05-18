<?php

abstract class Controller
{
  protected static $model;

  public function __construct()
  {
    if (!isset(static::$model))
    {
      $model_name = ucfirst(get_called_class()) . "s";
      static::$model = new $model_name();
    }
  }

  public function handleRequest($request) {
    if (method_exists($this,$request->method))
    {
      return $this->{$request->method}($request);
    }
    return false;
  }

  public function GET($request)
  {
    if ($request->object_id && $request->sub_object)
    {
      return $this->GetWithIdAndSubObject($request);
    }
    elseif ($request->object_id)
    {
      return $this->GetWithId($request);
    }
    else
    {
      return $this->_Get($request);
    }
  }

  public function POST($request)
  {
    //print_r($request->request_array);
    //print_r(static::$model::$required_fields);
    return static::$model->save($request->request_array, $request->object_id);

  }

  protected function PUT($request)
  {
    if($id=$request->object_id)
    {
      try
      {
        static::$model->save($id, $request->request_array);
      }
      catch (Exception $e)
      {
        echo json_encode(Array('error' => $e->getMessage()));
      }
    }
  }

  protected function DELETE($request)
  {
    if($id=$request->object_id)
    {
      if (static::$model->delete($id))
      {
        echo "Object with ID ".$id." deleted from ".static::$model->tableName();
      }
      else
      {
        throw new Exception("Failed To Delete Object With ID " . $id);
      }
    }
    else
    {
      throw new Exception("Failed to provide a Proper ID");
    }
  }

  protected function _Get($request)
  {
    print_r($request->request_array);
    print_r(static::$model::$fields);
    print_r(static::$model::$required_fields);
    $objs = json_decode(static::$model->findAll());
    foreach ( $objs as $obj)
    {
      foreach ($obj as $field=>$value)
      {
        if ($value)
        {
          echo "{$field}: " . $value ."\n";
        }
      }
      echo "\n\n";
    }
    return "This is a get response from " . static::$model->tableName();
  }

  public function GetWithId($request)
  {
    $objs = static::$model->findById($request->object_id);
    if($objs)
    {
      foreach ( $objs as $obj)
      {
      //   foreach ($obj as $field=>$value)
      //   {
      //     if ($value)
      //     {
      //       echo "{$field}: " . $value ."\n";
      //     }
      //   }
      //   echo "\n\n";
      // }
      print_r($obj);
      echo "<br />";
      }
    }
    else
    {
      echo "Unable to find object with ID:" .$request->object_id . " in table "
                                            .static::$model->tableName() . "\n";
    }
    return "This is a get response from " . static::$model->tableName();
  }

  public function GetWithIdAndSubObject($request)
  {
    echo static::$model->tableName();
    echo "\n" . $request->sub_object->tableName();
  }

  public function model($model)
  {
    require_once '../model' . $model . ".php";
  }

  protected function view($view, $data=[])
  {
    require_once '../view/' . $view . '.php';
  }

}
