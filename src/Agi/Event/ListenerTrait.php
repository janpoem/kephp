<?php
/**
 * Created by IntelliJ IDEA.
 * User: Janpoem
 * Date: 2015/3/20
 * Time: 16:41
 */

namespace Agi\Event;


trait ListenerTrait
{

    private static $_standardListener = null;

    public static function getStandardListener()
    {
        if (!isset(self::$_standardListener)) {
            self::$_standardListener = new Listener();
        }
        return self::$_standardListener;
    }

    public static function on($name, $handle, $once = false)
    {
        return static::getStandardListener()->on($name, $handle, $once);
    }

    public static function once($name, $handle)
    {
        return static::getStandardListener()->on($name, $handle, true);
    }

    public static function bind(array $events)
    {
        return static::getStandardListener()->bind($events);
    }

    public static function emit($name, $args = null)
    {
        return static::getStandardListener()->emit($name, $args);
    }

    public static function disable($name)
    {
        return static::getStandardListener()->disable($name);
    }

    public static function enable($name)
    {
        return static::getStandardListener()->enable($name);
    }
}