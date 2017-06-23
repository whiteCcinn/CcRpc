<?php

/**********************************************************\
 *                                                        *
 * CcRpc/http/Server.php                                  *
 *                                                        *
 * CcRpc Server class for php 7.0+                        *
 *                                                        *
 * LastModified: June 23, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
\**********************************************************/

namespace CcRpc\http;

use CcRpc\exception\ServerException;

class Server extends Service
{
  public $_calls = [];

  public $_names = [];

  public function addFunction(string $func, string $alias = ''): self
  {
    if (!is_callable($func))
    {
      throw new ServerException('Argument func must be callable.');
    }

    if (empty($alias))
    {
      $alias = $func;
    }

    $name = strtolower($alias);

    if (!array_key_exists($name, $this->_calls))
    {
      $this->_names[] = $alias;
    }

    $call         = new \stdClass();
    $call->method = $func;
    $call->mode   = 0;

    $this->_calls[ $name ] = $call;

    return $this;
  }

  public function Run()
  {
    $this->_handler();
  }
}