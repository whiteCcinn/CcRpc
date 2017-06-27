<?php

/**
 * 客户端调用例子
 */

include_once '../vendor/autoload.php';

/*------------------------------------------ example 1. int 传递 ----------------------------------------------------*/

//$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
//$result = $server->HelloWorld(1);
//var_dump($result);

/*------------------------------------------ example 2. string 传递 -------------------------------------------------*/

//$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
//$result = $server->HelloWorld('My Client');
//var_dump($result);

/*------------------------------------------ example 3. 数组<List> 传递 ----------------------------------------------*/

//$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
//$result = $server->HelloWorld([1,'s',2,'bb']);
//var_dump($result);

/*------------------------------------------ example 4. 数组<Map> 传递 -----------------------------------------------*/

//$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
//$result = $server->HelloWorld(['a' => 1, 'b' => 2]);
//var_dump($result);

/*------------------------------------------ example 5. 多参数 传递 --------------------------------------------------*/

$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
$result = $server->HelloWorld(1,2);
var_dump($result);

/*------------------------------------------ example 6. 不存在函数调用 ------------------------------------------------*/

//$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
//$result = $server->HelloWorld2([4,5]);
//var_dump($result);
