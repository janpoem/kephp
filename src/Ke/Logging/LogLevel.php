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