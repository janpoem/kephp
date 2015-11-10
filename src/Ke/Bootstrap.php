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

/** APP启动引导的时间 */
defined('KE_BOOTSTRAP') || define('KE_BOOTSTRAP', microtime());

require __DIR__ . '/Base.php'; // 这个是必须的，这里是基础的函数
require __DIR__ . '/Interfaces.php'; // 这个是必须的，这里是基础的接口定义
// 加载必须的类，在Bootstrap流程中，不直接使用classLoader来加载，减少匹配的命中
require __DIR__ . '/ClassLoader.php';
require __DIR__ . '/App.php';
require __DIR__ . '/Uri.php';

/**
 * 引导类
 *
 * @package Ke\Core
 */
class Bootstrap
{

	const COMPOSER_LOADER = 'Composer\\Autoload\\ClassLoader';

	private static $isStart = false;

	/**
	 * 引导启动函数
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function start()
	{
		if (self::$isStart)
			return false;

		self::$isStart = true;

		/** 引入全局的$KE的配置 */
		global $_KE;

		if (ob_get_level() <= 0)
			ob_start();

		/** 应用程序的运行模式，cli模式或者Web模式 */
		define('KE_APP_MODE', PHP_SAPI === 'cli' ? KE_CLI_MODE : KE_WEB_MODE);

		/** 当前执行脚本的完整路径 */
		define('KE_SCRIPT_PATH', realpath($_SERVER['SCRIPT_FILENAME']));
		/** 当前执行脚本的目录路径 */
		define('KE_SCRIPT_DIR', dirname(KE_SCRIPT_PATH));
		/** 当前执行脚本的文件名*/
		define('KE_SCRIPT_FILE', basename(KE_SCRIPT_PATH));

		// 优先取得APP_PATH
		if (!empty($_KE['APP_PATH']) && false !== ($appPath = realpath($_KE['APP_PATH']))) {
			define('KE_APP', $appPath);
		} else {
			// 严格意义上做路径的彻底匹配，是很难的，KePHP希望确保以下两点：
			// 1. 入口文件（/public/index.php）尽可能的简洁的代码，或者是能够被一眼就能理解的代码。
			//    所以入口文件（/public/index.php），只暴露一个$_KE的全局变量，来进行调节和适配。
			//    AgiMVC老版本的通过常量的方式也放弃了，因为常量声明后就无法更改，如果常量中输入了异常值，更难以被调试发现。
			// 2. 保持约定俗成的项目目录结构，也即：
			//    web执行发起目录，一定是基于项目根目录下的public目录，如：/root/public
			//    cli执行发起目录，一定是基于项目根目录下，如：/root/ke.php
			define('KE_APP', KE_APP_MODE === KE_CLI_MODE ? KE_SCRIPT_DIR : dirname(KE_SCRIPT_DIR));
		}

		define('KE_APP_DIR', basename(KE_APP));

		foreach (['SRC' => 'src', 'WEB' => 'public', 'CONF' => 'config', 'COMPOSER' => 'vendor',] as $prefix => $dir) {
			$keyDir = $prefix . '_DIR';
			$keyPath = $prefix . '_PATH';
			$path = false;
			// 要指定具体的目录路径，必须这个目录是真实存在的路径。
			if (!empty($_KE[$keyPath]))
				$path = realpath($_KE[$keyPath]);
			if ($path === false) {
				$path = KE_APP . DS;
				if (!empty($_KE[$keyDir]) && !empty($_dir = trim($_KE[$keyDir], KE_PATH_NOISE))) {
					$path .= $_dir;
				} else {
					$path .= $dir;
				}
			}
			define('KE_' . $prefix, $path);
		}

		$appCls = 'App';
		if (!empty($_KE['APP_CLASS'])) {
			$_KE['APP_CLASS'] = trim($_KE['APP_CLASS'], KE_DS_CLASS);
			if (!empty($_KE['APP_CLASS']))
				$appCls = $_KE['APP_CLASS'];
		}

		$appNs = '';
		$appNsPath = KE_SRC;
		$appNsPos = strrpos($appCls, KE_DS_CLASS);
		if ($appNsPos !== false) {
			$appNs = substr($appCls, 0, $appNsPos);
			if (!empty($appNs)) {
				$appNsPath = $appNs;
				if (KE_IS_WIN)
					$appNsPath = str_replace('\\', '/', $appNsPath);
				$appNsPath .= DS . $appNsPath;
			}
		}

		define('KE_APP_CLASS', $appCls);
		define('KE_APP_NS', empty($appNs) ? false : $appNs);
		define('KE_APP_NS_PATH', empty($appNsPath) ? false : $appNsPath);

		$useKeLoader = true; // 默认加载AppLoader
		$composerLoader = false; // 默认不加载ComposerLoader

		// 已经定义了
		if (class_exists(self::COMPOSER_LOADER, false)) {
			foreach (spl_autoload_functions() as $item) {
				if (isset($item[0]) && is_object($item[0]) && get_class($item[0]) === self::COMPOSER_LOADER) {
					$composerLoader = $item[0];
					break;
				}
			}
		}

		if ($composerLoader === false && !empty($_KE['USE_COMPOSER_LOADER']))
			$composerLoader = import(KE_COMPOSER . '/autoload.php');

		if ($composerLoader !== false)
			ClassLoader::addLoader('composer', $composerLoader);

		if (isset($_KE['USE_KE_LOADER']) && empty($_KE['USE_KE_LOADER']))
			$useKeLoader = false;

		// 强制加载keLoader，而且优先级预设为最高
		if ($useKeLoader) {
			ClassLoader::newLoader('app', [
				'dir'     => KE_SRC,
				'prepend' => true,
				'classes' => import(dirname(__DIR__) . '/classes.php'),
				'start'   => true,
			]);
		}

		unset($_KE); // 这里后面就不再使用$_KE这个全局变量了，彻底清空他。

		// 从App::__construct()调出来，这些操作放在前面执行
		// CLI模式下的兼容
		if (KE_APP_MODE === KE_CLI_MODE) {
			// 先尝试加载环境配置文件，这个文件以后会扩展成为json格式，以装载更多的信息
			$envFile = KE_APP . '/env';
			if (is_file($envFile) && is_readable($envFile)) {
				$_SERVER['SERVER_NAME'] = trim(file_get_contents($envFile));
			}
		}

		// Uri预备
		Uri::prepare();

		// App实例化，加载各项配置
		App::getApp();

		return true;
	}
}

