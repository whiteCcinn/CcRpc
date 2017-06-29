<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/23
 * Time: 16:41
 */

namespace CcRpc\exception;


use Exception;

class TcpClientException extends \Exception
{
  public function __construct($message = "", $code = 0, Exception $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }
}