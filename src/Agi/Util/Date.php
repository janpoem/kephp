<?php

namespace Agi\Util;


/**
 * Class Date
 *
 * @package Agi\Util
 * @author Janpoem created at 2014/10/1 8:41
 */
class Date
{

    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;

    public static function parse($value, $format = null)
    {
        // 整型格式，不做解析处理
        if (is_numeric($value))
            return intval($value);
        if ($value instanceof \DateTime)
            return $value->getTimestamp();
        if (!is_string($value))
            return false;
        if (!empty($format))
            $parse = date_parse_from_format($value, $format);
        else
            $parse = date_parse($value);
        return mktime($parse['hour'], $parse['minute'], $parse['second'], $parse['month'], $parse['day'], $parse['year']);
    }

    static public function beforeNow($datetime)
    {
        $timestamp = static::parse($datetime);
        if (!$timestamp)
            return null;
        $diff = NOW_TS - $timestamp;
        $unit = null;
        $suffix = '前';
        if ($diff < self::MINUTE) {
            $unit = '秒';
        } else if ($diff < self::HOUR) {
            $unit = '分钟';
            $last = round($diff / self::MINUTE, 0);
            $diff = intval($last);
        } else if ($diff < self::DAY) {
            $unit = '小时';
            $diff = round($diff / self::HOUR, 0);
        } else if ($diff < self::DAY * 10) {
            $unit = '天';
            $diff = round($diff / self::DAY, 0);
        } else {
            $diff = date(FORMAT_DATETIME, $timestamp) . ' ';
            $unit = $suffix = null;
        }
        return $diff . $unit . $suffix;
    }
}
