<?php

/**********************************************************\
 *                                                        *
 * CcRpc/http/Client.php                                  *
 *                                                        *
 * CcRpc Client class for php 7.0+                        *
 *                                                        *
 * LastModified: June 23, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
 * \**********************************************************/

namespace CcRpc\tcp;


use CcRpc\exception\ClientException;
use CcRpc\exception\TcpClientException;
use CcRpc\ini\ConfigIni;
use CcRpc\protocol\BytesStream;
use CcRpc\protocol\Marks;
use CcRpc\protocol\ReadProtocol;
use CcRpc\protocol\WriterProtocol;

class Client
{
  public $keepAlive = true;
  public $keepAliveTimeout = 300;
  public $noDelay = true;
  private $uri = [];

  // 1024 * 8 缓存区 = 8kb
  public $readBuffer = 8192;
  public $writeBuffer = 8192;

  CONST VERSION = 0x80000000;

  public $addTimestamp = false;

  public function __construct($uri)
  {
    $this->uri = $uri;
    ConfigIni::loadIni(true);
  }

  /**
   * __call魔术方法
   *
   * @param string $name
   * @param array  $arguments
   *
   * @return string
   */
  public function __call(string $name, array $arguments)
  {
    $request = $this->_createStream($name, $arguments);

    if (ConfigIni::$debug)
    {
      file_put_contents('package.log', var_export($request, true));
    }
    $response = $this->_sendRequest($request);

    // 解析响应协议
    $data = $this->_deProtocol($response);

    return $data;
  }

  /**
   * 解析协议
   *
   * @param string $response
   *
   * @return string
   * @throws ClientException
   * @throws \Exception
   */
  private function _deProtocol(string $response)
  {
    if (empty($response)) throw new ClientException(__CLASS__ . ':' . __FUNCTION__ . " EOF");
    if ($response[ strlen($response) - 1 ] !== Marks::MarkEnd)
    {
      throw new ClientException("Wrong Response: \r\n$response");
    }

    $stream = new BytesStream($response);
    $reader = new ReadProtocol($stream);

    $char   = $stream->getChar();
    $result = '';
    switch ($char)
    {
      case Marks::MarkResult:
        $result = $reader->unpack();
        break;
      case Marks::MarkError:
        throw new \Exception($reader->readString());
        break;
    }

    return $result;
  }

  private function _createStream($method, $arguments)
  {
    $stream = new BytesStream(Marks::MarkCall);
    $write  = new WriterProtocol($stream);
    $write->appendStringStream($method);
    if (count($arguments) > 0)
    {
      for ($i = 0; $i < count($arguments); $i++)
      {
        $write->appendSerializeStream($arguments[ $i ]);
      }
    }
    $stream->write(Marks::MarkEnd);

    $request = $stream->toString();
    $stream->reset();

    $dataLength = strlen($request);

    if (!$this->addTimestamp)
    {
      $binaryRequest = pack("NN", $dataLength | self::VERSION, 1) . $request;
    } else
    {
      $binaryRequest = pack("N", $request);
    }

    return $binaryRequest;
  }

  private function _sendRequest($request)
  {
    $clientStream = $this->_createClientStream();

    @stream_socket_sendto($clientStream, $request);

    do
    {
      $data = @stream_socket_recvfrom($clientStream, 1024 * 8);
    } while ($data === false);

    fclose($clientStream);

    return $data;
  }

  private function _createClientStream()
  {
    $url = $this->uri;

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
    $clientStream = @stream_socket_client($url, $errno, $errmsg, 3600, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $context);

    if ($clientStream === false)
    {
      throw new TcpClientException($errmsg, $errno);
    }

    @stream_set_blocking($clientStream, false);
    @stream_set_read_buffer($clientStream, $this->readBuffer);
    @stream_set_write_buffer($clientStream, $this->writeBuffer);

    if (in_array($schema, ['tcp', 'unix']))
    {
      // 用客户端字节流创建socket
      $socket = @socket_import_stream($clientStream);

      // 设置配置项作用范围，第二个参数，对socket有效
      @socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, (int)$this->keepAlive);

      if ($schema === 'tcp')
      {
        // 设置配置项作用范围，第二个参数，针对TCP有效
        @socket_set_option($socket, SOL_TCP, TCP_NODELAY, (int)$this->noDelay);
      }
    }


    return $clientStream;
  }

}