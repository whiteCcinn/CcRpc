<?php

/**********************************************************\
 *                                                        *
 * CcRpc/tcp/Server.php                                   *
 *                                                        *
 * CcRpc Server class for php 7.0+                        *
 *                                                        *
 * LastModified: June 23, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
 * \**********************************************************/

namespace CcRpc\tcp;

use CcRpc\exception\TcpServerException;

class Server extends Service
{

  // 保持活跃状态，自动心跳
  public $keepAlive = true;

  // 不延迟传输，及时传输
  public $noDelay = true;

  public $uriList = [];

  public $_calls = [];

  public $_names = [];

  public function __construct($url = null)
  {
    if (is_array($url))
    {
      $this->uriList = $url;
    } else
    {
      array_push($this->uriList, $url);
    }
  }

  public function addFunction(string $funcName)
  {
    if (!is_callable($funcName))
    {
      throw new TcpServerException('Server does\'t not exits');
    }
    if (empty($alias))
    {
      $alias = $funcName;
    }

    $name = strtolower($alias);

    if (!array_key_exists($name, $this->_calls))
    {
      $this->_names[] = $alias;
    }

    $call         = new \stdClass();
    $call->method = $funcName;
    $call->mode   = 0;

    $this->_calls[ $name ] = $call;

    return $this;
  }

  public function setNoDelay($value)
  {
    $this->noDelay = $value;
  }

  public function isNoDelay()
  {
    return $this->noDelay;
  }

  public function setKeepAlive($value)
  {
    $this->keepAlive = $value;
  }

  public function isKeepAlive()
  {
    return $this->keepAlive;
  }

  public function addListener($uri)
  {
    $this->uriList[] = $uri;
  }

  public function createServerStream($url)
  {
    $schema = parse_url($url, PHP_URL_SCHEME);

    if ($schema == 'unix')
    {
      $uri = 'unix://' . parse_url($url, PHP_URL_PATH);
    }

    $errno  = 0;
    $errmsg = '';

    // 创建上下文
    $context = @stream_context_create();

    // 用$url和上下文创建服务器字节流
    $serverStream = @stream_socket_server($url, $errno, $errmsg, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

    if ($serverStream === false)
    {
      throw new TcpServerException($errmsg, $errno);
    }

    if (in_array($schema, ['tcp', 'unix']))
    {
      // 用服务器字节流创建socket
      $socket = @socket_import_stream($serverStream);

      // 设置配置项作用范围，第二个参数，对socket有效
      socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, (int)$this->keepAlive);

      if ($schema === 'tcp')
      {
        // 设置配置项作用范围，第二个参数，针对TCP有效
        socket_set_option($socket, SOL_TCP, TCP_NODELAY, (int)$this->noDelay);
      }
    }

    return $serverStream;
  }

  public function Run()
  {
    $servers = [];
    foreach ($this->uriList as $url)
    {
      $servers[] = $this->createServerStream($url);
    }
    $this->handler($servers);
  }
}