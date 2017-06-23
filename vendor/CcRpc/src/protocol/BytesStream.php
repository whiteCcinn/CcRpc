<?php

/**********************************************************\
 *                                                        *
 * CcRpc/http/BytesStream.php                             *
 *                                                        *
 * CcRpc BytesStream class for php 7.0+                   *
 *                                                        *
 * LastModified: June 23, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
 * \**********************************************************/

namespace CcRpc\protocol;


class BytesStream
{

  // 字节流
  public $_buffer;

  // 字节流长度
  public $_length;

  // 标志位
  private $_pos = 0;

  public function __construct(string $string = '')
  {
    $this->_buffer = $string;
    $this->_length = strlen($string);
  }

  /**********************************************************************************************************/
  /*                                              写字节流数据操作                                            */
  /**********************************************************************************************************/


  /**
   * 字节流写操作
   *
   * @param string $str
   * @param int    $length 是否需要截取，截取长度
   *
   * @return int 当前字节流长度
   */
  public function write(string $str, int $length = -1): int
  {
    if ($length == -1)
    {
      $this->_buffer .= $str;
      $length = strlen($str);
    } else
    {
      $this->_buffer .= substr($str, 0, $length);
    }
    $this->_length += $length;

    return $this->_length;
  }

  /**
   * 返回字节流
   *
   * @return string
   */
  public function toString()
  {
    return $this->_buffer;
  }

  /**
   * 重写__toString
   * print($this):string => $this->_buffer
   *
   * @return string
   */
  public function __toString()
  {
    return $this->_buffer;
  }

  /**
   * 重置
   */
  public function reset()
  {
    $this->_buffer = '';
    $this->_length = 0;
  }

  /**********************************************************************************************************/
  /*                                              读取字节流数据操作                                          */
  /**********************************************************************************************************/

  /**
   * 获取字节流中的单字节
   *
   * @param int $pos
   *
   * @return string
   */
  public function getChar(int $pos = 0): string
  {
    if ($pos < $this->_length)
    {
      return $this->_buffer[ $pos++ ];
    }

    return '';
  }

  public function doReadWhile($tag)
  {
    $pos = strpos($this->_buffer, $tag, $this->_pos);
    if ($pos !== false)
    {
      $s          = substr($this->_buffer, $this->_pos, $pos - $this->_pos);
      $this->_pos = $pos + strlen($tag);
    } else
    {
      $s          = substr($this->_buffer, $this->_pos);
      $this->_pos = $this->_length;
    }

    return $s;
  }
}