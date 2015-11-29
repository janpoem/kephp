<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm;

// Database
use Ke\Adm\Adapter\DatabaseImpl;
use Ke\Adm\Adapter\Database\PdoMySQL;
// CacheStore
use Ke\Adm\Adapter\CacheStoreImpl;
use Ke\Adm\Adapter\CacheStore\Memcache;
use Ke\Adm\Adapter\CacheStore\RedisCache;

class DbSource
{

	const PDO_MYSQL = 'pdo_mysql';

	protected static $defaultName = 'main';

	private static $configs = [];

	private static $adapters = [
		self::PDO_MYSQL => PdoMySQL::class,
	];

	private static $adapterInstances = [];

	public static function define($name = null, $config = null)
	{
		// DbSource::define([ 'host' => '127.0.0.1' ]);
		if (!empty($name) && !isset($config)) {
			$config = $name;
			$name = static::$defaultName;
		}
		if (empty($name))
			$name = static::$defaultName;
		if ($config instanceof DatabaseImpl) {
			if (isset(self::$adapterInstances[$name]))
				throw new Exception('Database adapter "{0}" is exist!', $name);
			$config->setName($name);
			self::$adapterInstances[$name] = $config;
			return true;
		}
		elseif (is_array($config) && !empty($config)) {
			if (isset(self::$adapterInstances[$name])) {
				self::$adapterInstances[$name]->configure($config);
			}
			else {
				if (empty(self::$configs[$name]))
					self::$configs[$name] = $config;
				else
					self::$configs[$name] = array_merge(self::$configs[$name], $config);
			}
			return true;
		}
		return false;
	}

	public static function defineMulti(array $configs)
	{
		foreach ($configs as $name => $config) {
			static::define($name, $config);
		}
	}

	public static function getConfig($name = null)
	{
		if (empty($name))
			$name = static::$defaultName;
		if (isset(self::$adapterInstances[$name]))
			return self::$adapterInstances[$name]->getConfig();
		return isset(self::$configs[$name]) ? self::$configs[$name] : false;
	}

	/**
	 * @param null $name
	 * @return DatabaseImpl
	 * @throws Exception
	 */
	public static function getAdapter($name = null)
	{
		if (empty($name))
			$name = static::$defaultName;
		if (isset(self::$adapterInstances[$name]))
			return self::$adapterInstances[$name];
		if (!isset(self::$configs[$name]))
			throw new Exception('Undefined db source "{0}".', $name);
		if (empty(self::$configs[$name]['adapter']))
			throw new Exception('Undefined adapter in db source "{0}".', $name);
		$class = self::$configs[$name]['adapter'];
		if (isset(self::$adapters[$class]))
			$class = self::$adapters[$class];
		if (!is_subclass_of($class, DatabaseImpl::class))
			throw new Exception('Invalid adapter class "{1}" in db source "{0}".', [$name, $class]);
		/** @var DatabaseImpl $adapter */
		$adapter = new $class();
		$adapter->setName($name);
		$adapter->configure(self::$configs[$name]);
		self::$adapterInstances[$name] = $adapter;
		return self::$adapterInstances[$name];
	}

	public static function mkTableName($name, $model, $tableName = null, $prefix = null)
	{
		if (empty($name))
			$name = static::$defaultName;
		if (empty(self::$configs[$name]))
			throw new Exception('Undefined db source "{0}".', $name);
		$config = self::$configs[$name];
		if (empty($tableName)) {
			list(, $class) = parseClass($model);
			$tableName = strtolower($class);
		}
		if (empty($prefix)) {
			$prefix = empty($config['prefix']) ? '' : trim($config['prefix'], '_');
		}
		else {
			$prefix = trim($config['prefix'], '_');
		}
		if (!empty($prefix))
			$tableName = $prefix . '_' . $tableName;
		return $tableName;
	}
}

class CacheSource
{

	const MEMCACHE = 'memcache';

	const REDIS = 'redis';

	protected static $defaultName = 'main';

	private static $configs = [];

	private static $adapters = [
		self::MEMCACHE => Memcache::class,
		self::REDIS    => RedisCache::class,
	];

	private static $adapterInstances = [];

	public static function define($name = null, $config = null)
	{
		// DbSource::define([ 'host' => '127.0.0.1' ]);
		if (!empty($name) && !isset($config)) {
			$config = $name;
			$name = static::$defaultName;
		}
		if (empty($name))
			$name = static::$defaultName;
		if ($config instanceof CacheStoreImpl) {
			if (isset(self::$adapterInstances[$name]))
				throw new Exception('CacheStore adapter "{0}" is exist!', $name);
			$config->setName($name);
			self::$adapterInstances[$name] = $config;
			return true;
		} elseif (is_array($config) && !empty($config)) {
			if (isset(self::$adapterInstances[$name])) {
				self::$adapterInstances[$name]->configure($config);
			} else {
				if (empty(self::$configs[$name]))
					self::$configs[$name] = $config;
				else
					self::$configs[$name] = array_merge(self::$configs[$name], $config);
			}
			return true;
		}
		return false;
	}

	public static function defineMulti(array $configs)
	{
		foreach ($configs as $name => $config) {
			static::define($name, $config);
		}
	}

	public static function getConfig($name = null)
	{
		if (empty($name))
			$name = static::$defaultName;
		if (isset(self::$adapterInstances[$name]))
			return self::$adapterInstances[$name]->getConfig();
		return isset(self::$configs[$name]) ? self::$configs[$name] : false;
	}

	/**
	 * @param null $name
	 * @return CacheStoreImpl
	 * @throws Exception
	 */
	public static function getAdapter($name = null)
	{
		if (empty($name))
			$name = static::$defaultName;
		if (isset(self::$adapterInstances[$name]))
			return self::$adapterInstances[$name];
		if (!isset(self::$configs[$name]))
			throw new Exception('Undefined cache source "{0}".', $name);
		if (empty(self::$configs[$name]['adapter']))
			throw new Exception('Undefined adapter in cache source "{0}".', $name);
		$class = self::$configs[$name]['adapter'];
		if (isset(self::$adapters[$class]))
			$class = self::$adapters[$class];
		if (!is_subclass_of($class, CacheStoreImpl::class))
			throw new Exception('Invalid adapter class "{1}" in cache source "{0}".', [$name, $class]);
		/** @var CacheStoreImpl $adapter */
		$adapter = new $class();
		$adapter->setName($name);
		$adapter->configure(self::$configs[$name]);
		self::$adapterInstances[$name] = $adapter;
		return self::$adapterInstances[$name];
	}
}