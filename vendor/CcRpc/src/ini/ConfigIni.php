<?php

/**********************************************************\
 *                                                        *
 * CcRpc/http/ConfigIni.php                               *
 *                                                        *
 * CcRpc Client class for php 7.0+                        *
 *                                                        *
 * LastModified: June 26, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
 * \*******************************************************/

namespace CcRpc\ini;

class ConfigIni
{
  private static $defaultMode = 'dev';

  const USER_SET_FILE = 'setter.ini.php';

  const INI_NAME = 'CcRpc.php.ini';

  const INI_PATH = __DIR__ . DIRECTORY_SEPARATOR . self::INI_NAME;

  public static $config = [];

  /*----ini var----*/

  public static $debug = '';

  public static function loadIni(bool $autoSet = false)
  {
    self::$config = parse_ini_file(self::INI_PATH, true);
    $userSetter   = require_once __DIR__ . DIRECTORY_SEPARATOR . self::USER_SET_FILE;
    !isset($userSetter['mode']) && $userSetter['mode'] = self::$defaultMode;
    $arr = self::$config[ $userSetter['mode'] ];
    if ($autoSet)
    {
      foreach ($arr as $key => $value)
      {
        self::setNPrototype($key, $value);
      }
    }

    return $arr;
  }

  /**
   * 设置属性
   *
   * @param string $key
   * @param        $value
   * @param bool   $overwrite 是否覆盖设置
   *
   * @return bool
   */
  public static function setPrototype(string $key, $value, bool $overwrite = false): bool
  {
    if (!isset(self::$$key))
    {
      self::$$key = $value;
    } else
    {
      if ($overwrite)
      {
        return self::setNPrototype($key, $value);
      } else
      {
        return false;
      }
    }

    return true;
  }

  /**
   * 设置属性(覆盖)
   *
   * @param string $key
   * @param        $value
   *
   * @return bool
   */
  public static function setNPrototype(string $key, $value): bool
  {
    self::$$key = $value;
    return true;
  }

  /**
   * 魔术方法
   *
   * @param $key
   *
   * @return null
   */
  public function __get($key)
  {
    return isset(self::$$key) ? self::$$key : null;
  }
}