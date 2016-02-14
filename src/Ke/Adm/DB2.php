<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm;

use Exception;

class Db
{

	const DEFAULT_SOURCE = 'default';

	const MYSQL = 'mysql';

	private static $classes = [
		self::MYSQL => Adapter\Db\PdoMySQL::class,
	];

	private static $sources = [];

	private static $adapters = [];

	public static function define(array $configs)
	{
		foreach ($configs as $source => $config) {
			self::set($source, $config);
		}
	}

	public static function set(string $source = null, array $config)
	{
		if (empty($source))
			$source = self::DEFAULT_SOURCE;
		if (isset(self::$adapters[$source])) {
			self::$adapters[$source]->configure($config);
		}
		else {
			if (empty(self::$sources[$source]))
				self::$sources[$source] = $config;
			else
				self::$sources[$source] = array_merge(self::$sources[$source], $config);
		}
		return true;
	}

	public static function get(string $source = null)
	{
		if (empty($source))
			$source = self::DEFAULT_SOURCE;
		if (isset(self::$adapters[$source]))
			return self::$adapters[$source]->getConfiguration();
		elseif (isset(self::$sources[$source]))
			return self::$sources[$source];
		return false;
	}

	/**
	 * @param string|null $source
	 * @return Adapter\DbAdapter|Adapter\Db\PdoMySQL
	 * @throws Exception
	 */
	public static function getAdapter(string $source = null)
	{
		if (empty($source))
			$source = self::DEFAULT_SOURCE;
		if (isset(self::$adapters[$source]))
			return self::$adapters[$source];
		if (!isset(self::$sources[$source]))
			throw new Exception("DB[{$source}]: undefined database config!");
		if (empty(self::$sources[$source]['adapter']))
			throw new Exception("DB[{$source}]: undefined database adapter!");
		$class = self::$sources[$source]['adapter'];
		if (isset(self::$classes[$class]))
			$class = self::$classes[$class];
		if (!is_subclass_of($class, Adapter\DbAdapter::class))
			throw new Exception("DB[{$source}]: invalid database adapter!");

		/** @var Adapter\DbAdapter $adapter */
		self::$adapters[$source] = new $class($source, self::$sources[$source]);
		return self::$adapters[$source];
	}

	public static function mkTableName(string $source = null, $model, $tableName = null, $prefix = null)
	{
		if (empty($source))
			$source = self::DEFAULT_SOURCE;
		if (!isset(self::$sources[$source]))
			throw new Exception("DB[{$source}]: undefined database config!");

		if (strpos($tableName, '.') > 0)
			return $tableName;

		$config = self::$sources[$source];
		if (empty($tableName)) {
			list(, $class) = parse_class($model);
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