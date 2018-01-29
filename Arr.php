<?php
/**
 * Created by PhpStorm.
 * User: shizhice
 * Date: 2017/8/21
 * Time: 下午7:58
 */

namespace Shizhice\Support;


class Arr
{
    /**
     * return only key data from array
     * @param array $input
     * @param $keys
     * @return array
     */
    static public function only(array $input, $keys)
    {
        return array_intersect_key($input, array_flip((array) $keys));
    }

    /**
     * return except key data from array
     * @param array $input
     * @param $keys
     * @return array
     */
    static public function except(array $input, $keys)
    {
        $keys = is_array($keys) ? $keys : array_slice(func_get_args(),1);

        foreach ($keys as $key) {
            unset($input[$key]);
        }

        return $input;
    }

    /**
     * extend a array
     * @param array $to
     * @param array $from
     * @return array
     */
    static public function extend(array $to, array $from)
    {
        foreach ($from as $key => $value) {
            if (is_array($value)) {
                $to[$key] = self::extend((array) (isset($to[$key]) ? $to[$key] : []),$value);
            }else{
                $to[$key] = $value;
            }
        }
        return $to;
    }

    /**
     * array to xml
     * @param $arr
     * @return string
     */
    static public function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * xml to array
     * @param $xml
     * @return mixed
     */
    static public function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    /**
     * dic order
     * @param array $param
     * @return array
     */
    static public function sort(array $param)
    {
        ksort($param);
        reset($param);

        return $param;
    }

    /**
     * through list to tree
     * @param $list
     * @param string $pk
     * @param string $pid
     * @param string $child
     * @param int $root
     * @return array
     */
    static public function throughListToTree($list, $pk = 'id', $pid = 'pid', $child = 'child', $root = 0)
    {
        $tree = [];
        if (is_array($list)) {
            $refer = [];
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }

            foreach ($list as $key => $data) {
                $parentId = isset($data[$pid]) ? $data[$pid] : 0;
                if ($root == $parentId) {
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }

        return $tree;
    }

    /**
     * replace array key
     * @param array $arr
     * @param string $field
     * @return array
     */
    static public function replaceArrayKey($arr = [],$field = '')
    {
        $newArr = [];
        if($arr){
            foreach ($arr as $value) {
                $newArr[$value[$field]] = $value;
            }
        }
        return $newArr;
    }

    /**
     * replace array key with array
     * @param array $arr
     * @param string $field
     * @return array
     */
    static public function replaceArrayKeyWithArray($arr = [],$field = '')
    {
        $newArr = [];
        if($arr){
            foreach ($arr as $value) {
                $newArr[$value[$field]][] = $value;
            }
        }
        return $newArr;
    }

    /**
     * list sort
     * @param $list
     * @param $field
     * @param string $sort
     * @return array|bool
     */
    static public function listSort($list, $field, $sort = 'asc')
    {
        if (is_array($list)) {
            $refer = array();
            $resultSet = array();
            foreach ($list as $i => $data)
                $refer[$i] = &$data[$field];
            switch ($sort) {
                case 'asc':
                    asort($refer);
                    break;
                case 'desc':
                    arsort($refer);
                    break;
            }
            foreach ($refer as $key => $val)
                $resultSet[] = &$list[$key];
            return $resultSet;
        }
        return false;
    }

    /**
     * list sum
     * @param $list
     * @param $field
     * @param null $callback
     * @return int
     */
    static public function listSum($list, $field,$callback = null)
    {
        $sum = 0;
        foreach ($list as $row) {
            if ($callback instanceof \Closure) {
                if ($callback($row) === true) {
                    $sum += $row[$field];
                }
            }else {
                $sum += $row[$field];
            }
        }
        return $sum;
    }

    /**
     * 将二维数组中的某个字段提取出来生成一个新数组
     * @param $list
     * @param $field
     * @return array
     */
    static public function throughListFieldToArray($list, $field)
    {
        if (function_exists("array_column")) {
            return array_column($list, $field);
        }

        $array = [];
        foreach ($list as $row) {
            $array[] = $row[$field];
        }
        return $array;
    }

    /**
     * 数组排序，支持第二排序
     * @param $list
     * @param $orderBy
     * @return array
     */
    static public function listSortForMany($list, $orderBy)
    {
        if (!is_array($list) || !$orderBy) {
            return $list;
        }

        if (is_string($orderBy)) {
            $orderBy = explode(',',$orderBy);
        }

        list($key,$sort) = explode(' ',reset($orderBy));

        $arr = [];
        foreach ($list as $row) {
            $arr[$row[$key]][] = $row;
        }

        if ($sort == "desc") {
            krsort($arr);
        }else{
            ksort($arr);
        }

        array_shift($orderBy);

        if (!empty($orderBy)) {
            foreach ($arr as &$row) {
                $row = self::listSortForMany($row,$orderBy);
            }
        }

        return (count($arr) >= 1) ? array_merge(...array_values($arr)) : $arr;
    }

    static public function random(array $input, $num)
    {
        if (count($input) >= $num) {
            return $input;
        }

        shuffle($input);

        return array_slice($input, 0, $input);
    }

    static public function addPrefixToArrayKey($array, $prefix, $before = true)
    {
        foreach ($array as &$key => $value) {
            $key .= $prefix;
            if ($before) {
                $key = $prefix.$key;
            }else{
                $key .= $prefix;
            }
        }
        return $array;
    }
}