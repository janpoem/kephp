<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/19 0019
 * Time: 4:03
 */

namespace Ke\Adm;

use Ke\Adm\Adapter\CacheStoreImpl;
use Ke\Adm\Adapter\DatabaseImpl;
use Ke\Adm\Adapter\PdoMySQL;

class Source
{

	const MAIN = 'main';

	private static $sources = [];

	private static $adapters = [];

	protected static $domain = 'global';

	protected static $defaultRemote = self::MAIN;

	protected static $knownAdapters = [];

	protected static $adapterImpl = null;

	public static function define(array $sources)
	{
		foreach ($sources as $remote => $source) {
			static::defineSource($remote, $source);
		}
	}

	public static function defineSource($remote, $source = null)
	{
		if (empty($remote))
			$remote = static::$defaultRemote;
		if ($source === null) {
			self::$sources[static::$domain][$remote] = null;
		} else {
			if (!is_array($source))
				$source = (array)$source;
			if (empty($source['adapter']))
				throw new Exception(Exception::UNDEFINED_ADAPTER, [$remote, static::class]);
			if (!static::isAllowAdapter($source['adapter']))
				throw new Exception(Exception::UNKNOWN_ADAPTER, [$remote, static::class, $source['adapter']]);
			if (!isset(self::$sources[static::$domain][$remote])) {
				self::$sources[static::$domain][$remote] = $source;
			} else {
				self::$sources[static::$domain][$remote] = array_merge(self::$sources[static::$domain][$remote], $source);
			}
		}
		return true;
	}

	public static function isAllowAdapter($adapter)
	{
		if (!empty($adapter) && is_string($adapter)) {
			if (isset(static::$knownAdapters[$adapter]))
				return true;
			if (!empty(static::$adapterImpl) && is_subclass_of($adapter, static::$adapterImpl))
				return true;
		}
		return false;
	}

	public static function getSource($remote = null)
	{
		if (empty($remote))
			$remote = static::$defaultRemote;
		if (empty(self::$sources[static::$domain][$remote]))
			return false;
		return self::$sources[static::$domain][$remote];
	}

	/**
	 * @param null $remote
	 * @return \Ke\Adm\Adapter\DatabaseImpl|\Ke\Adm\Adapter\CacheStoreImpl
	 * @throws Exception
	 */
	public static function getAdapter($remote = null)
	{
		if (empty($remote))
			$remote = static::$defaultRemote;
		if (isset(self::$adapters[static::$domain][$remote]))
			return self::$adapters[static::$domain][$remote];
		if (!isset(self::$sources[static::$domain][$remote]))
			throw new Exception(Exception::UNDEFINED_SOURCE, [$remote, static::class]);
		$source = self::$sources[static::$domain][$remote];
		if (isset(static::$knownAdapters[$source['adapter']]))
			$class = static::$knownAdapters[$source['adapter']];
		else
			$class = $source['adapter'];
		$adapter = new $class($remote, $source);
		self::$adapters[static::$domain][$remote] = $adapter;
		return self::$adapters[static::$domain][$remote];
	}
}

class Db extends Source
{

	const PDO_MYSQL = 'pdo_mysql';

	protected static $domain = 'db';

	protected static $knownAdapters = [
		self::PDO_MYSQL => PdoMySQL::class,
	];

	protected static $adapterImpl = DatabaseImpl::class;
}

class Cache extends Source
{

	protected static $domain = 'cache';

	protected static $adapterImpl = CacheStoreImpl::class;
}