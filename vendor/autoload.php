<?php

// 加载异常处理
include_once 'AutoloadException.php';

const _NAMESPACE = 'CcRpc';

// 自动加载
spl_autoload_register('_autoload', false, true);

// 异常处理
//set_exception_handler('exception_handler');

// 错误处理
//set_error_handler('error_handler');

function _autoload($className)
{
  $prefix       = _NAMESPACE . '\\';
  $prefixLength = strlen($prefix);

  $file = '';
  if (0 === strpos($className, $prefix))
  {
    $file = explode('\\', substr($className, $prefixLength));
    $file = implode(DIRECTORY_SEPARATOR, $file) . '.php';
  }

  $path = __DIR__ . DIRECTORY_SEPARATOR . _NAMESPACE . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $file;


  if (is_file($path))
  {
    require_once $path;
  } else
  {
    echo $path . PHP_EOL;
    throw new AutoloadException('Autoload Fail');
  }
}

function exception_handler($e)
{
  echo get_class($e) . ':' . $e->getMessage() . PHP_EOL;
}

function error_handler()
{

}