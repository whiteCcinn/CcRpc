<?php

namespace CcRpc;

use CcRpc\protocol\BytesStream;

class Service
{
  protected function _handler(string $request = null, string $response = null)
  {
    $context = $this->_createContext($request, $response);

    $this->_setHeader($context);
  }

  private function _createContext($request, $response): \stdClass
  {
    $context           = new \stdClass();
    $context->server   = $this;
    $context->request  = $request;
    $context->response = $response;
    $context->userdata = new \stdClass();

    return $context;
  }

  private function _isGet(): bool
  {
    return isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'GET');
  }

  private function _isPost(): bool
  {
    return isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'POST');
  }

  private function _setHeader(\stdClass $context)
  {
    header("Content-Type: text/plain");
    $result = '';
    if ($this->_isGet())
    {
      $result = $this->_packageFunction();
    }
  }

  private function _packageFunction(): string
  {
    $stream = new BytesStream();

  }
}