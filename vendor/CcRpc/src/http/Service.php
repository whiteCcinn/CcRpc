<?php

/**********************************************************\
 *                                                        *
 * CcRpc/http/Service.php                                 *
 *                                                        *
 * CcRpc Service class for php 7.0+                       *
 *                                                        *
 * LastModified: June 23, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
\**********************************************************/

namespace CcRpc\http;

use CcRpc\protocol\BytesStream;
use CcRpc\protocol\Marks;
use CcRpc\protocol\WriterProtocol;

class Service
{
  const VERSION = '1.0';

  const Author = 'WhiteCcinn';

  protected $_calls = [];

  protected $_names = [];

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
    } elseif ($this->_isPost())
    {
      $this->_readRequest($context);
    } else
    {
      $result = $this->_packageFunction();
    }
    header('Content-Length: ' . strlen($result));

    // 版本信息
    header('CcRpc-Version: ' . self::VERSION);
    header('CcRpc-Author: ' . self::Author);

    return $this->_response($result);
  }

  private function _parseProtocol()
  {

  }

  /**
   * 封装协议
   *
   * @return string
   */
  private function _packageFunction(): string
  {
    $stream = new BytesStream();
    $write  = new WriterProtocol($stream);
    $stream->write(Marks::MarkFunctions);
    $write->appendArrayStream($this->_names);
    $stream->write(Marks::MarkEnd);
    $binaryData = $stream->toString();
    $stream->reset();

    return $binaryData;
  }

  private function _readRequest(\stdClass $context)
  {
    $request          = file_get_contents("php://input");
    $context->clients = $this;
    $context->methods = $this->_calls;
    $stream           = new BytesStream($request);
    switch ($stream->get())
    {
      case Marks::MarkCall:

        break;


    }

    return;
  }

  private function _response(string $binaryData): string
  {
    echo $binaryData;

    return $binaryData;
  }
}