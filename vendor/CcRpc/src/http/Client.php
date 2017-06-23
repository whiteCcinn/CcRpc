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
\**********************************************************/

namespace CcRpc\http;


use CcRpc\exception\ClientException;
use CcRpc\protocol\BytesStream;
use CcRpc\protocol\Marks;
use CcRpc\protocol\WriterProtocol;

class Client
{
  public $keepAlive = true;
  public $keepAliveTimeout = 300;

  private $_header
      = [
          'Content-type' => 'application/CcRpc'
      ];
  private $_options
      = [
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
          CURLOPT_HEADER         => true,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POST           => true,
          CURLOPT_NOSIGNAL       => 1
      ];
  private $uri
      = [];

  private $_setter = null;

  public function __construct($uri)
  {
    $this->$uri[]  = $uri;
    $this->_setter = new Setter([
                                    'timeout' => 5
                                ]);
  }

  /**
   * __call魔术方法
   * @param string $name
   * @param array  $arguments
   *
   * @return string
   */
  public function __call(string $name, array $arguments)
  {
    $request  = $this->_createStream($name, $arguments);
    $context  = $this->_createContext();
    $response = $this->_sendRequest($request, $context);

    return $response;
  }

  /**
   * 设置header头
   *
   * @param string $key
   * @param string $value
   *
   * @return bool
   */
  public function setHeader(string $key, string $value): bool
  {
    $lowerName = strtolower($key);
    if ($lowerName != 'content-type' &&
        $lowerName != 'content-length' &&
        $lowerName != 'host'
    )
    {
      if ($value && !empty($value))
      {
        $this->_header[ $key ] = $value;

        return true;
      }
    }

    return false;
  }

  /**
   * 移除header头
   *
   * @param string $key
   *
   * @return bool
   */
  public function mvHeader(string $key): bool
  {
    if (in_array($key, $this->_header))
    {
      unset($this->_header[ $key ]);

      return true;
    }

    return false;
  }

  /**
   * 添加选项
   *
   * @param $name
   * @param $value
   *
   * @return bool
   */
  public function setOption(string $name, string $value): bool
  {
    if (!in_array($name, $this->_options[ $name ]))
    {
      $this->_options[ $name ] = $value;

      return true;
    }

    return false;
  }

  /**
   * 移除选项
   *
   * @param string $name
   *
   * @return bool
   */
  public function removeOption(string $name): bool
  {
    if (in_array($name, $this->_options[ $name ]))
    {
      unset($this->_options[ $name ]);

      return true;
    }

    return false;
  }

  /**
   * 创建上下文
   *
   * @return \stdClass
   */
  private function _createContext(): \stdClass
  {
    $context          = new \stdClass();
    $context->timeout = isset($this->_setter->timeout) ? $this->_setter->timeout : 0;

    return $context;
  }

  /**
   * 请求字节流协议封装
   *
   * @param string $method
   * @param array  $arguments
   *
   * @return string
   */
  private function _createStream(string $method, array $arguments): string
  {
    $stream = new BytesStream(Marks::MarkCall);
    $write  = new WriterProtocol($stream);
    $write->appendStringStream($method);
    if (count($arguments) > 0)
    {
      $write->appendArrayStream($arguments);
    }
    $stream->write(Marks::MarkEnd);

    $request = $stream->toString();
    $stream->reset();

    return $request;
  }

  /**
   * 发送请求
   *
   * @param string    $request 远程调用方法字节流
   * @param \stdClass $context 上下文
   *
   * @return string
   * @throws ClientException
   */
  private function _sendRequest(string $request, \stdClass $context): string
  {
    $timeout = $context->timeout;

    $curl = curl_init();
    foreach ($this->_options as $name => $value)
    {
      curl_setopt($curl, $name, $value);
    }
    curl_setopt($curl, CURLOPT_URL, $this->uri);
    if (!ini_get('safe_mode'))
    {
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    }
    curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

    if ($this->keepAlive)
    {
      $headers_array[] = "Connection: keep-alive";
      $headers_array[] = "Keep-Alive: " . $this->keepAliveTimeout;
      curl_setopt($curl, CURLOPT_FRESH_CONNECT, false);
      curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
    } else
    {
      $headers_array[] = "Connection: close";
    }

    foreach ($this->_header as $name => $value)
    {
      $headers_array[] = $name . ": " . $value;
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers_array);

    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout / 1000);

    $data = curl_exec($curl);

    $errno = curl_errno($curl);
    if ($errno)
    {
      throw new ClientException($errno . ": " . curl_error($curl));
    }

    $data = $this->_parseResponseData($data, $context);

    curl_close($curl);

    return $data;
  }

  /**
   * 解析消息体
   *
   * @param string    $response
   * @param \stdClass $context
   *
   * @return string
   * @throws ClientException
   */
  private function _parseResponseData(string $response, \stdClass $context): string
  {
    do
    {
      list($response_headers, $response) = explode("\r\n\r\n", $response, 2);
      $http_response_header    = explode("\r\n", $response_headers);
      $http_response_firstline = array_shift($http_response_header);
      $matches                 = array();
      if (preg_match('@^HTTP/[0-9]\.[0-9]\s([0-9]{3})\s(.*)@',
                     $http_response_firstline, $matches))
      {
        $response_code   = $matches[1];
        $response_status = trim($matches[2]);
      } else
      {
        $response_code   = "500";
        $response_status = "Unknown Error.";
      }
    } while (substr($response_code, 0, 1) == "1");
    $header = array();
    foreach ($http_response_header as $headerline)
    {
      $pair  = explode(':', $headerline, 2);
      $name  = trim($pair[0]);
      $value = (count($pair) > 1) ? trim($pair[1]) : '';
      if (array_key_exists($name, $header))
      {
        if (is_array($header[ $name ]))
        {
          $header[ $name ][] = $value;
        } else
        {
          $header[ $name ] = array($header[ $name ], $value);
        }
      } else
      {
        $header[ $name ] = $value;
      }
    }
    $context->httpHeader = $header;
    if ($response_code != '200')
    {
      throw new ClientException($response_code . ": " . $response_status . "\r\n\r\n" . $response);
    }

    return $response;

  }
}