<?php

/**********************************************************\
 *                                                        *
 * CcRpc/async/Promise.php                                *
 *                                                        *
 * CcRpc Marks class for php 7.0+                         *
 *                                                        *
 * LastModified: June 23, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
\**********************************************************/

namespace CcRpc\async;

class Promise
{
  static function toPromise($obj)
  {
    if (Future::isFuture($obj))
    {
      return $obj;
    }

    if ($obj instanceof \Generator)
    {
      return self::co($obj);
    }

    // 返回结果（Future）
    return Future::value($obj);
  }

  static function co($generator)
  {
    if (is_callable($generator))
    {
      $args      = array_slice(func_get_args(), 1);
      $generator = $generator(...$args);
    }

    // if not \Generator

    $future = new Future();

    // 状态完成的时候的回调函数
    $onfulfilled = function ($value) use (&$onfulfilled, &$onrejected, $generator, $future)
    {

      // 向生成器发送消息,生成器移动到下一个yield返回值
      $next = $generator->send($value);

      // 检查当前生成器是否还有效
      if ($generator->valid())
      {
        Promise::toPromise($next)->then($onfulfilled, $onrejected);
      } else
      {
        if (method_exists($generator, "getReturn"))
        {
          $ret = $generator->getReturn();
          $future->resolve(($ret === null) ? $value : $ret);
        } else
        {
          $future->resolve($value);
        }
      }
    };

    // 状态失败的时候会掉函数
    $onrejected = function ($err) use (&$onfulfilled, $generator, $future)
    {
      try
      {
        $onfulfilled($generator->throw($err));
      } catch (\Exception $e)
      {
        $future->reject($e);
      } catch (\Throwable $e)
      {
        $future->reject($e);
      }
    };

    Promise::toPromise($generator->current())->then($onfulfilled, $onrejected);
  }
}