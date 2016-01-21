<?php

namespace Agi\Cache;

use App;
use Agi\Exception;
use Agi\Util\String;


/**
 * Class XCache
 *
 * @package Agi\Cache
 * @author Janpoem created at 2014/10/28 12:52
 */
class XCache extends Storage
{

    protected $config = array(
        'prefix'            => null,
    );

    private $prefix = null;

    protected function setConfig(array $config)
    {
        if (!function_exists('xcache_get'))
            throw new Exception(array('cache.no_driver', $this->name, 'xcache'));
        if (App::isMode(App::CLI))
            throw new Exception('xcache can\'t used in CLI!');
        $this->config = array_merge($this->config, $config);
        $this->prefix = empty($this->config['prefix']) ? PROJECT_FLAG : $this->config['prefix'];
        return $this;
    }

    public function connect()
    {
        return $this;
    }

    public function getStorage()
    {
        return true;
    }

    public function isConnect()
    {
        return true;
    }

    public function has($key)
    {
        return xcache_isset("{$this->prefix}#{$key}");
    }

    public function set($key, $data, $expire = 0)
    {
        $type = gettype($data);
        if ($type === PHP_ARY || $type === PHP_OBJ || $type === PHP_RES)
            $data = String::serialize($data, String::PHP_SERIALIZE_SCHEME);
        return xcache_set("{$this->prefix}#{$key}", $data);
    }

    public function replace($key, $data, $expire = 0)
    {
        return $this->set($key, $data, $expire);
    }

    public function increment($key, $value = 1)
    {
        if (is_numeric($value))
            return xcache_inc("{$this->prefix}#{$key}", $value);
        return false;
    }

    public function decrement($key, $value = 1)
    {
        if (is_numeric($value))
            return xcache_dec("{$this->prefix}#{$key}", $value);
        return false;
    }

    public function get($key)
    {
        $data = xcache_get("{$this->prefix}#{$key}");
        return String::unserialize($data);
    }

    public function delete($key)
    {
        return xcache_unset("{$this->prefix}#{$key}");
    }

    public function flush()
    {
        return xcache_unset_by_prefix($this->prefix);
    }
}

 