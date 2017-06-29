<?php

/**
 * 服务器端
 */

include_once '../../vendor/autoload.php';


function hello($name)
{
  return $name;
}

$server = new \CcRpc\tcp\Server("tcp://0.0.0.0:1314");
$server->addFunction('hello');
$server->Run();
