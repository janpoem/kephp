<?php

namespace Agi\Cache;


/**
 * Class Storage
 *
 * @package Agi\Cache
 * @author Janpoem created at 2014/10/28 11:38
 */
abstract class Storage
{

    protected $name = null;

    protected $config = array();

    public function __construct($name, array $config)
    {
        $this->name = $name;
        $this->setConfig($config);
    }

    abstract protected function setConfig(array $config);

    abstract public function connect();

    abstract public function getStorage();

    abstract public function isConnect();

    abstract public function has($key);

    abstract public function set($key, $data, $expire = 0);

    abstract public function replace($key, $data, $expire = 0);

    abstract public function increment($key, $value = 1);

    abstract public function decrement($key, $value = 1);

    abstract public function get($key);

    abstract public function delete($key);

    abstract public function flush();
}

 