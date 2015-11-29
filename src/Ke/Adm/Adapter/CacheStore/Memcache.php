<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm\Adapter\CacheStore;

use Ke\Adm\Exception;
use Memcache as PhpMemcache;
use Ke\Adm\Adapter\CacheStoreImpl;

class Memcache implements CacheStoreImpl
{

	protected $name = null;

	protected $prefix = '';

	protected $server = '';

	protected $config = [
		'prefix'            => '',
		'colon'				=> self::DEFAULT_COLON,
		'host'              => '',
		'port'              => 11211,
		'compressThreshold' => 0,
		'compressRatio'     => 0,
	];

	/** @var PhpMemcache */
	private $memcache = null;

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function configure(array $config)
	{
		$this->config = array_merge($this->config, $config);
		if (!extension_loaded('memcache') || !class_exists(PhpMemcache::class, false))
			throw new Exception('Missing memcache extension!');
		if (empty($this->config['host']))
			throw new Exception('Memcache host not specified in cache source "{0}"!', [$this->name]);
		$this->server = "{$this->config['host']}:{$this->config['port']}";
		if (!empty($this->config['prefix']) && is_string($this->config['prefix'])) {
			if (empty($this->config['colon']))
				$this->config['colon'] = self::DEFAULT_COLON;
			$this->prefix = rtrim($this->config['prefix'], '\\/.:_-#') . $this->config['colon'];
		}
		return $this;
	}

	public function getConfig()
	{
		return $this->config;
	}

	protected function connect()
	{
		if (!isset($this->instance)) {
			$this->memcache = new PhpMemcache();
			$this->memcache->addServer($this->config['host'], $this->config['port']);
			$status = @$this->memcache->getExtendedStats();
			// unset $status[$server] or $status[$server] === false
			if (empty($status[$this->server])) {
				throw new Exception('Memcache connect failed about cache source "{0}"', [$this->name]);
			}
//			if ($this->memcache->getServerStatus($this->config['host'], $this->config['port']) === 0) {
//				if ($this->config['pconnect'])
//					$conn = @$this->memcache->pconnect($this->config['host'], $this->config['port']);
//				else
//					$conn = @$this->memcache->connect($this->config['host'], $this->config['port']);
//				if ($conn === false)
//					throw new Exception('Memcache connect failure about cache source "{0}"', [$this->name]);
//			}
			if ($this->config['compressThreshold'] > 0 && $this->config['compressRatio'] > 0)
				$this->memcache->setCompressThreshold($this->config['compressThreshold'], $this->config['compressRatio']);
		}
		return $this;
	}

	public function exists($key)
	{
		if (!isset($this->memcache))
			$this->connect();
		$key = $this->prefix . $key;
		return $this->memcache->get($key) !== false;
	}

	public function set($key, $data, $expire = 0)
	{
		if (!isset($this->memcache))
			$this->connect();
		if ($data === false)
			$data = 0;
		$key = $this->prefix . $key;
		return $this->memcache->set($key, $data, null, $expire);
	}

	public function get($key)
	{
		if (!isset($this->memcache))
			$this->connect();
		$key = $this->prefix . $key;
		return $this->memcache->get($key);
	}

	public function delete($key)
	{
		if (!isset($this->memcache))
			$this->connect();
		$key = $this->prefix . $key;
		return $this->memcache->delete($key);
	}

	public function replace($key, $data, $expire = 0)
	{
		if (!isset($this->memcache))
			$this->connect();
		$key = $this->prefix . $key;
		return $this->memcache->replace($key, $data, $expire);
	}

	public function increment($key, $value = 1)
	{
		if (!isset($this->memcache))
			$this->connect();
		$key = $this->prefix . $key;
		return $this->memcache->increment($key, $value);
	}

	public function decrement($key, $value = 1)
	{
		if (!isset($this->memcache))
			$this->connect();
		$key = $this->prefix . $key;
		return $this->memcache->decrement($key, $value);
	}

	public function flush()
	{
		if (!isset($this->memcache))
			$this->connect();
		return $this->memcache->flush();
	}
}