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
	 * 取得当前日志记录器的配置数据，必须返回一个数组格式
	 *
	 * @return array
	 */
	public function getConfig();

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

	/** @var bool 记录器是否初始化 */
	private $loggerInit = false;

	private $loggerConfig = [
		'name'     => Log::DEFAULT_NAME,
		'level'    => Log::DEFAULT_LEVEL,
		'levels'   => [],
		'debug'    => false,
		'handle'   => null,
		'file'     => null,
		'fileType' => false,
		'format'   => 0,
	];

	protected function initLogger($config = null)
	{
		// 先取得全局的配置信息，并写入当前Logger
		$this->configure($this->loggerConfig['name']);
		// 再根据传入的config
		if (isset($config))
			$this->configure($config);
		$this->loggerInit = true;
	}

	public function configure($config)
	{
		if (empty($config))
			return $this;
		$type = gettype($config);
		// 字符串类型，表示从全局Log管理器中，取得相应的配置信息
		if ($type === KE_STR) {
			$name = $config;
			$config = Log::getConfig($name);
		} elseif ($type === KE_OBJ) {
			$name = get_class($config);
			$config = Log::getConfig($name);
		} elseif ($type !== KE_ARY) {
			$config = null;
		}

		// 数据过滤一下
		if (isset($config['handle'])) {
			// handle：必须是一个可callable的函数
			if (!is_callable($config['handle']))
				$config['handle'] = null;
		}
		if (isset($config['file'])) {
			// file：必须是一个非空的字符串类型
			// 如果和当前设定的文件一样，则不写入
			if (empty($config['file'])) {
				$config['file'] = null;
			} elseif ($config['file'] === $this->loggerConfig['file']) {
				unset($config['file']);
			} else {
				if (is_string($config['file'])) {
					$config['fileType'] = KE_STR;
				} elseif (is_array($config['file'])) {
					$config['fileType'] = KE_ARY;
				} else {
					unset($config['file'], $config['fileType']);
				}
			}
		}
		if (isset($config['level'])) {
			if (!is_numeric($config['level']))
				unset($config['level']);
		}
		if (isset($config['levels'])) {
			// levels采取手工的方式过滤，不进入array_merge的流程
			// 这里主要是为了确保多次写入的levels可以合并存在，
			// 一般来说，指定了level就足以满足正常需求，一旦需要指定特殊的levels，意味着必须针对性的做输出，
			// 而且这种针对性，可能会手动调用 `$logger->configure(['levels' => [LogLevel::WARN]])`
			// 所以levels，兼容最多条件的输入为好
			if (!is_array($config['levels']))
				$config['levels'] = (array)$config['levels'];
			foreach ($config['levels'] as $level) {
				$this->loggerConfig['levels'][$level] = true;
			}
			unset($config['levels']);
		}
		if (isset($config['debug'])) {
			$config['debug'] = (bool)$config['debug'];
		}
		if (isset($config['format'])) {
			// 我们并不想记录字符串格式的format，包括在朝外部的传递log数据的时候，也不希望将字符串格式的format来传递，这样需要太多的内存
			// 所以，这里的format实际上是一个整形的id，通过Log来全局管理format，并返回对应的id。
			if (is_numeric($config['format'])) { // 传入format id
				if (!Log::hasFormatId($config['format']))
					unset($config['format']);
			} else { // 传入指定的format
				if (empty($config['format']) || !is_string($config['format'])) {
					$config['format'] = null;
				} else {
					$config['format'] = Log::getFormatId($config['format']);
				}
			}
		}

		// 过滤了$config数据以后，再次检查一次，为空不写入
		if (!empty($config)) {
			$this->loggerConfig = array_merge($this->loggerConfig, $config);
		}
		return $this;
	}

	public function getConfig()
	{
		return $this->loggerConfig;
	}

	public function isLog($level)
	{
		return $level >= $this->loggerConfig['level'] || isset($this->loggerConfig['levels'][$level]);
	}

	public function isDebug()
	{
		return $this->loggerConfig['debug'];
	}

	public function getLogFile($level)
	{
		if ($this->loggerConfig['fileType'] === false)
			return false;
		if ($this->loggerConfig['fileType'] === KE_STR && $this->isLog($level))
			return $this->loggerConfig['file'];
		if ($this->loggerConfig['fileType'] === KE_ARY) {
			if (!empty($this->loggerConfig['file'][$level]))
				return $this->loggerConfig['file'][$level];
		}
		return false;
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
		// 必须确保，每个logger都被正确的初始化
		if (!$this->loggerInit)
			$this->initLogger(static::class);
		$logFile = $this->getLogFile($level);
		if ($this->isLog($level) || !empty($logFile)) {
			$log = Log::mkRawLog(
				$level,
				$message,
				$params,
				$this->loggerConfig['debug'],
				$this->loggerConfig['format'],
				$this->loggerConfig['name'],
				static::class
			);
			$this->onLogging($log);
			if (isset($this->loggerConfig['handle']))
				call_user_func_array($this->loggerConfig['handle'], [&$log]);
			if (!empty($logFile))
				LogBuffer::push($logFile, $log);
		}
		return $this;
	}

	protected function onLogging(array &$log)
	{
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

	private static $staticLogger = null;

	public static function getLogger()
	{
		if (!isset(self::$staticLogger))
			self::$staticLogger = Log::getLogger(static::getLoggerName());
		return self::$staticLogger;
	}

	public static function getLoggerName()
	{
		return static::class;
	}

	public static function log($level, $message, array $params = null)
	{
		if (!isset(self::$staticLogger))
			self::$staticLogger = static::getLogger();
		return self::$staticLogger->log($level, $message, $params);
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

	public function __construct($config = null)
	{
		$this->initLogger($config);
		$this->onConstruct();
	}

	protected function onConstruct()
	{
	}
}