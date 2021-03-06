<?php

/**********************************************************\
 *                                                        *
 * CcRpc/http/ReadProtocol.php                            *
 *                                                        *
 * CcRpc ReadProtocol class for php 7.0+                  *
 *                                                        *
 * LastModified: June 23, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
\**********************************************************/

namespace CcRpc\protocol;


use CcRpc\exception\ProtocolException;

class ReadProtocol
{
  public $_stream = '';

  public function __construct(BytesStream $stream)
  {
    $this->_stream = $stream;
  }

  public function unpack()
  {
    $mark = $this->_stream->getChar();
    switch ($mark)
    {
      case is_numeric($mark):
        return intval($mark);
      case Marks::MarkInteger:
        return $this->readIntegerWithoutMark();
      case Marks::MarkLong:
        return $this->readLongWithoutMark();
      case Marks::MarkDouble:
        return $this->readDoubleWithoutMark();
      case Marks::MarkNull:
        return null;
      case Marks::MarkEmpty:
        return '';
      case Marks::MarkTrue:
        return true;
      case Marks::MarkFalse:
        return false;
      case Marks::MarkArgs:
        return $this->readArgsWithoutMark();
      case Marks::MarkMap:
        return $this->readMapsWithoutMark();
      case Marks::MarkBytes:
        return $this->readBytesWithoutMark();
      case Marks::MarkUTF8Char:
        return $this->readUTF8CharWithoutMark();
      case Marks::MarkString:
        return $this->readStringWithoutMark();
      case Marks::MarkObject:
        return $this->readObjectWithoutMark();
      default:
        throw new ProtocolException(__CLASS__ . ' : ' . __FUNCTION__ . ' Fail!');
    }
  }

  public function unpackMapKey()
  {
    $mark = $this->_stream->getChar();
    switch ($mark)
    {
      case is_numeric($mark):
        return intval($mark);
      case Marks::MarkInteger:
        return $this->readIntegerWithoutMark();
      case Marks::MarkLong:
      case Marks::MarkDouble:
        return $this->readDoubleWithoutMark();
      case Marks::MarkNull:
        return 'null';
      case Marks::MarkEmpty:
        return '';
      case Marks::MarkTrue:
        return 'true';
      case Marks::MarkFalse:
        return 'false';
      case Marks::MarkArgs:
        return $this->readArgsWithoutMark();
      case Marks::MarkBytes:
        return $this->readBytesWithoutMark();
      case Marks::MarkUTF8Char:
        return $this->readUTF8CharWithoutMark();
      case Marks::MarkString:
        return $this->readStringWithoutMark();
      case Marks::MarkObject:
        return $this->readObjectWithoutMark();
      default:
        throw new ProtocolException(__CLASS__ . ' : ' . __FUNCTION__ . ' Fail!');
    }
  }

  public function readIntegerWithoutMark()
  {
    return (int)($this->_stream->doReadWhile(Marks::MarkSemicolon));
  }

  public function readLongWithoutMark()
  {
    return $this->_stream->doReadWhile(Marks::MarkSemicolon);
  }

  public function readDoubleWithoutMark()
  {
    return (float)($this->_stream->doReadWhile(Marks::MarkSemicolon));
  }

  public function readBytesWithoutMark()
  {
    $count = (int)($this->_stream->doReadWhile(Marks::MarkQuote));
  }

  public function readUTF8CharWithoutMark()
  {
    return $this->_stream->readString(1);
  }

  public function readStringWithoutMark()
  {
    $len = (int)$this->_stream->doReadWhile(Marks::MarkQuote);
    $s   = $this->_stream->readString($len);
    $this->_stream->skip(1);

    return $s;
  }

  public function readObjectWithoutMark()
  {

  }

  public function readArgsWithoutMark()
  {
    $list  = array();
    $count = (int)$this->_stream->doReadWhile(Marks::MarkBodyOpen);
    for ($i = 0; $i < $count; ++$i)
    {
      $list[ $i ] = $this->unpack();
    }
    $this->_stream->skip(1);

    return $list;
  }

  public function readMapsWithoutMark()
  {
    $map   = [];
    $count = (int)$this->_stream->doReadWhile(Marks::MarkBodyOpen);
    for ($i = 0; $i < $count; ++$i)
    {
      $key         = $this->unpackMapKey();
      $map[ $key ] = $this->unpack();
    }
    $this->_stream->skip(1);

    return $map;
  }

  public function readString()
  {
    $char = $this->_stream->getChar();
    switch ($char)
    {
      case Marks::MarkNull:
        return null;
      case Marks::MarkEmpty:
        return '';
      case Marks::MarkUTF8Char:
        return $this->readUTF8CharWithoutMark();
      case Marks::MarkString:
        return $this->readStringWithoutMark();
      default:
        throw new ProtocolException(__CLASS__ . ' : ' . __FUNCTION__ . ' Error!');
    }
  }

}