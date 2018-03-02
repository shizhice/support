<?php
/**
 * Created by PhpStorm.
 * User: shizhice
 * Date: 2018/1/30
 * Time: 上午9:23
 */

namespace Shizhice\Support;

use \Exception;

class Config
{
    static private $config;

    static private $configPath;

    private function __construct()
    {
    }

    /**
     * 初始化配置文件路径
     * @param $path
     * @throws Exception
     */
    static public function setConfigPath($path)
    {
        if (! is_dir($path)) {
            throw new \Exception(sprintf("配置文件%s路径不存在.", $path));
        }

        self::$configPath = $path;
    }

    /**
     * get a config with key
     * @param null $key
     * @param null $replace
     * @return null
     * @author shizhice<shizhice@gmail.com>
     * @throws Exception
     */
    static public function get($key = null,$replace = null)
    {
        if (is_null($key)) {
            throw new Exception('第一个参数必填');
        }

        $param = explode('.',$key);
        $file = $param[0];
        $offset = array_slice($param,1);

        if (! isset(self::$config[$file])) {
            if (!file_exists(self::$configPath. "/{$file}.php")) {
                throw new Exception("{$file}配置文件不存在");
            }
            self::$config[$file] = include_once self::$configPath. "/{$file}.php";
        }

        $value = self::recursionGetValue(self::$config[$file],$offset);
        return is_null($value) ? $replace : $value;
    }


    /**
     * 递归获取value
     * @param $array
     * @param array $keys
     * @author shizhice<shizhice@gmail.com>
     * @return null
     */
    static private function recursionGetValue($array,array $keys)
    {
        if (! is_array($array) && ! empty($keys)) {
            return null;
        }
        if (empty($keys)) {
            return $array;
        }
        if (! isset($array[$keys[0]])) {
            return null;
        }
        return self::recursionGetValue($array[$keys[0]],array_slice($keys,1));
    }
}