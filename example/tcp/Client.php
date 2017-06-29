<?php

/**
 * 客户端调用例子
 */

include_once '../../vendor/autoload.php';
$client = new \CcRpc\tcp\Client('tcp://127.0.0.1:1314');
$response = $client->hello([1,2]);
var_dump($response);
