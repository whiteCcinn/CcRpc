<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/27
 * Time: 10:26
 */

namespace CcRpc\exception;


use Exception;

class HttpServerException extends ServerException
{
  public function __construct($message = "", $code = 0, Exception $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }
}