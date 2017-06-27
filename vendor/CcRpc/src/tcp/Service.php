<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/27
 * Time: 10:44
 */

namespace CcRpc\tcp;


use CcRpc\exception\TcpServerException;

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
      $onReceive();
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
      $data = @fread($acceptSocket, $this->readBuffer);

      /* deal with protocol */

      return $data;
    };

  }

}