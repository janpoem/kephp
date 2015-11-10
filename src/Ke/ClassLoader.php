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
 * 类加载器
 *
 * 一个类加载器，可以自动加载两个方面的类：
 * 1. 指定了类路径($classPaths)的，这部分可以动态添加。
 * 2. 基于$root + namespace/class.php自动构建而成。
 *
 * 一般来说，一个项目内一个类加载器的实例已经足够了。如果想加入外部源，则可以直接添加另外一个额外的实例即可
 *
 * @package Ke\Core
 */
class ClassLoader
{

	const SPR_NS = '\\';

	const LOADED = 1;

	const NOT_LOADED = 0;

	const UNDEFINED = -1;

	const PREPEND = true;

	const NOT_PREPEND = false;

	const START = true;

	const NOT_START = false;

	private static $loaders = [];

	private $dir = null;

	private $isMultiDir = false;

	private $ext = '.php';

	private $prepend = false;

	private $isStart = false;

	private $classPaths = [];

	private $status = [];

	/**
	 * @param null $name
	 * @return ClassLoader|mixed
	 * @throws \Exception
	 */
	public static function getLoader($name = null)
	{
		if (!isset(self::$loaders[$name]))
			throw new Exception('Undefined class loader {name}!', ['name' => $name]);
		return self::$loaders[$name];
	}

	public static function exists($name)
	{
		return isset(self::$loaders[$name]);
	}

	public static function addLoader($name, $loader)
	{
		if (isset(self::$loaders[$name]))
			throw new Exception('The class loader named {name} has been defined!', ['name' => $name]);
		if (!is_object($loader))
			throw new Exception('The class loader should be an object!');
		self::$loaders[$name] = $loader;
		return $loader;
	}

	/**
	 * @param string $name
	 * @param array  $options
	 * @return ClassLoader
	 * @throws Exception
	 */
	public static function newLoader($name, array $options)
	{
		if (isset(self::$loaders[$name]))
			throw new Exception('The class loader "{name}" has been defined.', ['name' => $name]);
		self::$loaders[$name] = new static($options);
		return self::$loaders[$name];
	}

	public function __construct(array $options)
	{
		$this->init($options);
	}

	protected function init(array $options)
	{
		if (isset($options['dir']))
			$this->setDir($options['dir']);
		if (isset($options['ext']))
			$this->setExt($options['ext']);
		$this->prepend = !empty($options['prepend']);
		if (isset($options['classes']) && is_array($options['classes']))
			$this->addClassPaths($options['classes']);
		if (isset($options['import']))
			$this->importClassPaths($options['import']);
		if (!empty($options['start']))
			$this->start();
	}

	public function setDir($dir)
	{
		if ($this->isStart)
			return $this;
		if (is_array($dir)) {
			$temp = [];
			foreach ($dir as $item) {
				if (is_dir($item))
					$temp[] = $item;
			}
			$count = count($temp);
			if ($count > 1) {
				$this->dir = $temp;
				$this->isMultiDir = true;
			} else {
				if (isset($temp[0]))
					$this->dir = $temp[0];
				$this->isMultiDir = false;
			}
		} elseif (is_dir($dir)) {
			$this->dir = $dir;
			$this->isMultiDir = false;
		}
		return $this;
	}

	public function getDir()
	{
		return $this->dir;
	}

	public function isMultiDir()
	{
		return $this->isMultiDir;
	}

	public function setExt($ext)
	{
		if ($this->isStart)
			return $this;
		if (empty($ext))
			$this->ext = '';
		else {
			if ($ext[0] !== '.')
				$ext = '.' . $ext;
			$this->ext = strtolower($ext);
		}
		return $this;
	}

	public function getExt()
	{
		return $this->ext;
	}

	public function start()
	{
		if (!$this->isStart) {
			$this->isStart = spl_autoload_register([$this, 'loadClass'], false, $this->prepend);
		}
		return $this;
	}

	public function stop()
	{
		if ($this->isStart) {
			if (spl_autoload_unregister([$this, 'loadClass']))
				$this->isStart = false;
		}
		return $this;
	}

	/**
	 * 添加Class路径，这里只能添加新的，而不能覆盖已经存在的Class路径
	 *
	 * @param array $paths
	 * @return $this
	 */
	public function addClassPaths(array $paths)
	{
		if (!empty($paths))
			$this->classPaths += $paths;
		return $this;
	}

	/**
	 * 通过加载文件的方式来添加Class路径，支持传入数组和字符两种格式
	 *
	 * <code>
	 * App::importClassPaths(APP_DIR . '/classes.php');
	 * App::importClassPaths([
	 *     APP_DIR . '/classes_1.php',
	 *     APP_DIR . '/classes_2.php',
	 *     APP_DIR . '/classes_3.php',
	 * ]);
	 * </code>
	 *
	 * 通过数组的方式，先依次加载文件，并将所有返回的Class路径拼接成一个大的数组，然后统一添加到classMaps
	 *
	 * @param string|array $file
	 * @return $this
	 */
	public function importClassPaths($file)
	{
		if (empty($file))
			return $this;
		$maps = [];
		if (is_array($file)) {
			foreach ($file as $item) {
				$part = import($item);
				if (!empty($part) && is_array($part))
					$maps += $part;
			}
		} elseif (is_string($file)) {
			$return = import($file);
			if (!empty($return) && is_array($return))
				$maps = $return;
		}
		if (!empty($maps)) {
			return $this->addClassPaths($maps);
		}
		return $this;
	}

	/**
	 * 返回所有已定义的Class路径映射
	 *
	 * @return array
	 */
	public function getClassPaths()
	{
		return $this->classPaths;
	}

	/**
	 * 取得Class的加载路径
	 *
	 * Class加载状态，只在loadClass进行判断，这里只生成一个Class的加载路径。
	 *
	 * @param string $class
	 * @return string
	 */
	public function getPath($class)
	{
		// 凡是要使用$class作为检索查询的，都要对$class的名称进行过滤
		if ($class[0] === self::SPR_NS)
			$class = trim($class, self::SPR_NS);
		if (isset($this->classPaths[$class]))
			return $this->classPaths[$class];
		// 处理一下路径
		$path = $class;
		if (!KE_IS_WIN)
			$path = strtr($path, self::SPR_NS, KE_DS_UNIX);
		if ($this->isMultiDir) {
			$paths = [];
			foreach ($this->dir as $dir) {
				$paths[] = $dir . DIRECTORY_SEPARATOR . $path . $this->ext;
			}
			return $paths;
		} else {
			return $this->dir . DIRECTORY_SEPARATOR . $path . $this->ext;
		}
	}

	/**
	 * 加载Class的接口实现
	 *
	 * @todo 这里还是需要检查一下，如果已经加载过的，就不需要再执行了
	 *
	 * @param string $class
	 */
	public function loadClass($class)
	{
		if ($class[0] === self::SPR_NS)
			$class = trim($class, self::SPR_NS);
		if (!isset($this->status[$class])) {
			$path = false;
			$isMulti = false;
			$status = self::NOT_LOADED;
			if (isset($this->classPaths[$class])) {
				$path = $this->classPaths[$class];
			} else {
				$isMulti = $this->isMultiDir;
				$path = $class;
				if (!KE_IS_WIN)
					$path = strtr($path, self::SPR_NS, KE_DS_UNIX);
				if ($isMulti === false)
					$path = $this->dir . DIRECTORY_SEPARATOR . $path . $this->ext;
			}

			if ($isMulti === false) {
				if (import($path) !== false)
					$status = self::LOADED;
			} else {
				foreach ($this->dir as $dir) {
					$path = $dir . DIRECTORY_SEPARATOR . $path . $this->ext;
					if (import($path) !== false) {
						$status = self::LOADED;
						break;
					}
				}
			}

			if ($status === self::LOADED) {
				if (class_exists($class, false)) {
					if (is_subclass_of($class, AutoLoadClassImpl::class))
						call_user_func([$class, 'onLoadClass'], $class, $path);
					// 这里预留一个空间，这里会加入一些接口的实现
				} elseif (trait_exists($class, false)) {

				} else {
					// 如果类不存在，将其记录下来，因为PSR的规范，loadClass不应该抛出异常或错误
					$status = self::UNDEFINED;
				}
			}

			$this->status[$class] = $status;
		}
	}

	public function getStatus($class)
	{
		// 凡是要使用$class作为检索查询的，都要对$class的名称进行过滤
		if ($class[0] === self::SPR_NS)
			$class = trim($class, self::SPR_NS);
		if (isset($this->status[$class])) {
			return $this->status[$class];
		}
		return self::NOT_LOADED;
	}

	public function getAllStatus()
	{
		return $this->status;
	}
}