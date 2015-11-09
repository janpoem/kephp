<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/8
 * Time: 21:40
 */

namespace Ke;

/**
 * 全局的数据注册器
 *
 * 主要用于存放类库的配置信息，和多语言翻译信息
 *
 * @package Ke
 */
class DataRegistry implements AutoLoadClassImpl
{

	private static $storage = [];

	private static $types = [];

	protected static $domain = 'data';

	protected static $defaultName = '*';

	protected static $defaultData = false;

	public static function onLoadClass($class, $path)
	{
		$default = static::getDefaultData();
		if ($default !== false) {
			static::define(null, $default);
		}
	}

	public static function getDomain()
	{
		return static::$domain;
	}

	public static function getDefaultName()
	{
		return static::$defaultName;
	}

	public static function getDefaultData()
	{
		return static::$defaultData;
	}

	public static function mkName($name = null)
	{
		if (empty($name))
			$name = static::getDefaultName();
		return $name;
	}

	public static function define($name = null, $data = null)
	{
		$name = static::mkName($name);
		$domain = static::getDomain();
		if ($data === null) {
			if (isset(self::$storage[$domain][$name])) {
				unset(self::$storage[$domain][$name]);
				unset(self::$types[$domain][$name]);
				return true;
			}
		} else {
			$type = gettype($data);
			if (!isset(self::$storage[$domain][$name])) {
				self::$storage[$domain][$name] = $data;
				self::$types[$domain][$name] = $type;
				return true;
			} elseif ($data !== self::$storage[$domain][$name]) {
				if ($type === KE_ARY && $type === self::$types[$domain][$name]) {
					self::$storage[$domain][$name] = array_merge(self::$storage[$domain][$name], $data);
				} else {
					self::$storage[$domain][$name] = $data;
					self::$types[$domain][$name] = $type;
				}
				return true;
			}
		}
		return false;
	}

	public static function defineMulti(array $multiData)
	{
		$total = 0;
		foreach ($multiData as $name => $data)
			$total += static::define($name, $data);
		return $total;
	}

	public static function read($name = null, $default = false)
	{
		$name = static::mkName($name);
		$domain = static::getDomain();
		if (isset(self::$storage[$domain][$name]))
			return self::$storage[$domain][$name];
		return $default;
	}

	public static function query($names, $default = false)
	{
		$domain = static::getDomain();
		if (!isset(self::$storage[$domain]))
			return $default;
		if (isset(self::$storage[$domain][$names]))
			return self::$storage[$domain][$names];
		return depthQuery(self::$storage[$domain], $names, $default);
	}

	public static function getData()
	{
		$domain = static::getDomain();
		return isset(self::$storage[$domain]) ? self::$storage[$domain] : false;
	}

	public static function getAllData()
	{
		return self::$storage;
	}

	public static function isDefine($name = null)
	{
		$name = static::mkName($name);
		$domain = static::getDomain();
		return isset(self::$storage[$domain][$name]);
	}

	public static function isEmpty($name = null)
	{
		$name = static::mkName($name);
		$domain = static::getDomain();
		return empty(self::$storage[$domain][$name]);
	}

	public static function isProtect($name)
	{
		return false;
	}

	public static function remove($name = null)
	{
		$name = static::mkName($name);
		$domain = static::getDomain();
		if (isset(self::$storage[$domain][$name]) && !static::isProtect($name)) {
			unset(self::$storage[$domain][$name], self::$types[$domain][$name]);
			if (empty(self::$storage[$domain])) {
				unset(self::$storage[$domain], self::$types[$domain]);
			}
			return true;
		}
		return false;
	}
}