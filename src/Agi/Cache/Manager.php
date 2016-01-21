<?php

namespace Agi\Cache;

use App;
use Agi\Exception;
use Agi\Cache\Memcache;
use Agi\Cache\XCache;

/**
 * Class Storage
 *
 * @package Agi\Cache
 * @author Janpoem created at 2014/10/28 10:49
 */
class Manager
{

    const IMPL_CLASS = '\\Agi\\Cache\\Storage';

    const MEMCACHE = 'memcache';

    const XCACHE = 'xcache';

    private static $managedStorageClasses = array(
        self::MEMCACHE => '\\Agi\\Cache\\Memcache',
        self::XCACHE   => '\\Agi\\Cache\\XCache',
    );

    private static $configs = array();

    private static $storageInstances = array();

    public static function addStorage($name, array $config, $force = false)
    {
        if (!$force && !App::isProcess(App::PRO_BOOTSTRAP))
            return false;
        if (empty($name) || !is_string($name))
            return false;
        if (!isset($config['storage']) || !isset(self::$managedStorageClasses[$config['storage']]))
            return false;
        self::$configs[$name] = $config;
        return true;
    }

    public static function registerStorage(array $configs, $force = false)
    {
        $total = 0;
        if (!$force && !App::isProcess(App::PRO_BOOTSTRAP))
            return $total;
        foreach ($configs as $name => $config) {
            if (self::addStorage($name, $config, $force))
                $total += 1;
        }
        return $total;
    }

    public static function hasStorage($name)
    {
        return self::$configs[$name];
    }

    /**
     * @param $name
     * @return Storage
     * @throws Exception
     */
    public static function getStorage($name)
    {
        if (!isset(self::$configs[$name]))
            return false;
        if (!isset(self::$storageInstances[$name])) {
            $config = self::$configs[$name];
            $class = self::$managedStorageClasses[$config['storage']];
            if (!class_exists($class, true) || !is_subclass_of($class, self::IMPL_CLASS))
                throw new Exception(array('cache.invalid_storage', $name, $class));
            self::$storageInstances[$name] = new $class($name, $config);
        }
        return self::$storageInstances[$name];
    }
}
