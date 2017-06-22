<?php

include_once '../vendor/autoload.php';

function HelloWorld()
{
  return __FUNCTION__;
}

$server = new \CcRpc\Server();
$server->Run();