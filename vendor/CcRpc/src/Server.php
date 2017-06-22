<?php
namespace CcRpc;

use CcRpc\exception\ServerException;

class Server extends Service
{
  private $_calls = [];

  private $_names = [];

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