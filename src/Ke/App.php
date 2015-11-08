<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke;

/**
 * 应用程序
 *
 * 在实际的项目代码中，不应该直接使用Ke\Core\App，而应该使用项目的App(继承自Ke\Core\App)。
 * 如果用户未在项目中定义App，引导流程会将class_alias(Ke\Core\App, APP_CLASS)，保证这个Class一定存在。
 *
 * @package Ke\Core
 * @author  曾建凯 <janpoem@163.com>
 */
class App
{

	/** @var array 已知的服务器名 */
	private static $knownServers = [
		''          => KE_DEVELOPMENT,
		'0.0.0.0'   => KE_DEVELOPMENT,
		'localhost' => KE_DEVELOPMENT,
		'127.0.0.1' => KE_DEVELOPMENT,
	];

	private static $app = null;

	private static $profiler = null;

	/** @var string 项目的名称 */
	public $name = null;

	/** @var string 项目的基础Hash */
	public $salt = null;

	/** @var string 区域语言习惯 */
	public $locale = 'en_US';

	/** @var string 默认时区 */
	public $timezone = 'Asia/Shanghai';

	/** @var string 编码 */
	public $encoding = 'UTF-8';

	/** @var string http的路径前缀 */
	public $httpBase = null;

	/** @var string http的验证字段 */
	public $httpSecurityField = null;

	/** @var string http验证字段的内容加密的hash */
	public $httpSecuritySalt = null;

	/** @var array 声明SERVER_NAME所对应的应用程序运行环境 */
	public $servers = [];

	/**
	 * 取得项目的App实例
	 *
	 * @return App
	 * @throws Exception
	 */
	public static function getApp()
	{
		if (!isset(self::$app)) {
			$cls = KE_APP_CLASS;
			if (class_exists($cls, true)) {
				if (!is_subclass_of($cls, static::class))
					throw new Exception('无效的App类');
			} else {
				$cls = static::class;
			}
			self::$app = new $cls();
		}
		return self::$app;
	}

	final private function __construct()
	{
		$this->servers += self::$knownServers;

		/////////////////////////////////////////////////////////////////////////////
		// p1：基本环境的准备
		/////////////////////////////////////////////////////////////////////////////

		$method = 'on';

		// CLI模式下的兼容
		if (KE_APP_MODE === KE_CLI) {
			// 先尝试加载环境配置文件，这个文件以后会扩展成为json格式，以装载更多的信息
			$envFile = KE_APP_ROOT . '/env';
			if (is_file($envFile) && is_readable($envFile)) {
				$_SERVER['SERVER_NAME'] = trim(file_get_contents($envFile));
			}
		}

		// 加载公共配置数据
		importWithApp(KE_APP_CONF . '/common.php', $this);

		// Uri预备
		Uri::prepare();

		$env = $this->detectEnv();
		if (empty($env)) {
			if (isset($this->servers[KE_REQUEST_HOST]))
				$env = $this->servers[KE_REQUEST_HOST];
		}
		// 最后，再次严格的检查一次环境，确保不会出现这三个以外的环境声明
		if ($env !== KE_DEVELOPMENT && $env !== KE_TEST)
			$env = KE_PRODUCTION;

		define('KE_APP_ENV', $env);
		$method .= $env;

		// 加载当前环境相关的配置数据
		importWithApp(KE_APP_CONF . '/' . KE_APP_ENV . '.php', $this);

		if (KE_APP_MODE === KE_WEB) {
			$_SERVER['SCRIPT_NAME'] = purgePath($_SERVER['SCRIPT_NAME'], KE_PATH_DOT_REMOVE, KE_PATH_LEFT_REMAIN, '/');
			if (empty($this->httpBase)) {
				$this->httpBase = comparePath(KE_REQUEST_PATH, $_SERVER['SCRIPT_NAME'], '/');
			} elseif ($this->httpBase !== '/') {
				$this->httpBase = purgePath($this->httpBase, KE_PATH_DOT_REMOVE, KE_PATH_LEFT_REMAIN, '/');
			}
			define('KE_HTTP_BASE', $this->httpBase);
		}

		/////////////////////////////////////////////////////////////////////////////
		// p2：填充当前的APP实例的数据
		/////////////////////////////////////////////////////////////////////////////
		// 初始化项目的名称 => 不应为空，也必须是一个字符串
		if (empty($this->name) || !is_string($this->name))
			$this->name = KE_APP_DIR;

		// 一个App的完整摘要
		$summary = sprintf('%s(%s,%s,%s)', $this->name, KE_APP_ENV, KE_REQUEST_HOST, KE_APP_ROOT);

		// 项目的hash，基于完整摘要生成，而非基于用户设置的项目名称
		// hash，主要用于服务器缓存识别不同的项目时使用
		// 比如memcached，key为user.10，而这个项目的存储则应该是：$flag.user.10，来避免项目和项目之间的数据混串
		$hash = hash('crc32b', $summary);

		// 真正用于显示的项目名称，包含项目名称、环境、hash
		$this->name = "{$this->name}({$env}@{$hash})";

		// 项目的基本加密混淆码 => 不应为空，也必须是一个字符串，且必须不小于32长度
		if (empty($this->salt) || !is_string($this->salt) || strlen($this->salt) < 32)
			$salt = $summary;
		else
			$salt = $this->salt;

		define('KE_APP_NAME', $this->name);
		define('KE_APP_HASH', $hash);
		define('KE_APP_SALT', hash('sha512', $salt, true));
		// 敏感数据还是清空为妙
		$this->salt = null;

		// http验证字段，如果没指定，就只好使用一个统一的了
		if (empty($this->httpSecurityField) || !is_string($this->httpSecurityField))
			$this->httpSecurityField = '_ke_http_';

		// http验证字段的加密混淆码
		if (empty($this->httpSecuritySalt) || !is_string($this->httpSecuritySalt))
			$this->httpSecuritySalt = "{$this->name}:{$this->httpSecurityField}";

		$this->httpSecuritySalt = $this->hash($this->httpSecuritySalt);

		define('KE_HTTP_SECURITY_FIELD', $this->httpSecurityField, true);
		define('KE_HTTP_SECURITY_SALT', $this->httpSecuritySalt, true);
		// 敏感数据还是清空为妙
		$this->httpSecuritySalt = null;

		// 检查httpCharset
		if (empty($this->encoding) || false === @mb_encoding_aliases($this->encoding))
			$this->encoding = 'UTF-8';

		// 时区
		if (empty($this->timezone) || false === @date_default_timezone_set($this->timezone)) {
			$this->timezone = 'Asia/Shanghai';
			date_default_timezone_set($this->timezone);
		}

		define('KE_APP_TIMEZONE', $this->timezone, true);
		define('KE_APP_ENCODING', $this->encoding, true);

		// 系统的配置
		ini_set('default_charset', KE_APP_ENCODING);
		ini_set('default_mimetype', 'text/html');
		mb_internal_encoding(KE_APP_ENCODING);
		mb_http_output(KE_APP_ENCODING);

		$this->onBootstrap();
		call_user_func([$this, $method]);

		register_shutdown_function(function () {
			$this->onExiting();
		});

		set_error_handler([$this, 'errorHandle']);
		set_exception_handler([$this, 'exceptionHandle']);

		ini_set("display_errors", "off");
		error_reporting(E_ALL);
	}

	/**
	 * 应用程序引导接口
	 */
	protected function onBootstrap()
	{
	}

	/**
	 * PHP退出接口
	 */
	protected function onExiting()
	{
		$err = $this->getLastError();
		if (!empty($err))
			$this->echoError($err, 500);
	}

	/**
	 * 开发环境的接口
	 */
	protected function onDevelopment()
	{
	}

	/**
	 * 测试环境的接口
	 */
	protected function onTest()
	{
	}

	/**
	 * 产品环境的接口
	 */
	protected function onProduction()
	{
	}

	/**
	 * PHP错误的处理的接管函数
	 */
	public function errorHandle($no, $msg, $file, $line)
	{
		// 在App的层级，因为并不知道具体运行时的上下文环境，所以只是简单的输出错误信息而已。
		$this->echoError([
			'type'    => $no,
			'message' => $msg,
			'file'    => $file,
			'line'    => $line,
		], 500);
	}

	public function getLastError()
	{
		return error_get_last();
	}

	/**
	 * PHP异常处理的接管函数
	 *
	 * @param \Exception $ex
	 */
	public function exceptionHandle(\Exception $ex)
	{
		// 在App的层级，因为并不知道具体运行时的上下文环境，所以只是简单的输出错误信息而已。
		$this->echoError($ex, 500);
	}

	public function hash($content, $salt = KE_APP_HASH)
	{
		return hash('sha512', $content . $salt, true);
	}

	/**
	 * 识别当前App的运行环境，如果不做匹配，请确保该函数返回的结果为空。
	 *
	 * @return null
	 */
	public function detectEnv()
	{
		return null;
	}

	public function echoError($error, $code = 500)
	{
		$ex = null;
		if ($error instanceof \Exception) {
			$ex = $error;
			$error = [
				'type'    => get_class($ex),
				'message' => $ex->getMessage(),
				'file'    => $ex->getFile(),
				'line'    => $ex->getLine(),
			];
		} elseif (isset($error['type'])) {
			$error['type'] = getPhpErrorStr($error['type']);
		}
		$type = gettype($error);
		if ($type !== KE_OBJ && $type !== KE_ARY) {
			$error = ['message' => $error];
		}
		if (isset($error['file']) && KE_APP_ENV !== KE_DEVELOPMENT) {
			$error['file'] = $this->remainAppPath($error['file']);
		}
		if (KE_APP_MODE === KE_WEB) {
			header("{$_SERVER['SERVER_PROTOCOL']} {$code}", true, $code);
			$output = '';
			$tpl = '<h1>An error occurred</h1><table><tr><th>Error Type</th><td>{type}</td></tr><tr><th>Message</th><td>{message}</td></tr><tr><th>File</th><td>{file}</td></tr><tr><th>Line</th><td>{line}</td></tr>';
			if (KE_APP_ENV === KE_DEVELOPMENT) {
				$output = OutputBuffer::getInstance()->getFunctionBuffer('app_debug', function() {
					echo '<tr><th>Debug</th><td><pre>';
					debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
					echo '<pre></td></tr>';
				});
			}
			$tpl .= $output . '</table';
		} else {
			$tpl = '{type} - {message} [{file}:{line}]';
		}
		exit(substitute($tpl, $error));
	}

	public function remainAppPath($path)
	{
		return str_replace([KE_APP_ROOT, '\\'], ['/' . KE_APP_DIR, '/'], $path);
	}
}


