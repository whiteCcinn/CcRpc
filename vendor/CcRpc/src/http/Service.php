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
 * \*******************************************************/

namespace CcRpc\http;

use CcRpc\exception\ServerException;
use CcRpc\ini\ConfigIni;
use CcRpc\protocol\BytesStream;
use CcRpc\protocol\Marks;
use CcRpc\protocol\ReadProtocol;
use CcRpc\protocol\WriterProtocol;

class Service
{
  const VERSION = '1.0';

  const Author = 'WhiteCcinn';

  protected $_calls = [];

  protected $_names = [];

  /**
   * 启动句柄
   *
   * @param string|null $request
   * @param string|null $response
   */
  protected function _handler(string $request = null, string $response = null)
  {
    ConfigIni::loadIni(true);

    $context = $this->_createContext($request, $response);

    $this->_setHeader($context);
  }

  /**
   * 创建上下文
   *
   * @param $request
   * @param $response
   *
   * @return \stdClass
   */
  private function _createContext($request, $response): \stdClass
  {
    $context           = new \stdClass();
    $context->server   = $this;
    $context->request  = $request;
    $context->response = $response;
    $context->userdata = new \stdClass();

    return $context;
  }

  /**
   * 判断请求是否为GET方法
   *
   * @return bool
   */
  private function _isGet(): bool
  {
    return isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'GET');
  }

  /**
   * 判断请求是否为POST方法
   *
   * @return bool
   */
  private function _isPost(): bool
  {
    return isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'POST');
  }

  /**
   * 设置header头
   *
   * @param \stdClass $context
   *
   * @return string
   */
  private function _setHeader(\stdClass $context)
  {
    header("Content-Type: text/plain");

    $result = '';
    if ($this->_isGet())
    {
      $result = $this->_packageFunction();
    } elseif ($this->_isPost())
    {
      $result = $this->_readRequest($context);
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

  /**
   * 封装Error协议
   *
   * @param string $errMsg
   *
   * @return string
   */
  private function _packageError(string $errMsg): string
  {
    $stream = new BytesStream();
    $write  = new WriterProtocol($stream);
    $stream->write(Marks::MarkError);
    $write->appendStringStream($errMsg);
    $stream->write(Marks::MarkEnd);
    $binaryData = $stream->toString();
    $stream->reset();

    return $binaryData;
  }

  /**
   * 读取Request数据
   *
   * @param \stdClass $context
   *
   * @return string
   * @throws ServerException
   */
  private function _readRequest(\stdClass $context): string
  {
    $request = file_get_contents("php://input");
    if (ConfigIni::$debug)
    {
      file_put_contents('access.log', var_export($request, true));
    }
    $context->clients = $this;
    $context->methods = $this->_calls;
    $stream           = new BytesStream($request);
    $data             = '';
    switch ($stream->getChar())
    {
      case Marks::MarkCall:
        $data = $this->_invoke($stream);
        break;
      default:
        throw new ServerException(__CLASS__ . ' : ' . __FUNCTION__ . '- 无法解析标志');
    }

    return $data;
  }

  /**
   * 调用
   *
   * @param BytesStream $stream
   *
   * @return string
   */
  private function _invoke(BytesStream $stream): string
  {
    $reader              = new ReadProtocol($stream);
    $name                = $reader->readString();
    $alias               = strtolower($name);
    $cc                  = new \stdClass();
    $cc->isMissingMethod = false;

    if (isset($this->_calls[ $alias ]))
    {
      $cc->method = $this->_calls[ $alias ]->method;
      $response   = $this->_realInvoke($reader, $cc, $stream);
    } else
    {
      $errMsg   = 'Can\'t not find the function ' . $name . '(). ';
      $response = $this->_packageError($errMsg);
    }

    return $response;
  }

  /**
   * 实际调用
   *
   * @param ReadProtocol $reader
   * @param \stdClass    $std
   * @param BytesStream  $stream
   *
   * @return string
   * @throws ServerException
   */
  private function _realInvoke(ReadProtocol $reader, \stdClass $std, BytesStream $stream): string
  {
    do
    {
      $break = false;
      $char  = $stream->getChar();
      switch ($char)
      {
        case is_numeric($char):
          $data[] = intval($char);
          break;
        case Marks::MarkArgs:
          $data[] = $reader->readArgsWithoutMark();
          break;
        case Marks::MarkMap:
          $data[] = $reader->readMapsWithoutMark();
          break;
        case Marks::MarkEnd:
          if (!isset($data))
            $data = [];
          $break = true;
          break;
        default:
          throw new ServerException(__CLASS__ . ' : ' . __FUNCTION__ . '- 无法解析标志');
      }
    } while (!$break);

    if (ConfigIni::$debug)
    {
      file_put_contents('args.log', var_export($data, true));
    }

    $data = ($std->method)(...$data);

    $data = $this->_encode($data);

    return $data;
  }

  /**
   * 封装成功响应协议
   *
   * @param $response
   *
   * @return string
   */
  private function _encode($response): string
  {
    $stream = new BytesStream();
    $write  = new WriterProtocol($stream);
    $stream->write(Marks::MarkResult);
    $write->appendSerializeStream($response);
    $stream->write(Marks::MarkEnd);
    $binaryData = $stream->toString();
    $stream->reset();

    return $binaryData;
  }

  /**
   * 输出封装后的数据
   *
   * @param string $binaryData
   *
   * @return string
   */
  private function _response(string $binaryData): string
  {
    echo $binaryData;

    return $binaryData;
  }
}