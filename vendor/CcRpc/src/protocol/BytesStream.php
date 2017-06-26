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
\**********************************************************/

namespace CcRpc\protocol;


use CcRpc\exception\ProtocolException;

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
   * @return string
   */
  public function getChar(): string
  {
    if ($this->_pos < $this->_length)
    {
      return $this->_buffer[ $this->_pos++ ];
    }

    return '';
  }

  /**
   * 一直读取到特定mark
   *
   * @param $mark
   *
   * @return string
   */
  public function doReadWhile($mark)
  {
    $pos = strpos($this->_buffer, $mark, $this->_pos);
    if ($pos !== false)
    {
      $s          = substr($this->_buffer, $this->_pos, $pos - $this->_pos);
      $this->_pos = $pos + strlen($mark);
    } else
    {
      $s          = substr($this->_buffer, $this->_pos);
      $this->_pos = $this->_length;
    }

    return $s;
  }

  public function readString($n)
  {
    $pos    = $this->_pos;
    $buffer = $this->_buffer;
    for ($i = 0; $i < $n; ++$i)
    {
      switch (ord($buffer[ $pos ]) >> 4)
      {
        case 0:
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
        case 6:
        case 7:
        {
          // 0xxx xxxx
          ++$pos;
          break;
        }
        case 12:
        case 13:
        {
          // 110x xxxx   10xx xxxx
          $pos += 2;
          break;
        }
        case 14:
        {
          // 1110 xxxx  10xx xxxx  10xx xxxx
          $pos += 3;
          break;
        }
        case 15:
        {
          // 1111 0xxx  10xx xxxx  10xx xxxx  10xx xxxx
          $pos += 4;
          ++$i;
          if ($i >= $n)
          {
            throw new ProtocolException('bad utf-8 encoding');
          }
          break;
        }
        default:
        {
          throw new ProtocolException('bad utf-8 encoding');
        }
      }
    }

    return $this->read($pos - $this->_pos);
  }

  public function read($n)
  {
    $s = substr($this->_buffer, $this->_pos, $n);
    $this->skip($n);

    return $s;
  }

  public function skip($n)
  {
    $this->_pos += $n;
  }


}