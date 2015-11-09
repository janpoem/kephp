<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/8
 * Time: 20:17
 */

namespace Ke\Logging;


class LogContext
{

	const IDX_FORMAT = 0;

	const IDX_MICROTIME = 1;

	const IDX_LEVEL = 2;

	const IDX_MESSAGE = 3;

	const IDX_PARAMS = 4;

	const IDX_DEBUG = 5;

	const IDX_FILE = 6;

	const IDX_HANDLE = 7;

	private static $logs = [];

	private static $formats = [];

	private static $isRegisterExit = false;

	private static $vars = [
		'datetime' => 0,
		//		'year'      => 0,
		//		'month'     => 0,
		//		'day'       => 0,
		//		'hour'      => 0,
		//		'minute'    => 0,
		//		'second'    => 0,
		'env'      => '',
		'scheme'   => '',
		'host'     => '',
		'uri'      => '',
		'method'   => '',
		'file'     => '',
		'line'     => '',
	    'br'       => PHP_EOL,
	];

	public static function push($level, $message, $params, $debug = null, $format = null, $file = null, $handle = null)
	{
		$formatId = -1;
		if (!empty($format)) {
			if (!isset(self::$formats[$format])) {
				$formatId = count(self::$formats);
				self::$formats[$format] = $formatId;
			} else {
				$formatId = self::$formats[$format];
			}
		}
		if ($formatId > -1) {
			self::$logs[] = [$formatId, microtime(true), $level, $message, $params, $debug, $file, $handle];
			if (!self::$isRegisterExit) {
				register_shutdown_function([static::class, 'exiting']);
			}
		}
		return true;
	}

	public static function exiting()
	{
		$baseVars = static::getVars();
		$formats = array_flip(static::$formats);
		$files = [];
		foreach (self::$logs as $row) {
			$vars = $baseVars; // copy new one
			$formatId = $row[self::IDX_FORMAT];
			if (!isset($formats[$formatId]))
				continue;
			// format
			$format = $formats[$formatId];
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
			$log = trim(strtr($format, $vars));
			if (!empty($row[self::IDX_HANDLE]) && is_callable($row[self::IDX_HANDLE])) {
				call_user_func($row[self::IDX_HANDLE], $log, $vars, $row[self::IDX_FILE]);
			} elseif (!empty($row[self::IDX_FILE])) {
				if (!isset($files[$row[self::IDX_FILE]]))
					$files[$row[self::IDX_FILE]] = '';
				$files[$row[self::IDX_FILE]] .= $log . PHP_EOL;
			}
		}
		if (!empty($files)) {
			foreach ($files as $file => $logs) {
				file_put_contents($file, $logs, FILE_APPEND);
			}
		}
	}

	public static function getLogs()
	{
		return self::$logs;
	}

	public static function getVars()
	{
		if (self::$vars['datetime'] === 0) {
			self::$vars['env'] = KE_APP_ENV;
			self::$vars['scheme'] = KE_REQUEST_SCHEME;
			self::$vars['host'] = KE_REQUEST_HOST;
			self::$vars['method'] = PHP_SAPI === 'cli' ? 'CLI' : $_SERVER['REQUEST_METHOD'];

			$uri = substr(KE_REQUEST_URI, strlen(KE_HTTP_BASE));
			if (empty($uri))
				$uri = '/';
			elseif (isset($uri[0]) && $uri[0] !== '/')
				$uri = '/' . $uri;
			self::$vars['uri'] = $uri;

			$now = time();
			$dtKeys = ['year', 'month', 'day', 'hour', 'minute', 'second'];
			$dtFields = array_combine($dtKeys, explode('|', date('Y|m|d|h|i|s', $now)));
			self::$vars += $dtFields;
			self::$vars['datetime'] = $now;
		}
//		if (!empty($log))
//			return array_merge(self::$vars, array_combine(self::$logKeys, $log));
		return self::$vars;
	}

	public static function setVar($name, $value)
	{
		self::$vars[$name] = $value;
		return true;
	}

	public static function setVars(array $vars)
	{
		self::$vars = array_merge(self::$vars, $vars);
		return true;
	}
}