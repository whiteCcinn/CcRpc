<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/22
 * Time: 16:23
 */

namespace CcRpc\exception;

use Exception;

class ServerException extends \Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
      parent::__construct($message, $code, $previous);
    }
}