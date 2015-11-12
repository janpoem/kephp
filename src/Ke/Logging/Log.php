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

	const DEFAULT_NAME = 'global';

	const DEFAULT_FORMAT = '[Y/m/d h:i:s ms][levelName:class][method:relativeUri]traceFileBlock env: message params';

	const DEFAULT_LEVEL = LogLevel::NOTICE;

	protected static $domain = 'log.settings';

	protected static $defaultName = self::DEFAULT_NAME;

	private static $baseVars = false;

	private static $formats = [
		0 => self::DEFAULT_FORMAT,
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
		if (static::isDefine($name)) {
			$access = $name;
			$config = static::getConfig($name);
		} else {
			$access = static::getDefaultName();
			$config = static::getConfig($access);
		}

		if (isset(self::$loggers[$access]))
			return self::$loggers[$access];

		if ($config instanceof LoggerImpl) {
			self::$loggers[$access] = $config;
			return self::$loggers[$access];
		}

		$class = BaseLogger::class;
		if (isset($config['class']) && is_string($config['class']) && $config['class'] !== $class) {
			if (class_exists($config['class'], true) && is_subclass_of($config['class'], LoggerImpl::class)) {
				$class = $config['class'];
				unset($config['class']);
			} else {
				throw new Exception('Invalid logger class in config {name}', ['name' => $name]);
			}
		}

		self::$loggers[$name] = new $class($config);
		return self::$loggers[$name];
	}

	public static function getConfig($name = null)
	{
		if (empty($name))
			$name = static::getDefaultName();
		$config = static::read($name);
		if (!empty($config)) {
			if ($config instanceof LoggerImpl) {
				return $config;
			}
			$type = gettype($config);
			if ($type === KE_STR) {
				$config['class'] = $config;
			} elseif ($type !== KE_ARY) {
				$config = (array)$config;
			}
			$config['name'] = $name;
		} else {
			$config = [];
		}
		return $config;
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

	public static function hasFormatId($id)
	{
		return isset(self::$formats[$id]);
	}

	public static function getFormat($id)
	{
		if (isset(self::$formats[$id]))
			return self::$formats[$id];
		return static::DEFAULT_FORMAT;
	}

	public static function getBaseVars()
	{
		if (self::$baseVars === false) {
			self::$baseVars = [
				'datetime'       => 0,
				'env'            => KE_APP_ENV,
				'scheme'         => KE_REQUEST_SCHEME,
				'host'           => KE_REQUEST_HOST,
				'uri'            => KE_REQUEST_URI,
				'relativeUri'    => '',
				'method'         => PHP_SAPI === 'cli' ? 'CLI' : $_SERVER['REQUEST_METHOD'],
				'file'           => '',
				'line'           => '',
				'br'             => PHP_EOL,
				'traceFile'      => '',
				'traceFileBlock' => '',
			];
			if (KE_APP_MODE === KE_CLI_MODE) {
				self::$baseVars['relativeUri'] = KE_SCRIPT_FILE;
			} else {
				$uri = substr(KE_REQUEST_URI, strlen(KE_HTTP_BASE));
				if (empty($uri))
					$uri = '/';
				elseif (isset($uri[0]) && $uri[0] !== '/')
					$uri = '/' . $uri;
				self::$baseVars['relativeUri'] = $uri;
			}
			$now = time();
			$dtKeys = ['Y', 'm', 'd', 'h', 'i', 's'];
			$dtFields = array_combine($dtKeys, explode('|', date('Y|m|d|h|i|s', $now)));
			self::$baseVars += $dtFields;
			self::$baseVars['datetime'] = $now;
		}
		return self::$baseVars;
	}

	public static function prepareLog(array &$raw, $returnLog = false)
	{
		if (!isset($raw['prepared']))
			$raw['prepared'] = false;
		if (!$raw['prepared']) {
			$raw = array_merge(static::getBaseVars(), $raw);
			// microtime
			$microtime = $raw['ms'] - (int)$raw['ms'];
			$raw['ms'] = str_pad(round($microtime, 6), 8, ' ', STR_PAD_RIGHT);
			// level
			$raw['levelName'] = LogLevel::getName($raw['level']);
			// message
			$raw['message'] = trim($raw['message']);
			// params
			if (!empty($raw['params'])) {
				$params = $raw['params'];
				$raw['params'] = '';
				foreach ($params as $key => $item) {
					$raw['params'] .= PHP_EOL . $key . ' => ' . print_r($item, true);
				}
			}
			// file, line, debug
			if (!empty($raw['debug'])) {
				$debug = $raw['debug'];
				if ($debug instanceof \Exception) {
					$raw['file'] = $debug->getFile();
					$raw['line'] = $debug->getLine();
					$raw['debug'] = '';
				} elseif (is_array($debug) && !empty($debug)) {
					$last = $debug[count($debug) - 1];
					$raw['file'] = $last['file'];
					$raw['line'] = $last['line'];
					$raw['debug'] = '';
				}
				$raw['traceFile'] = $raw['file'] . ':' . $raw['line'];
				$raw['traceFileBlock'] = '[' . $raw['traceFile'] . ']';
			}
			$raw['log'] = false;
			$raw['prepared'] = true;
		}
		if (!$returnLog) {
			return $raw;
		} else {
			if ($raw['log'] === false) {
				$format = self::$formats[$raw['format']];
				$raw['log'] = trim(strtr($format, $raw));
			}
			return $raw['log'];
		}
	}

	public static function mkRawLog(
		$level = self::DEFAULT_LEVEL,
		$message = '',
		array $params = null,
		$isDebug = false,
		$format = 0,
		$name = self::DEFAULT_NAME,
		$class = null
	) {
		$raw = [
			'prepared' => false,
			'format'   => $format,
			'name'     => $name,
			'class'    => $class,
			'level'    => $level,
			'ms'       => microtime(true),
			'message'  => '',
			'params'   => $params,
			'debug'    => null,
		];
		if ($message instanceof \Exception) {
			$class = get_class($message);
			$raw['message'] = $class . ' - ' . $message->getMessage();
			$raw['debug'] = $message;
		} else {
			$raw['message'] = (string)$message;
			if ($isDebug) {
				$raw['debug'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			}
		}
		return $raw;
	}
}
