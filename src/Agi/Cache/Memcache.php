<?php

namespace Agi\Cache;

use Agi\Exception;
use Memcache as PhpMemcache;

/**
 * Class Memcache
 *
 * @package Agi\Cache
 * @author Janpoem created at 2014/10/28 11:39
 */
class Memcache extends Storage
{

    const REQUIRE_CLASS = '\\Memcache';

    protected $config = array(
        'prefix'            => null,
        'servers'           => array(
            // host:port => '127.0.0.1:11211'
            //array('host', 'port')
        ),
        'compressThreshold' => 0,
        'compressRatio'     => 0,
    );

    private $prefix = null;

    /** @var PhpMemcache */
    private $storage = null;

    private $serverCount = 0;

    private $availableCount = 0;

    protected function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
        if (empty($this->config['servers']))
            throw new Exception(array('cache.memcache.no_servers', $this->name));
        $this->prefix = empty($this->config['prefix']) ? PROJECT_FLAG : $this->config['prefix'];
        return $this;
    }

    public function connect()
    {
        if (!isset($this->storage)) {
            if (!class_exists(self::REQUIRE_CLASS, false))
                throw new Exception(array('cache.no_driver', $this->name, self::REQUIRE_CLASS));
            $this->storage = new PhpMemcache();
            foreach ($this->config['servers'] as $server) {
                if (empty($server))
                    continue;
                $type = gettype($server);
                if ($type === PHP_STR) {
                    $server = explode(':', $server);
                    $type = PHP_ARY;
                }
                if ($type === PHP_ARY) {
                    if (call_user_func_array(array($this->storage, 'addServer'), $server))
                        $this->serverCount += 1;
                }
            }
            if ($this->config['compressThreshold'] > 0 && $this->config['compressRatio'] > 0)
                $this->storage->setCompressThreshold($this->config['compressThreshold'], $this->config['compressRatio']);
        }
        return $this;
    }

    public function getStorage()
    {
        if (!isset($this->storage)) {
            $this->connect();
            foreach (@$this->storage->getExtendedStats() as $status) {
                if ($status !== false)
                    $this->availableCount += 1;
            }
        }
//        if ($this->availableCount <= 0)
//            throw new Exception('None usable memcache server!');
        return $this->storage;
    }

    public function isConnect()
    {
        return isset($this->storage);
    }

    public function has($key)
    {
        return $this->get("{$this->prefix}#{$key}") === false;
    }

    public function set($key, $data, $expire = 0)
    {
        if ($data === false)
            $data = 0;
        return $this->getStorage()->set("{$this->prefix}#{$key}", $data, null, $expire);
    }

    public function replace($key, $data, $expire = 0)
    {
        return $this->getStorage()->replace("{$this->prefix}#{$key}", $data, null, $expire);
    }

    public function increment($key, $value = 1)
    {
        if (is_numeric($value))
            return $this->getStorage()->increment("{$this->prefix}#{$key}", $value);
        return false;
    }

    public function decrement($key, $value = 1)
    {
        if (is_numeric($value))
            return $this->getStorage()->decrement("{$this->prefix}#{$key}", $value);
        return false;
    }

    public function get($key)
    {
        return $this->getStorage()->get("{$this->prefix}#{$key}");
    }

    public function delete($key)
    {
        return $this->getStorage()->delete("{$this->prefix}#{$key}");
    }

    public function flush()
    {
        return $this->getStorage()->flush();
    }
}

 