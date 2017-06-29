<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/27
 * Time: 10:44
 */

namespace CcRpc\tcp;


use CcRpc\exception\TcpServerException;
use CcRpc\ini\ConfigIni;
use CcRpc\protocol\BytesStream;
use CcRpc\protocol\Marks;
use CcRpc\protocol\ReadProtocol;
use CcRpc\protocol\WriterProtocol;

class Service
{
  // 1024 * 8 缓存区 = 8kb
  public $readBuffer = 8192;
  public $writeBuffer = 8192;

  // Socket队列
  private $_readSockets = [];
  private $_writeSockets = [];

  // 回调函数
  public $_onSends = [];
  public $_onReceives = [];

  public function handler(array $servers)
  {
    ConfigIni::loadIni(true);

    // 插入队列头
    array_splice($this->_readSockets, 0, 0, $servers);
    while (!empty($this->_readSockets))
    {
      $reader = $this->_readSockets;
      $writer = $this->_writeSockets;

      $expect  = null;
      $tv_sec  = 3600;  // 秒
      $tv_usec = 0;     // 微秒

      // 判断read-Socket，或者write-Socket是否有数据变动,如果设置了$tv_sec的话，那么会维持一段时间，如果设置为0的话，则会立刻返回
      $n = @stream_select($reader, $writer, $expect, $tv_sec, $tv_usec);

      // 系统调用被信号突然中断等突发情况
      if ($n === false)
      {
        throw new TcpServerException(__CLASS__ . ':' . __FUNCTION__ . '-- Line 37 $n === false');
      }

      if ($n > 0)
      {
        foreach ($reader as $socket)
        {
          if (array_search($socket, $servers, true) !== false)
          {
            $this->_accept($socket);
          } else
          {
            ## 由于不在$servers里面，代表这个socket是接受过数据的句柄了
            $this->_read($socket);
          }
        }
      }
    }
  }

  /**
   * 读取数据
   *
   * @param $acceptSocket
   */
  private function _read($acceptSocket)
  {
    if (isset($this->_onReceives[ (int)$acceptSocket ]))
    {
      $onReceive = $this->_onReceives[ (int)$acceptSocket ];
      $data      = $onReceive();

      @stream_socket_sendto($acceptSocket, $data);

      fclose($acceptSocket);
    }
  }

  /**
   * accept接受数据
   *
   * @param $serverSocket
   */
  private function _accept($serverSocket)
  {

    /**
     * 第二个参数是是否延迟接收数据，由于我们用了stream_select监听socket了。
     * 又设置了维持时间，所以这一步就没必要延迟了。设置为0
     **/
    $acceptSocket = @stream_socket_accept($serverSocket, 0);

    if ($acceptSocket === false) return;

    /**
     * 该参数的设置将会影响到像 fgets() 和 fread() 这样的函数从资源流里读取数据。
     * 在非阻塞模式下，调用 fgets() 总是会立即返回。
     * 而在阻塞模式下，将会一直等到从资源流里面获取到数据才能返回。
     */
    if (@stream_set_blocking($acceptSocket, false) === false)
    {
      return;
    }

    @stream_set_read_buffer($acceptSocket, $this->readBuffer);
    @stream_set_write_buffer($acceptSocket, $this->writeBuffer);

    /* Setter accept callback */

    $this->_readSockets[]                    = $acceptSocket;
    $this->_onSends[ (int)$acceptSocket ]    = $this->_getOnSend($acceptSocket);
    $this->_onReceives[ (int)$acceptSocket ] = $this->_getOnReceive($acceptSocket);
  }

  private function _getOnSend($acceptSocket)
  {
    return function ()
    {
    };
  }


  private function _getOnReceive($acceptSocket)
  {
    return function () use ($acceptSocket)
    {
      $request = @stream_socket_recvfrom($acceptSocket, $this->readBuffer/*,STREAM_PEEK // will flush cache */);

      /* deal with net-work protocol */
      $bytes        = $request;
      $headerLength = 4;
      $dataLength   = -1;
      while (true)
      {
        $length = strlen($bytes);
        if (($dataLength < 0) && ($length >= $headerLength))
        {
          list(, $dataLength) = unpack('N', substr($bytes, 0, 4));
          if (($dataLength & 0x80000000) !== 0)
          {
            $dataLength &= 0x7FFFFFFF;
            $headerLength = 8;
          }
        }
        if (($headerLength === 8) && ($length >= $headerLength))
        {
          list(, $time) = unpack('N', substr($bytes, 4, 4));
        }
        if (($dataLength >= 0) && (($length - $headerLength) >= $dataLength))
        {
          $request      = substr($bytes, $headerLength, $dataLength);
          $bytes        = substr($bytes, $headerLength + $dataLength);
          $time         = null;
          $headerLength = 4;
          $dataLength   = -1;
        } else
        {
          break;
        }
      }

      if (ConfigIni::$debug)
      {
        file_put_contents('access.log', var_export($request, true));
      }
      $stream = new BytesStream($request);
      $data   = '';
      switch ($stream->getChar())
      {
        case Marks::MarkCall:
          $data = $this->_invoke($stream);
          break;
        default:
          throw new TcpServerException(__CLASS__ . ' : ' . __FUNCTION__ . '- 无法解析标志');
      }

      return $data;
    };

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
   * @throws TcpServerException
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
        case Marks::MarkString:
          $data[] = $reader->readStringWithoutMark();
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
          throw new TcpServerException(__CLASS__ . ' : ' . __FUNCTION__ . '- 无法解析标志');
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

}