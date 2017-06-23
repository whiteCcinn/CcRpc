<?php

/**********************************************************\
 *                                                        *
 * CcRpc/http/WriterProtocol.php                          *
 *                                                        *
 * CcRpc WriterProtocol class for php 7.0+                *
 *                                                        *
 * LastModified: June 23, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
\**********************************************************/

namespace CcRpc\protocol;


use CcRpc\exception\ProtocolException;

class WriterProtocol
{
  public $stream;

  public function __construct(BytesStream $steam)
  {
    $this->stream = $steam;
  }

  private static function _isUTF8($str)
  {
    return mb_detect_encoding($str, 'UTF-8', true) !== false;
  }

  /**
   * 追加数组字节流
   *
   * @param array $array
   *
   * @return int
   */
  public function appendArrayStream(array $array): int
  {
    /* example : c1{}*/
    $this->stream->write(Marks::MarkCollection);
    $count = count($array);
    if ($count > 0)
    {
      $this->stream->write((string)$count);
    }
    $this->stream->write(Marks::MarkBodyOpen);
    for ($i = 0; $i < $count; $i++)
    {
      $this->appendSerializeStream($array[ $i ]);
    }
    $this->stream->write(Marks::MarkBodyClose);

    return $this->stream->_length;
  }

  /**
   * 封装字节流
   *  1.暂不支持NaN校验      is_nan
   *  2.暂不支持infinite校验 is_infinite
   *
   * @return int
   * @throws ProtocolException
   */
  public function appendSerializeStream($val): int
  {
    if ($val === null)
    {
      return $this->appendNullStream($val);
    } elseif (is_scalar($val))
    {
      switch ($val)
      {
        case is_int($val):
          if ($val >= 0 && $val <= 9)
          {
            $this->stream->write((string)$val);
          } elseif ($val >= -2147483648 && $val <= 2147483647)
          {
            $this->appendIntegerStream($val);
          } else
          {
            $this->appendLongStream($val);
          }
          break;
        case is_bool($val):
          $this->appendBooleanStream($val);
          break;
        case is_float($val):
          $this->appendDoubleStream($val);
          break;
        case is_string($val):
          if ($val === '')
          {
            $this->appendEmptyStream($val);
          } elseif (self::_isUTF8($val))
          {
            $this->appendStringStream($val);
          }
          break;
        case is_array($val):
          /* do some thing*/
          break;
        case is_object($val):
          /* do some thing*/
          break;
        default:
          throw new ProtocolException('Not support to serialize this data');
      }
    }

    return $this->stream->_length;
  }

  /**
   * 追加空字节流
   *
   * @param $val
   *
   * @return int
   */
  public function appendStringStream(String $val): int
  {
    $len = strlen($val);
    $this->stream->write(Marks::MarkString);
    if ($len > 0)
    {
      $this->stream->write((string)$len);
    }
    $this->stream->write(Marks::MarkQuote . $val . Marks::MarkQuote);

    return $this->stream->_length;
  }

  /**
   * 追加空字节流
   *
   * @param $val
   *
   * @return int
   */
  public function appendEmptyStream($val): int
  {
    return $this->stream->write(Marks::MarkEmpty);
  }

  /**
   * 追加NULL字节流
   *
   * @param $val
   *
   * @return int
   */
  public function appendNullStream($val): int
  {
    return $this->stream->write(Marks::MarkNull);
  }

  /**
   * 追加int类型字节流
   *
   * @param int $val
   *
   * @return int
   */
  public function appendIntegerStream(int $val): int
  {
    return $this->stream->write(Marks::MarkInteger . (string)$val . Marks::MarkSemicolon);
  }

  /**
   * 追加long类型字节流
   *
   * @param int $val
   *
   * @return int
   */
  public function appendLongStream(int $val): int
  {
    return $this->stream->write(Marks::MarkInteger . (string)$val . Marks::MarkSemicolon);
  }

  /**
   * 追加boolean类型字节流
   *
   * @param bool $val
   *
   * @return int
   */
  public function appendBooleanStream(bool $val): int
  {
    return $this->stream->write($val ? Marks::MarkTrue : Marks::MarkFalse);
  }

  /**
   * 追加浮点类型字节流
   *
   * @param float $val
   *
   * @return int
   */
  public function appendDoubleStream(float $val): int
  {
    return $this->stream->write(Marks::MarkDouble . (string)$val . Marks::MarkSemicolon);
  }
}