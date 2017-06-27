<?php

/**
 * 服务器端
 */

include_once '../vendor/autoload.php';

function HelloWorld($data = [],$a)
{
  return $a;
}

function Hi()
{
  return __FUNCTION__;
}

$server = new \CcRpc\http\Server();
$server->addFunction('HelloWorld');
$server->addFunction('Hi');
$server->Run();