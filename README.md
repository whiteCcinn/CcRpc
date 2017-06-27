# CcRpc
> 专注于PHP之间的RPC框架

# 配置要求

- PHP >= 7.0.0

# 调试功能

```
// 可根据此文件调整模式，支持debug、product模式，debug模式下降会输出部分重要log
CcRpc\src\ini\ConfigIni.php

// 如果你想调整的话，可以通过以下文件自定义配置项
CcRpc\src\ini\CcRpc.php.ini 
```


# Usage

## example 1. int 传递 

```
$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
$result = $server->HelloWorld(1);
var_dump($result);
```

## example 2. string 传递

```
$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
$result = $server->HelloWorld('My Client');
var_dump($result);
```

## example 3. 数组<List> 传递 

```
$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
$result = $server->HelloWorld([1,'s',2,'bb']);
var_dump($result);
```

## example 4. 数组<Map> 传递

```
$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
$result = $server->HelloWorld(['a' => 1, 'b' => 2]);
var_dump($result);
```

## example 5. 多参数 传递 

```
$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
$result = $server->HelloWorld(1,2);
var_dump($result);
```

## example 6. 不存在函数调用

```
$server = new \CcRpc\http\Client('http://www.yourhost.com/example/Server.php');
$result = $server->HelloWorld2([4,5]);
var_dump($result);
```
