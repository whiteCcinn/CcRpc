<?php

/**********************************************************\
 *                                                        *
 * CcRpc/http/Setter.php                                  *
 *                                                        *
 * CcRpc Setter class for php 7.0+                        *
 *                                                        *
 * LastModified: June 23, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
\**********************************************************/

namespace CcRpc\http;


class Setter
{
  public $settings;

  public function __construct(array $settings = [])
  {
    if ($settings !== null)
    {
      $this->settings = $settings;
    } else
    {
      $this->settings = [];
    }
  }

  public function __set($name, $value)
  {
    $this->settings[ $name ] = $value;
  }

  public function __get($name)
  {
    return isset($this->settings[ $name ]) ? $this->settings[ $name ] : null;
  }
}