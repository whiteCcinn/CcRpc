<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/22
 * Time: 16:50
 */

namespace CcRpc\protocol;


class BytesStream
{
  private $_buffer;
  private $_length;

  /**
   * 字节流写操作
   *
   * @param string $str
   * @param int    $length 是否需要截取，截取长度
   */
  public function write(string $str, int $length = -1)
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
  }
}