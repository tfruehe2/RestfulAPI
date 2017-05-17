<?php
require_once "init.php";

print_r($_REQUEST);

try
{
  $API = new API($_REQUEST['endpoint']);
  echo $API->processAPI();
}
catch (Exception $e)
{
  echo json_encode(Array('error' => $e->getMessage()));
}
