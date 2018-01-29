<?php
/**
 * Created by PhpStorm.
 * User: shizhice
 * Date: 2018/1/29
 * Time: 上午10:49
 */

namespace Shizhice\Support;


class Time
{
    /**
     * second to time
     * @param $times
     * @return string
     */
    static public function secondToTime($times)
    {
        $result = '00:00:00';

        if ($times > 0) {
            $hour = floor($times/3600);
            $minute = floor(($times-3600 * $hour)/60);
            $second = floor((($times-3600 * $hour) - 60 * $minute) % 60);
            $result = str_pad($hour, 2, "0", STR_PAD_LEFT).':'.str_pad($minute, 2, "0", STR_PAD_LEFT).':'.str_pad($second, 2, "0", STR_PAD_LEFT);
        }

        return $result;
    }

    /**
     * human time
     * @param $date
     * @return string
     */
    static public function humanTime($date)
    {
        $diffTime = time() - strtotime($date);

        if ($diffTime < 60) {
            return "刚刚";
        }

        if ($diffTime < 3600) {
            return (intdiv($diffTime, 60) ?: 1)."分钟前";
        }

        if ($diffTime < 86400) {
            return (intdiv($diffTime, 3600) ?: 1)."小时前";
        }

        if ($diffTime < 2592000) {
            return (intdiv($diffTime, 86400) ?: 1)."天前";
        }

        if ($diffTime < 15552000) {
            return (intdiv($diffTime, 2592000) ?: 1)."个月前";
        }

        if ($diffTime < 31536000) {
            return "半年前";
        }

        return (intdiv($diffTime, 31536000) ?: 1)."年前";
    }
}