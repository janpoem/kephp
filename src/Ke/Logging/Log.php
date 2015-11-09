<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
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

	const IDX_FORMAT = 0;

	const IDX_MICROTIME = 1;

	const IDX_LEVEL = 2;

	const IDX_MESSAGE = 3;

	const IDX_PARAMS = 4;

	const IDX_DEBUG = 5;

	const DEFAULT_FORMAT = '[level][year/month/day hour:minute:second microtime][method:relativeUri][file:line] env - message debug params';

	protected static $domain = 'log.settings';

	protected static $defaultData = [
		'level'  => LogLevel::NOTICE,    // 记录的最低级别
		'levels' => [],                  // 记录的级别的枚举
		'debug'  => false,               // 是否debug，debug会占用比较多的php运行时内存
		'format' => null,                // 基础的格式
		'file'   => null,                // 日志输出文件
		'handle' => null,                // 日志输出接管函数
	];

	private static $baseVars = [
		'datetime'    => 0,
		'env'         => '',
		'scheme'      => '',
		'host'        => '',
		'uri'         => '',
		'relativeUri' => '',
		'method'      => '',
		'file'        => '',
		'line'        => '',
		'br'          => PHP_EOL,
	];

	private static $formats = [];

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
		// 取得logger实际的loggerName
		$name = static::getRealName($name);
		$class = BaseLogger::class;

		if (isset(self::$loggers[$name]))
			return self::$loggers[$name];

		$config = static::getConfig($name);
		if ($config instanceof LoggerImpl) {
			self::$loggers[$name] = $config;
		} else {
			if (isset($config['class']) && is_string($config['class']) && $config['class'] !== $class) {
				if (class_exists($config['class'], true) ||
					!is_subclass_of($config['class'], LoggerImpl::class)
				) {
					throw new Exception('Invalid logger class in config {name}', ['name' => $name]);
				} else {
					$class = $config['class'];
				}
				unset($config['class']);
			}
		}

		/** @var LoggerImpl $logger */
		$logger = new $class();
		$logger->configure($config);
		self::$loggers[$name] = $logger;

		return self::$loggers[$name];
	}

	public static function getRealName($name)
	{
		if (empty($name))
			return static::getDefaultName();
		if (static::isDefine($name))
			return $name;
		return static::getDefaultName();
	}

	public static function getConfig($name)
	{
		if (static::isDefine($name)) {
			$config = static::read($name);
			if ($config instanceof LoggerImpl) {
				return $config;
			}
			$type = gettype($config);
			if ($type === KE_STR) {
				$config['class'] = $config;
			} elseif ($type !== KE_ARY) {
				$config = (array)$config;
			}
			return $config;
		}
		return [];
	}

	public static function getFormatId($format)
	{
		if (empty($format) || !is_string($format))
			$format = static::DEFAULT_FORMAT;
		if (empty(self::$formats)) {
			self::$formats[0] = $format;
			return 0;
		} else {
			$id = array_search($format, self::$formats);
			if ($id === false) {
				$id = count(self::$formats);
				self::$formats[$id] = $format;
			}
			return $id;
		}
	}

	public static function getFormat($id)
	{
		if (isset(self::$formats[$id]))
			return self::$formats[$id];
		return static::DEFAULT_FORMAT;
	}

	public static function getBaseVars()
	{
		if (self::$baseVars['datetime'] === 0) {
			self::$baseVars['env'] = KE_APP_ENV;
			self::$baseVars['scheme'] = KE_REQUEST_SCHEME;
			self::$baseVars['host'] = KE_REQUEST_HOST;
			self::$baseVars['method'] = PHP_SAPI === 'cli' ? 'CLI' : $_SERVER['REQUEST_METHOD'];
			self::$baseVars['uri'] = KE_REQUEST_URI;

			$uri = substr(KE_REQUEST_URI, strlen(KE_HTTP_BASE));
			if (empty($uri))
				$uri = '/';
			elseif (isset($uri[0]) && $uri[0] !== '/')
				$uri = '/' . $uri;
			self::$baseVars['relativeUri'] = $uri;

			$now = time();
			$dtKeys = ['year', 'month', 'day', 'hour', 'minute', 'second'];
			$dtFields = array_combine($dtKeys, explode('|', date('Y|m|d|h|i|s', $now)));
			self::$baseVars += $dtFields;
			self::$baseVars['datetime'] = $now;
		}
		return self::$baseVars;
	}

	public static function prepareLog(array $row, $returnLog = false)
	{
		$vars = static::getBaseVars();
		$formatId = $row[self::IDX_FORMAT];
		if (!isset(self::$formats[$formatId]))
			return false;
		// format
//		$format = self::$formats[$formatId];
		// microtime
		$vars['microtime'] = str_pad(round($row[self::IDX_MICROTIME] - (int)$row[self::IDX_MICROTIME], 6), 8, ' ', STR_PAD_RIGHT);
		$vars['millitime'] = str_pad(intval($vars['microtime'] * 1000), 6, ' ', STR_PAD_RIGHT);
		// level
		$vars['level'] = LogLevel::getName($row[self::IDX_LEVEL]);
		// message
		$vars['message'] = trim($row[self::IDX_MESSAGE]);
		// params
		$vars['params'] = '';
		if (!empty($row[self::IDX_PARAMS])) {
			foreach ($row[self::IDX_PARAMS] as $key => $item) {
				$vars['params'] .= PHP_EOL . $key . ' => ' . print_r($item, true);
			}
		}
		// file, line, debug
		$debug = $row[self::IDX_DEBUG];
		if ($debug instanceof \Exception) {
			$vars['file'] = $debug->getFile();
			$vars['line'] = $debug->getLine();
			$vars['debug'] = '';
		} elseif (is_array($debug) && !empty($debug)) {
			$last = $debug[count($debug) - 1];
			$vars['file'] = $last['file'];
			$vars['line'] = $last['line'];
			$vars['debug'] = '';
		}
		if (!$returnLog) {
			$vars['formatId'] = $formatId;
			return $vars;
		} else {
			return trim(strtr(self::$formats[$formatId], $vars));
		}
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