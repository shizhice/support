<?php
/**
 * Created by PhpStorm.
 * User: shizhice
 * Date: 2018/1/29
 * Time: 上午10:34
 */

namespace Shizhice\Support;


class Str
{
    /**
     * make a guid
     * @return string
     */
    static public function guid()
    {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double)microtime()*10000);
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);
            $uuid   = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
            return $uuid;
        }
    }

    /**
     * end with string
     * @param $haystack
     * @param $needle
     * @return bool
     */
    static public function endWith($haystack, $needle)
    {
        if (substr($haystack, -strlen($needle)) === (string) $needle) {
            return true;
        }

        return false;
    }

    /**
     * 字符串转数组 utf字符集切割
     * @param $str
     * @param int $l
     * @return array|array[]|false|string[]
     */
    static public function str_split_unicode($str, $l = 0)
    {
        $l = 0;
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * to chinese number
     * @param $num
     * @param bool $mode
     * @return mixed|string
     */
    static public function toChineseNum($num, $mode = true)
    {
        $char = array("零", "一", "二", "三", "四", "五", "六", "七", "八", "九");
        $dw = array("", "十", "百", "千", "", "万", "亿", "兆");
        $dec = "点";
        $retval = "";

        if ($mode)
            preg_match_all("/^0*(\d*)\.?(\d*)/", $num, $ar);
        else
            preg_match_all("/(\d*)\.?(\d*)/", $num, $ar);
        if ($ar[2][0] != "")
            $retval = $dec . ch_num($ar[2][0], false); //如果有小数，先递归处理小数
        if ($ar[1][0] != "") {
            $str = strrev($ar[1][0]);
            for ($i = 0; $i < strlen($str); $i++) {
                $out[$i] = $char[$str[$i]];
                if ($mode) {
                    $out[$i] .= $str[$i] != "0" ? $dw[$i % 4] : "";
                    if ($str[$i] + ($str[$i - 1] ?? 0) == 0)
                        $out[$i] = "";
                    if ($i % 4 == 0)
                        $out[$i] .= $dw[4 + floor($i / 4)];
                }
            }
            $retval = join("", array_reverse($out)) . $retval;
        }

        if (strpos($retval, "一十") === 0) {
            $retval = str_replace("一十", "十", $retval);
        }

        return $retval;
    }

    /**
     * bytes格式化
     * @param $size
     * @return string
     */
    static public function formatBytes($size)
    {
        $units = array(' B', ' KB', ' MB', ' GB', ' TB');

        for ($i = 0; $size >= 1024 && $i < 4; $i++)
            $size /= 1024;

        return round($size, 2).$units[$i];
    }

    /**
     * 获取唯一数字字符串
     * @param bool $long
     * @return string
     */
    static public function uniqueNum($long = false)
    {
        list($millisecond, $second) = explode(" ", microtime());

        $millisecond = str_pad(round($millisecond*1000),3,'0',STR_PAD_RIGHT);

        $daySecond = str_pad($second - strtotime(date("Y-m-d")),5,'0',STR_PAD_LEFT);

        $day = substr(date("Ymd"),2);

        $num = str_pad(rand(1,999),3,'0',STR_PAD_LEFT);

        if ($long === true) {
            $num .= str_pad(rand(1,999),3,'0',STR_PAD_LEFT);
        }

        return $day.$daySecond.$millisecond.$num;
    }
}