<?php

/**
 * 客户端调用例子
 */

include_once '../vendor/autoload.php';

$server = new \CcRpc\http\Client('http://www.l2.localhost.com/hprose/rpc/src/Server.php');

var_dump($server);