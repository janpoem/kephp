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
/** KePHP类库的根路径 */
define('KE_LIB', dirname(dirname(__DIR__)));


// 加载必须的类
require __DIR__ . '/Base.php';
require __DIR__ . '/Interfaces.php';
require __DIR__ . '/ClassLoader.php';

/**
 * 引导类
 *
 * @package Ke\Core
 */
class Bootstrap
{

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

		/** 当前执行脚本的完整路径 */
		define('KE_SCRIPT_PATH', realpath($_SERVER['SCRIPT_FILENAME']));
		/** 当前执行脚本的目录路径 */
		define('KE_SCRIPT_DIR', dirname(KE_SCRIPT_PATH));
		/** 当前执行脚本的文件名*/
		define('KE_SCRIPT_FILE', basename(KE_SCRIPT_PATH));

		/** 应用程序的运行模式，cli模式或者Web模式 */
		define('KE_APP_MODE', PHP_SAPI === 'cli' ? KE_CLI : KE_WEB);

		// 优先取得APP_PATH
		if (!empty($_KE['APP']) && false !== ($appPath = realpath($_KE['APP']))) {
			define('KE_APP_ROOT', $appPath);
		} else {
			define('KE_APP_ROOT', dirname(KE_SCRIPT_DIR));
		}

		define('KE_APP_DIR', basename(KE_APP_ROOT));

		foreach (['SRC' => 'src', 'WEB' => 'public', 'CONF' => 'config', 'COMPOSER' => 'vendor',] as $prefix => $dir) {
			$keyDir = $prefix . '_DIR';
			$keyPath = $prefix . '_PATH';
			$path = false;
			if (!empty($_KE[$keyPath]))
				$path = realpath($_KE[$keyPath]);
			if ($path === false) {
				if (!empty($_KE[$keyDir]) && !empty($_dir = trim($_KE[$keyDir], KE_PATH_NOISE))) {
					$path = KE_APP_ROOT . DIRECTORY_SEPARATOR . $_dir;
				} else {
					$path = KE_APP_ROOT . DIRECTORY_SEPARATOR . $dir;
				}
				define('KE_APP_' . $prefix, $path);
			}
		}

		$appCls = 'App';
		if (!empty($_KE['APP_CLASS'])) {
			$_KE['APP_CLASS'] = trim($_KE['APP_CLASS'], KE_DS_CLASS);
			if (!empty($_KE['APP_CLASS']))
				$appCls = $_KE['APP_CLASS'];
		}

		$appNs = '';
		$appNsPos = strrpos($appCls, KE_DS_CLASS);
		$appNsPath = '';
		if ($appNsPos !== false) {
			$appNs = substr($appCls, 0, $appNsPos);
			$appNsPath = $appNs;
			if (!KE_IS_WIN)
				$appNsPath = str_replace('\\', '/', $appNs);
		}

		define('KE_APP_CLASS', $appCls);
		define('KE_APP_NS', empty($appNs) ? false : $appNs);
		// @todo 此常量需要监督是否有实际作用，如无，后续应该删除。
		define('KE_APP_NS_PATH', empty($appNsPath) ? KE_APP_SRC : KE_APP_SRC . DIRECTORY_SEPARATOR . $appNsPath);

		// 默认没有加载这个ComposerLoader
		$composerLoader = false;
		if (isset($_KE['COMPOSER_AUTOLOAD']) && !empty($_KE['COMPOSER_AUTOLOAD'])) {
			$composerLoader = import(KE_APP_COMPOSER . '/autoload.php');
			if ($composerLoader !== false)
				ClassLoader::addLoader('composer', $composerLoader);
		}

		// 加载了composer的loader，且仍强调需要APP_AUTOLOAD
		if ($composerLoader === false || !empty($_KE['APP_AUTOLOAD'])) {
			ClassLoader::newLoader('app', [
				'dir'     => KE_APP_SRC,
				'prepend' => true,
				'classes' => import(KE_LIB . '/classes.php'),
				'start'   => true,
			]);
		}

		unset($_KE);

		App::getApp();

		return true;
	}
}

