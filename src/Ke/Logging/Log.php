<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/8
 * Time: 20:30
 */

namespace Ke\Logging;

use Ke\DataRegistry;
use Ke\Exception;

/**
 * 全局日志管理器
 *
 * @package Ke\Logging
 */
class Log extends DataRegistry
{

	const DEFAULT_FORMAT = '[level][year/month/day hour:minute:second microtime][method:uri][file:line] env - message debug params';

	protected static $domain = 'log.config';

	protected static $defaultData = [
		'class'  => BaseLogger::class,   // logger class
		'level'  => LogLevel::NOTICE,    // 记录的最低级别
		'levels' => [],                  // 记录的级别的枚举
		'debug'  => false,               // 是否debug，debug会占用比较多的php运行时内存
		'format' => self::DEFAULT_FORMAT,                // 基础的格式
		'file'   => null,                // 日志输出文件
		'handle' => null,                // 日志输出接管函数
	];

	private static $loggers = [];

	/**
	 * 取回所有已经被实例化的所有记录器
	 *
	 * @return array
	 */
	public static function getLoggers()
	{
		return self::$loggers;
	}

	/**
	 * @param $name
	 * @return LoggerImpl
	 * @throws Exception
	 */
	public static function getLogger($name = null)
	{
		$name = static::mkName($name);
//		$config = static::getConfig($name);
		$class = BaseLogger::class;

		if (isset(self::$loggers[$name]))
			return self::$loggers[$name];
//
//		if (!static::isDefine($name)) {
//			throw new Exception('Undefined log config {name}!', ['name' => $name]);
//		}

		self::$loggers[$name] = new $class($name);

		return self::$loggers[$name];
	}

	public static function getConfig($name)
	{
		$config = static::read($name);
		$default = static::read(static::$defaultName);
		if (empty($config))
			return $default;
		return array_merge($default, $config);
	}
}

class LogLevel
{

	/** 什么都不输出 */
	const NONE = 999999;

	/** 调试输出 - 最低级别，为开发过程中，为了调试特定的变量而输出 */
	const DEBUG = 10;

	/** 基本信息 - 低等级的标记信息 */
	const INFO = 20;

	/** 标志性信息 - 用于输出一些运行过程中的标志性信息 */
	const NOTICE = 30;

	/** 警告 - 比较重要，但不影响整体运行 */
	const WARN = 40;

	/** 出错了，但程序仍然可以继续运行 */
	const ERROR = 50;

	/** 致命性错误，应用程序将退出正常运行 */
	const FATAL = 60;

	/** 全部 */
	const ALL = -1;

	private static $levels = [
		self::DEBUG  => 'DEBUG',
		self::INFO   => 'INFO',
		self::NOTICE => 'NOTICE',
		self::WARN   => 'WARN',
		self::ERROR  => 'ERROR',
		self::FATAL  => 'FATAL',
	];

	/**
	 * 获取日志级别的名称
	 *
	 * @param int $level
	 * @return string
	 */
	public static function getName($level)
	{
		return isset(self::$levels[$level]) ? self::$levels[$level] : 'UNKNOWN';
	}
}