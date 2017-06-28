<?php
class A
{
  function b()
  {
    return __CLASS__ . __FUNCTION__;
  }
}

class Future
{
  const PENDING   = 0;
  const FULFILLED = 1;
  const REJECTED  = 2;

  private $state = Future::PENDING;
  private $value;
  private $reason;
  private $subscribers = array();

  public function then($onfulfill, $onreject = null)
  {
    if (!is_callable($onfulfill))
    {
      $onfulfill = null;
    }
    if (!is_callable($onreject))
    {
      $onreject = null;
    }
    $next = new self();
    if ($this->state === self::FULFILLED)
    {
      $this->privateResolve($onfulfill, $next, $this->value);
    } elseif ($this->state === self::REJECTED)
    {
      $this->privateReject($onreject, $next, $this->reason);
    } else
    {
      array_push($this->subscribers, array(
          'onfulfill' => $onfulfill,
          'onreject'  => $onreject,
          'next'      => $next
      ));
    }

    return $next;
  }

  private function privateReject()
  {

  }

  public function reject()
  {
  }


  private function privateResolve($onfulfill, Future $next, $x)
  {
    if (is_callable($onfulfill))
    {
      $this->privateCall($onfulfill, $next, $x);
    } else
    {
      $next->resolve($x);
    }
  }

  private function privateCall($callback, Future $next, $x)
  {
    try
    {
      $r = $callback($x);
      $next->resolve($r);
    }
//    catch (UncatchableException $e)
//    {
//      throw $e->getPrevious();
//    }
    catch (Exception $e)
    {
      $next->reject($e);
    } catch (Throwable $e)
    {
      $next->reject($e);
    }
  }

  public function fill($future)
  {
    $this->then(array($future, 'resolve'), array($future, 'reject'));
  }

  public function resolve($value = null)
  {
    if ($value === $this)
    {
      $this->reject(new TypeError('Self resolution'));

      return;
    }
    if (Future::isFuture($value))
    {
      $value->fill($this);

      return;
    }
    if (($value !== null) and is_object($value) or is_string($value))
    {
      if (method_exists($value, 'then'))
      {
        $then   = array($value, 'then');
        $notrun = true;
        $self   = $this;
        try
        {
          call_user_func($then,
              function ($y) use (&$notrun, $self)
              {
                if ($notrun)
                {
                  $notrun = false;
                  $self->resolve($y);
                }
              },
              function ($r) use (&$notrun, $self)
              {
                if ($notrun)
                {
                  $notrun = false;
                  $self->reject($r);
                }
              }
          );
        }
//        catch (UncatchableException $e)
//        {
//          throw $e->getPrevious();
//        }
        catch (Exception $e)
        {
          if ($notrun)
          {
            $notrun = false;
            $this->reject($e);
          }
        } catch (Throwable $e)
        {
          if ($notrun)
          {
            $notrun = false;
            $this->reject($e);
          }
        }

        return;
      }
    }
    if ($this->state === self::PENDING)
    {
      $this->state = self::FULFILLED;
      $this->value = $value;
      while (count($this->subscribers) > 0)
      {
        $subscriber = array_shift($this->subscribers);
        $this->privateResolve(
            $subscriber['onfulfill'],
            $subscriber['next'],
            $value);
      }
    }
  }

  static function isFuture($obj)
  {
    return $obj instanceof Future;
  }

  static function value($v)
  {
    $future = new self();
    $future->resolve($v);

    return $future;
  }

  static function toFuture($obj)
  {
    return self::isFuture($obj) ? $obj : self::value($obj);
  }

}

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

$a = new A;
Promise::co(function () use ($a)
{
  var_dump(yield $a->b());
  var_dump(yield $a->b());
  var_dump(yield (($a->b()) . 'stpe1'));
});