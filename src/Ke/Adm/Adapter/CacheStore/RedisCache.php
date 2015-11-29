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

use Redis;
use Ke\Adm\Exception;
use Ke\Adm\Adapter\CacheStoreImpl;

class RedisCache implements CacheStoreImpl
{

	protected $name = null;

	protected $prefix = '';

	protected $server = '';

	protected $db = null;

	protected $config = [
		'prefix'     => '',
		'colon'      => self::DEFAULT_COLON,
		'host'       => '',
		'port'       => 6379,
		'pconnect'   => true,
		'db'         => null,
		//		Redis::SERIALIZER_NONE,
		//		Redis::SERIALIZER_IGBINARY
		'serializer' => Redis::SERIALIZER_PHP,
		// 'server' => '127.0.0.1:11211'
		// 'server' => ['127.0.0.1', 11211]
	];

	/** @var Redis */
	private $redis = null;

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function configure(array $config)
	{
		$this->config = array_merge($this->config, $config);
		if (!extension_loaded('redis') || !class_exists(Redis::class, false))
			throw new Exception('Missing redis extension!');
		if (empty($this->config['host']))
			throw new Exception('Redis host not specified in cache source "{0}"!', [$this->name]);
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
		if (!isset($this->redis)) {
			$this->redis = new Redis();
			if ($this->config['pconnect']) {
				$isConn = $this->redis->pconnect($this->config['host'], $this->config['port']);
			} else {
				$isConn = $this->redis->connect($this->config['host'], $this->config['port']);
			}
			if ($isConn === false)
				throw new Exception('Redis service connect error in cache source "{0}"!', [$this->name]);
			if (!empty($this->prefix))
				$this->redis->setOption(Redis::OPT_PREFIX, $this->prefix);
			if (!empty($this->config['serializer']))
				$this->redis->setOption(Redis::OPT_SERIALIZER, $this->config['serializer']);
			if (isset($this->config['db']) && is_numeric($this->config['db']) && $this->config['db'] >= 0) {
				$db = (int)$this->config['db'];
				if ($this->redis->select($db)) {
					$this->db = $db;
				}
			}
		}
		return $this;
	}

	public function exists($key)
	{
		if (!isset($this->redis))
			$this->connect();
		return $this->redis->exists($key);
	}

	public function set($key, $data, $expire = 0)
	{
		if (!isset($this->redis))
			$this->connect();
		return $this->redis->set($key, $data, $expire);
	}

	public function get($key)
	{
		if (!isset($this->redis))
			$this->connect();
		return $this->redis->get($key);
	}

	public function delete($key)
	{
		if (!isset($this->redis))
			$this->connect();
		return $this->redis->del($key);
	}

	public function replace($key, $data, $expire = 0)
	{
		if (!isset($this->redis))
			$this->connect();
		return $this->redis->set($key, $data, $expire);
	}

	public function increment($key, $value = 1)
	{
		if (!isset($this->redis))
			$this->connect();
		return $this->redis->incrBy($key, $value);
	}

	public function decrement($key, $value = 1)
	{
		if (!isset($this->redis))
			$this->connect();
		return $this->redis->decrBy($key, $value);
	}

	public function flush()
	{
		if (!isset($this->redis))
			$this->connect();
		if (isset($this->db))
			$this->redis->flushDB();
		else
			$this->redis->flushAll();
	}
}