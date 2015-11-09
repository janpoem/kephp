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

use Exception as PhpException;

/**
 * 日志记录器接口
 *
 * 接口和错误日志等级，并不按照PSR的规范，而采用和PHP本身的错误等级一致的命名。
 *
 * @package Ke
 */
interface LoggerImpl
{

	/**
	 * @param $config
	 * @return LoggerImpl
	 */
	public function configure($config);

	/**
	 * @param $level
	 * @return bool
	 */
	public function isLog($level);

	/**
	 * @return bool
	 */
	public function isDebug();

	/**
	 * @param mixed      $level
	 * @param mixed      $message
	 * @param array|null $params
	 *
	 * @return LoggerImpl
	 */
	public function log($level, $message, array $params = null);

	/**
	 * debug记录
	 *
	 * @param string     $message 消息的内容
	 * @param array|null $params  额外记录的变量参数
	 * @return LoggerImpl
	 */
	public function debug($message, array $params = null);

	/**
	 * info记录
	 *
	 * @param string     $message 消息内容
	 * @param array|null $params  额外记录的变量参数
	 * @return LoggerImpl
	 */
	public function info($message, array $params = null);

	/**
	 * info记录
	 *
	 * @param string     $message 消息内容
	 * @param array|null $params  额外记录的变量参数
	 * @return LoggerImpl
	 */
	public function notice($message, array $params = null);

	/**
	 * warn记录
	 *
	 * @param string     $message 消息内容
	 * @param array|null $params  额外记录的变量参数
	 * @return LoggerImpl
	 */
	public function warn($message, array $params = null);

	/**
	 * error记录
	 *
	 * @param string     $message 消息内容
	 * @param array|null $params  额外记录的变量参数
	 * @return LoggerImpl
	 */
	public function error($message, array $params = null);

	/**
	 * fatal记录
	 *
	 * @param string     $message 消息内容
	 * @param array|null $params  额外记录的变量参数
	 * @return LoggerImpl
	 */
	public function fatal($message, array $params = null);
}

/**
 * 记录器实例方法的集合，混入这个特性，使一个类具有记录器的行为特征
 *
 * 注意，log函数仍然需要由被混入的类去实现。
 *
 * @package Ke\LoggerOps
 */
trait LoggerOps
{

	/** @var bool|array */
	private $config = false;

	private $handle = null;

	private $file = null;

	protected function initConfig()
	{
		$this->config = Log::read();
	}

	public function configure($config)
	{
		if ($this->config === false)
			$this->initConfig();
		if (!is_array($config)) {
			$config = (array)$config;
		}
		if (!empty($config))
			$this->config = array_merge($this->config, $config);
		if (isset($this->config['handle']) && is_callable($this->config['handle'])) {
			$this->handle = $this->config['handle'];
		}
		elseif (!empty($this->config['file']) && is_string($this->config['file'])) {
			$this->file = $this->config['file'];
		}
		return $this;
	}

	public function isLog($level)
	{
		if ($this->config === false)
			$this->initConfig();
		return $level >= $this->config['level'] || (isset($this->config['levels'][$level]) && $this->config['levels'][$level]);
	}

	public function isDebug()
	{
		if ($this->config === false)
			$this->initConfig();
		return !empty($this->config['debug']);
	}

	/**
	 * 日志记录的抽象接口实现
	 * LoggerOps本身并不知道、也不限定一个Logger要如何去记录日志，只是假设一个Logger有一个log的接口
	 * 而其他的记录方法都基于这个log的接口来完成。
	 *
	 * @param int        $level   记录的级别
	 * @param string     $message 消息的内容
	 * @param array|null $params  额外记录的变量参数
	 * @return $this
	 */
	public function log($level, $message, array $params = null)
	{
		if ($this->isLog($level) && (isset($this->handle) || isset($this->file))) {
			$row = [
				Log::IDX_FORMAT    => Log::getFormatId($this->config['format']),
				Log::IDX_MICROTIME => microtime(true),
				Log::IDX_LEVEL     => $level,
				Log::IDX_MESSAGE   => '',
				Log::IDX_PARAMS    => $params,
				Log::IDX_DEBUG     => null,
			];
			$debug = null;
			if ($message instanceof PhpException) {
				$row[Log::IDX_MESSAGE] = $message->getMessage();
				$row[Log::IDX_DEBUG] = $message;
			} else {
				$row[Log::IDX_MESSAGE] = (string)$message;
				if ($this->isDebug()) {
					$row[Log::IDX_DEBUG] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				}
			}
			if (isset($this->handle)) {
				call_user_func($this->handle, Log::prepareLog($row));
			} else {
				LogBuffer::push($this->file, $row);
			}
		}
		return $this;
	}

	/**
	 * debug记录
	 *
	 * @param string     $message 消息的内容
	 * @param array|null $params  额外记录的变量参数
	 * @return $this
	 */
	public function debug($message, array $params = null)
	{
		return $this->log(LogLevel::DEBUG, $message, $params);
	}

	/**
	 * info记录
	 *
	 * @param string     $message 消息内容
	 * @param array|null $params  额外记录的变量参数
	 * @return $this
	 */
	public function info($message, array $params = null)
	{
		return $this->log(LogLevel::INFO, $message, $params);
	}

	/**
	 * info记录
	 *
	 * @param string     $message 消息内容
	 * @param array|null $params  额外记录的变量参数
	 * @return $this
	 */
	public function notice($message, array $params = null)
	{
		return $this->log(LogLevel::NOTICE, $message, $params);
	}

	/**
	 * warn记录
	 *
	 * @param string     $message 消息内容
	 * @param array|null $params  额外记录的变量参数
	 * @return $this
	 */
	public function warn($message, array $params = null)
	{
		return $this->log(LogLevel::WARN, $message, $params);
	}

	/**
	 * error记录
	 *
	 * @param string     $message 消息内容
	 * @param array|null $params  额外记录的变量参数
	 * @return $this
	 */
	public function error($message, array $params = null)
	{
		return $this->log(LogLevel::ERROR, $message, $params);
	}

	/**
	 * fatal记录
	 *
	 * @param string     $message 消息内容
	 * @param array|null $params  额外记录的变量参数
	 * @return $this
	 */
	public function fatal($message, array $params = null)
	{
		return $this->log(LogLevel::FATAL, $message, $params);
	}
}

/**
 * 记录器静态方法的集合
 *
 * 混入这个特性能让一个静态类看起来像是一个日志记录器，拥有类似记录器的静态方法。
 *
 * @package Ke\Logging
 */
trait LoggerAward
{

	private static $logger = null;

	public static function getLogger()
	{
		if (!isset(self::$logger))
			self::$logger = Log::getLogger(static::getLoggerName());
		return self::$logger;
	}

	public static function getLoggerName()
	{
		return static::class;
	}

	public static function log($level, $message, array $params = null)
	{
		if (!isset(self::$logger))
			self::$logger = static::getLogger();
		return self::$logger->log($level, $message, $params);
	}

	public static function debug($message, array $params = null)
	{
		return static::log(LogLevel::DEBUG, $message, $params);
	}

	public static function info($message, array $params = null)
	{
		return static::log(LogLevel::INFO, $message, $params);
	}

	public static function notice($message, array $params = null)
	{
		return static::log(LogLevel::NOTICE, $message, $params);
	}

	public static function warn($message, array $params = null)
	{
		return static::log(LogLevel::WARN, $message, $params);
	}

	public static function error($message, array $params = null)
	{
		return static::log(LogLevel::ERROR, $message, $params);
	}

	public static function fatal($message, array $params = null)
	{
		return static::log(LogLevel::FATAL, $message, $params);
	}
}

class BaseLogger implements LoggerImpl
{

	use LoggerOps;

}