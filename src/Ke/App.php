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

require 'Base.php';
require 'DirectoryRegistry.php';
require 'Loader.php';

use Throwable;
use Exception as PhpException;

class App
{

	/** @var array 已知的服务器名 */
	private static $knownServers = [
		''          => KE_DEVELOPMENT,
		'0.0.0.0'   => KE_DEVELOPMENT,
		'localhost' => KE_DEVELOPMENT,
		'127.0.0.1' => KE_DEVELOPMENT,
	];

	/** @var App */
	private static $app = null;

	private $isInit = false;

	private $root = null;

	/** @var string 项目的名称 */
	protected $name = null;

	/** @var string 项目的基础Hash */
	protected $salt = null;

	/** @var string 区域语言习惯 */
	protected $locale = 'en_US';

	/** @var string 默认时区 */
	protected $timezone = 'Asia/Shanghai';

	/** @var string 编码 */
	protected $encoding = 'UTF-8';

	/**
	 * 编码顺序，值类型应该数组格式，或者以逗号分隔的字符串类型
	 *
	 * 'GBK,GB2312,CP936'
	 * ['GBK', 'GB2312', 'CP936']
	 *
	 * @var string|array
	 */
	protected $encodingOrder = ['GBK', 'GB2312'];

	/** @var string http的路径前缀 */
	protected $httpBase = null;

	/** @var bool 是否开启了HTTP REWRITE */
	protected $httpRewrite = true;

	/** @var string http的验证字段 */
	protected $httpSecurityField = null;

	/** @var string http验证字段的内容加密的hash */
	protected $httpSecuritySalt = null;

	protected $httpSecuritySessionField = null;

	/** @var array 声明SERVER_NAME所对应的应用程序运行环境 */
	protected $servers = [];

	protected $helpers = [];

	protected $aliases = [
		'web' => 'public',
	];

	protected $dirs = [];

	protected $loader = null;

	final public static function getApp(): App
	{
		if (!isset(self::$app))
			throw new PhpException('未创建App实例！');
		return self::$app;
	}

//	final public static function bootstrap(string $root)
//	{
//		if (isset(self::$app))
//			return self::$app;
//		$appClass = static::class;
//		try {
//			new $appClass($root);
//		}
//		catch (Throwable $ex) {
//			print $ex->getMessage();
//		}
//		return self::$app;
//
//
//		// 项目的根目录和src源代码目录
//		$root = realpath($root);
//		if ($root === false || !is_dir($root))
//			exit('Invalid app root path!');
//		if (empty($src))
//			$src = 'src';
//		if (is_dir($src))
//			$src = realpath($src);
//		else
//			$src = $root . DS . $src;
//
//		// 项目的基础的类、命名空间和命名空间对应的路径
//		$appClass = static::class;
//		$appNs = null;
//		$appNsPath = $src;
//		if ($appClass !== __CLASS__) {
//			list($appNs) = parse_class($appClass);
//			if (!empty($appNs)) {
//				$appNsPath .= DS . $appNs;
//			}
//			if (!KE_IS_WIN)
//				$appNsPath = str_replace('\\', '/', $appNsPath);
//		}
//
//		define('KE_APP_SRC', $src);
//		define('KE_APP_ROOT', $root);
//		define('KE_APP_DIR', basename(KE_APP_ROOT));
//		define('KE_APP_CLASS', $appClass);
//		define('KE_APP_NS', $appNs);
//		define('KE_APP_NS_PATH', $appNsPath);
//
//		return self::$app;
//	}

	final public function __construct(string $root = null, array $dirs = null)
	{
		if (isset(self::$app))
			throw new PhpException('重复创建App实例！');
		self::$app = $this;

		// 检查根目录
		if (($this->root = real_dir($root)) === false)
			throw new PhpException('应用程序根目录(root)不是一个目录，或者路径无效！');

		// 绑定绝对路径
		if (!empty($dirs))
			$this->setDirs($dirs);

		// CLI模式加载特定的环境配置文件
		if (KE_APP_MODE === KE_CLI_MODE) {
			// 先尝试加载环境配置文件，这个文件以后会扩展成为json格式，以装载更多的信息
			$envFile = $this->root . '/env';
			if (is_file($envFile) && is_readable($envFile)) {
				$_SERVER['SERVER_NAME'] = trim(file_get_contents($envFile));
			}
		}

		// 匹配当前的环境
		$this->servers += self::$knownServers;
		$env = $this->detectEnv();
		// 不是开发模式或者测试模式，就必然是发布模式，确保在未知的模式下，返回发布模式
		if ($env !== KE_DEVELOPMENT && $env !== KE_TEST)
			$env = KE_PRODUCTION;

		define('KE_APP_ENV', $env);
		define('KE_APP_ROOT', $this->root);
		define('KE_APP_DIR', basename($this->root));
		define('KE_APP_SRC', $this->path('src'));

		// 项目的基础的类、命名空间和命名空间对应的路径
		$appClass = static::class;
		$appNs = null;
		$appNsPath = KE_APP_SRC;
		if ($appClass !== __CLASS__) {
			list($appNs) = parse_class($appClass);
			if (!empty($appNs)) {
				$appNsPath .= DS . $appNs;
			}
			if (!KE_IS_WIN)
				$appNsPath = str_replace('\\', '/', $appNsPath);
		}

		define('KE_APP_CLASS', $appClass);
		define('KE_APP_NS', $appNs);
		define('KE_APP_NS_PATH', $appNsPath);

		$this->loader = new Loader([
			'dirs'    => [
				'app_src'    => [KE_APP_SRC, 0],
				'app_helper' => [KE_APP_SRC . '/Helper', 0, Loader::HELPER],
				'ke_helper'  => [KE_ROOT . '/Helper', 1000, Loader::HELPER],
			],
			'classes' => import(__DIR__ . '/../classes.php'),
			'prepend' => true,
		]);
		$this->loader->start();
		if (!empty($this->helpers))
			$this->loader->loadHelper(...$this->helpers);

		// Uri准备
		Uri::prepare();

		$this->onConstruct();
	}

	protected function onConstruct() { }

	final public function init()
	{
		if ($this->isInit)
			return $this;

		$env = KE_APP_ENV;

		// 加载配置
		import([
			"{$this->root}/config/common.php",
			"{$this->root}/config/{$env}.php",
		]);

		if (KE_APP_MODE === KE_WEB_MODE) {
			$this->httpRewrite = (bool)$this->httpRewrite;
			if (empty($this->httpBase)) {
				$target = dirname($_SERVER['SCRIPT_NAME']);
				if ($target === '\\')
					$target = '/';
				$this->httpBase = compare_path(KE_REQUEST_PATH, $target, KE_DS_UNIX);
			}
			elseif ($this->httpBase !== '/') {
				$this->httpBase = purge_path($this->httpBase, KE_PATH_DOT_REMOVE ^ KE_PATH_LEFT_TRIM, KE_DS_UNIX);
			}
			// 上面的过滤，无论如何，过滤出来的httpBase都为没有首位的/的路径，如:path/dir/dir
			if (empty($this->httpBase))
				$this->httpBase = '/';
			elseif ($this->httpBase !== '/')
				$this->httpBase = '/' . $this->httpBase . '/';
			// 如果不指定重写，则httpBase应该是基于一个php文件为基础的
			if (!$this->httpRewrite)
				$this->httpBase .= KE_SCRIPT_FILE;
			define('KE_HTTP_BASE', $this->httpBase);
			define('KE_HTTP_REWRITE', (bool)$this->httpRewrite);
		}

		/////////////////////////////////////////////////////////////////////////////
		// p2：填充当前的APP实例的数据
		/////////////////////////////////////////////////////////////////////////////
		// 初始化项目的名称 => 不应为空，也必须是一个字符串
		if (empty($this->name) || !is_string($this->name))
			$this->name = KE_APP_DIR;

		// 一个App的完整摘要
		$summary = sprintf('%s(%s,%s,%s)', $this->name, KE_APP_ENV, KE_REQUEST_HOST, $this->root);

		// 项目的hash，基于完整摘要生成，而非基于用户设置的项目名称
		// hash，主要用于服务器缓存识别不同的项目时使用
		// 比如memcached，key为user.10，而这个项目的存储则应该是：$flag.user.10，来避免项目和项目之间的数据混串
		$hash = hash('crc32b', $summary);

		// 真正用于显示的项目名称，包含项目名称、环境、hash
		$this->name = sprintf('%s(%s:%s)', $this->name, KE_APP_ENV, $hash);

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
			$this->httpSecurityField = 'ke_http';

		if (empty($this->httpSecuritySessionField) || !is_string($this->httpSecuritySessionField))
			$this->httpSecuritySessionField = 'ke_security_reference';

		// http验证字段的加密混淆码
		if (empty($this->httpSecuritySalt) || !is_string($this->httpSecuritySalt))
			$this->httpSecuritySalt = "{$this->name}:{$this->httpSecurityField}";

		$this->httpSecuritySalt = $this->hash($this->httpSecuritySalt);

		define('KE_HTTP_SECURITY_FIELD', $this->httpSecurityField);
		define('KE_HTTP_SECURITY_SALT', $this->httpSecuritySalt);
		define('KE_HTTP_SECURITY_SESS_FIELD', $this->httpSecuritySessionField);
		// 敏感数据还是清空为妙
		$this->httpSecuritySalt = null;

		// 检查httpCharset
		if (empty($this->encoding) || false === @mb_encoding_aliases($this->encoding))
			$this->encoding = 'UTF-8';

		if (!empty($this->encodingOrder)) {
			if (is_string($this->encodingOrder))
				$this->encodingOrder = explode(',', $this->encodingOrder);
			if (is_array($this->encodingOrder)) {
				$list = ['ASCII'];
				foreach ($this->encodingOrder as $encoding) {
					$encoding = strtoupper(trim($encoding));
					if (empty($encoding) || $encoding === 'ASCII' || $encoding === $this->encoding)
						continue;
					$list[] = $encoding;
				}
				$list[] = $this->encoding;
				mb_detect_order($list);
			}
		}

		// 时区
		if (empty($this->timezone) || false === @date_default_timezone_set($this->timezone)) {
			$this->timezone = 'Asia/Shanghai';
			date_default_timezone_set($this->timezone);
		}

		define('KE_APP_TIMEZONE', $this->timezone);
		define('KE_APP_ENCODING', $this->encoding);

		// 系统的配置
		ini_set('default_charset', KE_APP_ENCODING);
		ini_set('default_mimetype', 'text/html');
		mb_internal_encoding(KE_APP_ENCODING);
		mb_http_output(KE_APP_ENCODING);

		$this->isInit = true;

		call_user_func([$this, 'on' . KE_APP_ENV]);

		register_shutdown_function(function () {
			$this->onExiting();
		});

		return $this;
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

	protected function onExiting()
	{
	}

	public function hash(string $content, string $salt = KE_APP_HASH): string
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
		if (isset($this->servers[$_SERVER['SERVER_NAME']]))
			return $this->servers[$_SERVER['SERVER_NAME']];
		return KE_PRODUCTION;
	}

	public function setDirs(array $dirs)
	{
		foreach ($dirs as $name => $dir) {
			if ($dir === null) {
				unset($this->dirs[$name]);
				continue;
			}
			if (($real = real_dir($dir)) !== false) {
				$this->dirs[$name] = $real;
			}
			else {
				$this->aliases[$name] = $dir;
			}
		}
		return $this;
	}

	public function path(string $name = null, string $path = null, string $ext = null)
	{
		$result = false;
		if (empty($name))
			$result = $this->root;
		elseif (isset($this->dirs[$name]))
			$result = $this->dirs[$name];
		else {
			$this->dirs[$name] =
			$result = $this->root . DS . (empty($this->aliases[$name]) ? $name : $this->aliases[$name]);
		}
		if (!empty($path)) {
			if (!empty($ext))
				$path = ext($path, $ext);
			$result .= DS . $path;
		}
		if (!KE_IS_WIN) {
			$result = str_replace('\\', '/', $result);
		}
		return $result;
	}

	public function __call(string $name, array $args)
	{
		return $this->path($name, ...$args);
	}

	public function getLoader()
	{
		return $this->loader;
	}
}
