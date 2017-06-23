<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/23
 * Time: 11:30
 */

namespace CcRpc\exception;


use Exception;

class ProtocolException extends \Exception
{
  public function __construct($message = "", $code = 0, Exception $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }
}