<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */


// 全局的常量

/** APP启动引导的时间 */
defined('KE_BOOTSTRAP_TIME') || define('KE_BOOTSTRAP_TIME', microtime());

/** 当前执行脚本的完整路径 */
define('KE_SCRIPT_PATH', realpath($_SERVER['SCRIPT_FILENAME']));
/** 当前执行脚本的目录路径 */
define('KE_SCRIPT_DIR', dirname(KE_SCRIPT_PATH));
/** 当前执行脚本的文件名*/
define('KE_SCRIPT_FILE', basename(KE_SCRIPT_PATH));

/**
 * 类库的版本号
 */
const KE_VER = '1.0.0';

/**
 * 命令行模式
 */
const KE_CLI = 'cli';
/**
 * 网页模式
 */
const KE_WEB = 'web';

/** KE_APP_ENV:开发环境 */
const KE_DEV = 'development';
/** KE_APP_ENV:测试环境 */
const KE_TEST = 'test';
/** KE_APP_ENV:开发环境 */
const KE_PRO = 'production';

// PHP变量类型的字面值，即为gettype方法返回的结果
/** null类型 */
const KE_NULL = 'NULL';
/** 布尔类型 */
const KE_BOOL = 'boolean';
/** 整型类型 */
const KE_INT = 'integer';
/** 浮点 */
const KE_FLOAT = 'double';
/** 字符串类型 */
const KE_STR = 'string';
/** 数组类型 */
const KE_ARY = 'array';
/** 对象类型 */
const KE_OBJ = 'object';
/** 资源类型 */
const KE_RES = 'resource';

const DS = DIRECTORY_SEPARATOR;
/** 命名空间的分隔符 */
const KE_DS_CLS = '\\';
/** Windows系统的目录分隔符 */
const KE_DS_WIN = '\\';
/** Unix, Linux系统的目录分隔符 */
const KE_DS_UNIX = '/';

/** 是否WINDOWS环境 */
const KE_IS_WIN = DIRECTORY_SEPARATOR === KE_DS_WIN;

/** 路径名中的噪音值，主要用trim函数中 */
const KE_PATH_NOISE = '/\\ ';

// 以下常量，对应的是import方法专用的参数，用于说明import返回结果的内容
/** import返回原始的数据 */
const KE_IMPORT_RAW = 0b0;
/** import返回成功加载的文件名（并不转为realpath） */
const KE_IMPORT_PATH = 0b10;
/** import返回结果以数组形式返回，如果非数组，将强制转数组，多个文件时，为追加方式，即当出现重复key的时候，后来者不能覆盖前者 */
const KE_IMPORT_ARRAY = 0b100;
const KE_IMPORT_MERGE = 0b101;

const KE_IMPORT_CONTEXT = 0b1000;

// 必须的函数

if (!function_exists('import')) {
	/**
	 * 无副作用加载文件
	 *
	 * @param string|array $_path
	 * @param array        $_vars
	 * @param int          $_mode
	 * @param array        &$_result
	 * @return bool|array|mixed
	 */
	function import($_path, array $_vars = null, int $_mode = KE_IMPORT_RAW, array &$_result = null)
	{
		if (empty($_path))
			return false;
		$_modeArray = ($_mode & KE_IMPORT_ARRAY) === KE_IMPORT_ARRAY;
		if (is_array($_path)) {
			$_result = [];
			if (!empty($_vars)) {
				$_extractMode = EXTR_SKIP;
				if (($_mode & KE_IMPORT_CONTEXT) === KE_IMPORT_CONTEXT)
					$_extractMode = EXTR_SKIP | EXTR_REFS;
				extract($_vars, $_extractMode);
			}
			foreach ($_path as $_index => $_item) {
				$_return = false;
				$_isImport = false;
				if (!empty($_item) && is_file($_item) && is_readable($_item)) {
					$_return = require $_item;
					$_isImport = true;
				}
				if ($_isImport) {
					if ($_modeArray) {
						if (is_object($_return))
							$_return = (array)$_return;
						if (!empty($_return) && is_array($_return)) {
							if ($_mode === KE_IMPORT_MERGE)
								$_result = $_return + $_result;
							else
								$_result += $_return;
						}
						// 其他的字符串、布尔、数值无法转化为等量的array，就放弃不管了。
					}
					elseif ($_mode === KE_IMPORT_PATH)
						$_result[] = $_item;
				}
				if ($_mode === KE_IMPORT_RAW)
					$_result[$_index] = $_return;
			}
			return $_result;
		}
		else {
			$_return = false;
			$_isImport = false;
			if (!empty($_vars)) {
				// 上下文模式，在单个文件里面，是没效果的，只在多个文件中有意义
				extract($_vars, EXTR_SKIP);
			}
			if (is_file($_path) && is_readable($_path)) {
				$_return = require $_path;
				$_isImport = true;
			}
			// 强转数组的格式，必须是成功加载的时候，才进行转换
			if ($_isImport) {
				if ($_modeArray) {
					if (is_array($_return))
						return $_return;
					elseif (is_object($_return))
						return (array)$_return;
					return [];
				}
				elseif ($_mode === KE_IMPORT_PATH)
					return $_path;
			}
			return $_return;
		}
	}
}

if (!function_exists('parse_class')) {
	/**
	 * 解析className，解析出namespace前缀和className
	 *
	 * @param string $class
	 * @return array
	 */
	function parse_class(string $class): array
	{
		$class = trim($class, KE_PATH_NOISE);
		if (empty($class))
			return [null, null];
		if (($pos = strrpos($class, '\\')) !== false) {
			return [substr($class, 0, $pos), substr($class, $pos + 1)];
		}
		return [null, $class];
	}
}

if (!function_exists('error_name')) {
	/**
	 * 根据php的错误类型的整型值，转为字符输出
	 *
	 * @param int $code
	 * @return string
	 */
	function error_name(int $code): string
	{
		static $codes;
		if (!isset($codes)) {
			$codes = [
				0                   => 'Unknown',
				E_ERROR             => 'Error',
				E_WARNING           => 'Warn',
				E_PARSE             => 'Parse Error',
				E_NOTICE            => 'Notice',
				E_CORE_ERROR        => 'Error(Core)',
				E_CORE_WARNING      => 'Warn(Core)',
				E_COMPILE_ERROR     => 'Error(Compile)',
				E_COMPILE_WARNING   => 'Warn(Compile)',
				E_USER_ERROR        => 'Error(User)',
				E_USER_WARNING      => 'Warn(User)',
				E_USER_NOTICE       => 'Notice(User)',
				E_STRICT            => 'Strict',
				E_RECOVERABLE_ERROR => 'Error',
			];
		}
		return isset($codes[$code]) ? $codes[$code] : $codes[0];
	}
}

if (!function_exists('depth_query')) {
	/**
	 * 深入查询$data
	 * <code>
	 * $data = array(
	 *     'level1' => array(
	 *         'level2' => array(
	 *             0, 1, 2, 3, 4, 5
	 *         );
	 *     ),
	 * );
	 * depthQuery($data, 'level1->level2'); // return array(0,1,2,3,4,5)
	 * depthQuery($data, 'level1->level2->0'); // return 0
	 * depthQuery($data, 'not_exist', 'test'); // return 'test'
	 * </code>
	 * $keys可以为数组格式，如：array('level1', 'level2');
	 *
	 * @param array|object $data    数据源
	 * @param string|array $keys    查询的keys，字符串格式为：`'key1->key2->0'`，数组格式：`array('key1', 'key2', 0)`
	 * @param mixed        $default 默认值，当查询的keys的值不存在时，返回该默认值。
	 * @param string       $keysSpr
	 * @return mixed
	 */
	function depth_query($data, $keys, $default = null, string $keysSpr = '->')
	{
		if (empty($keys))
			return $data;
		$keysType = gettype($keys);
		if ($keysType === KE_STR) {
			if (strpos($keys, $keysSpr) !== false) {
				$keys = explode($keysSpr, $keys);
				$keysType = KE_ARY;
			}
			else {
				// Janpoem 2014.09.21
				// 调整了一些，原来只是检查isset，现在增加empty的判断
				// 需要做更长时间的监控，是否有副作用
				if (is_array($data))
					return !isset($data[$keys]) || ($data[$keys] != 0 && empty($data[$keys])) ? $default : $data[$keys];

				elseif (is_object($data))
					return !isset($data->{$keys}) ||
					       ($data[$keys] != 0 && empty($data->{$keys})) ? $default : $data->{$keys};
				else
					return $default;
			}
		}
		if (!empty($data) || (!empty($keys) && $keysType === KE_ARY)) {
			foreach ($keys as $key) {
				if (!is_numeric($key) && empty($key)) continue;
				// 每次循环，一旦没有$key，就退出
				// 不是array的話也到頭了
				// Janpoem 2014.09.21
				// 调整了一些，原来只是检查isset，现在增加empty的判断
				// 需要做更长时间的监控，是否有副作用
				if (is_array($data)) {
					if (!isset($data[$key]) || ($data[$key] != 0 && empty($data[$key])))
						return $default;
					else
						$data = $data[$key];
				}
				elseif (is_object($data)) {
					if (!isset($data->{$key}) || ($data->{$key} != 0 && empty($data->{$key})))
						return $default;
					else
						$data = $data->{$key};
				}
			}
			return $data;
		}
		else
			return $default;
	}
}

if (!function_exists('equals')) {
	/**
	 * 值内容是否相等
	 * 动态类型语言的值类型检查真的蛋疼
	 *
	 * @param mixed $old
	 * @param mixed $new
	 * @return bool 是否相等
	 */
	function equals($old, $new): bool
	{
		if ($old === $new)
			return true;
		$oldType = gettype($old);
		$newType = gettype($new);
		if ($oldType !== KE_ARY && $oldType !== KE_OBJ && $oldType !== KE_RES &&
		    $newType !== KE_ARY && $newType !== KE_OBJ && $newType !== KE_RES
		) {
			if ($old === true) $old = 1;
			elseif ($old === false) $old = 0;
			if ($new === true) $new = 1;
			elseif ($new === false) $new = 0;
			return strval($old) === strval($new);
		}
		else {
			return $old === $new;
		}
	}
}


if (!function_exists('diff_micro')) {
	/**
	 * 比较两个时间戳的差值，返回结果单位为微秒
	 *
	 * @param string      $start
	 * @param null|string $end
	 * @return float
	 */
	function diff_micro(string $start, string $end = null): float
	{
		list($startUS, $startMS) = explode(' ', $start);
		if (empty($end))
			$end = microtime();
		list($endUS, $endMS) = explode(' ', $end);
		return ((float)$endUS + (float)$endMS) - ((float)$startUS + (float)$startMS);
	}
}

if (!function_exists('diff_milli')) {
	/**
	 * 比较两个时间戳的差值，返回结果单位为毫秒
	 *
	 * @param string      $start
	 * @param null|string $end
	 * @return float
	 */
	function diff_milli(string $start, string $end = null): float
	{
		return diff_micro($start, $end) * 1000;
	}
}

if (!function_exists('substitute')) {
	/**
	 * 字符串替换函数，命名源自mootools的String.substitute
	 *
	 * 原本作为Utils包里面的函数，现在将他提取到Common中。
	 *
	 * @param string $str
	 * @param array  $args
	 * @param string $regex
	 * @param array  $matches
	 * @return string
	 */
	function substitute(string $str, array $args = [], array & $matches = [], $regex = '#\{([^\{\}\r\n]+)\}#'): string
	{
		if (empty($str))
			return '';
		if (empty($args)) // 没有参数，就表示无需替换了
			return $str;
		if (empty($regex))
			$regex = '#\{([^\{\}\r\n]+)\}#';
		if (preg_match($regex, $str)) {
			$str = preg_replace_callback($regex, function ($m) use ($args, & $matches) {
				$key = $m[1];
				$matches[$key] = ''; // give a default empty string
				if (isset($args[$key]) || isset($args->$key)) {
					$matches[$key] = $args[$key];
				}
				else {
					$matches[$key] = depth_query($args, $key, '');
				}
				return $matches[$key];
			}, $str);
			return substitute($str, $args, $matches, $regex);
		}
		return $str;
	}
}

global $KE;

if (!function_exists('ext')) {
	function ext(string $path, string $ext): string
	{
		if (!empty($path) && !empty($ext)) {
			if ($ext[0] !== '.')
				$ext = '.' . $ext;
			// 大小写有差异，应确保所有文件后缀应为小写
			if (strcasecmp(strrchr($path, '.'), $ext) !== 0)
				$path = $path . strtolower($ext);
		}
		return $path;
	}
}

if (!function_exists('real_path')) {
	function real_path(string $path)
	{
		global $KE;
		$paths = &$KE['paths'];
		if (!isset($paths[$path]))
			$paths[$path] = realpath($path);
		return $paths[$path];
	}
}

if (!function_exists('real_dir')) {
	function real_dir(string $path)
	{
		global $KE;
		$realPath = $KE['paths'][$path] ?? real_path($path);
		if (!isset($KE['stats'][$realPath][0]))
			$KE['stats'][$realPath][0] = $realPath === false ? false : is_dir($realPath);
		return $KE['stats'][$realPath][0] ? $realPath : false;
	}
}

if (!function_exists('real_file')) {
	function real_file(string $path)
	{
		global $KE;
		$realPath = $KE['paths'][$path] ?? real_path($path);
		if (!isset($KE['stats'][$realPath][1]))
			$KE['stats'][$realPath][1] = $realPath === false ? false : is_file($realPath);
		return $KE['stats'][$realPath][1] ? $realPath : false;
	}
}

if (!function_exists('parse_path')) {
	/**
	 * 解析一个路径名，拆分出目录、文件名、后缀名
	 *
	 * 如果路径不包含相应部分的数据，那个值会为null，后缀名会强制转为小写
	 *
	 * <code>
	 * parse_path('index.html'); // [null, 'index', 'html']
	 * parse_path('/hello/world/jan.txt'); // ['/hello/world', 'jan', 'txt']
	 * // 如果以路径名为末尾，则默认认为这个表示的是一个目录
	 * parse_path('good/'); // ['good', null, null]
	 * </code>
	 *
	 * @param string $path
	 * @return array 返回数据格式：[目录, 文件名, 后缀名]
	 */
	function parse_path(string $path, bool $parseFormat = true): array
	{
		$return = [null, null];
		if (!empty($path)) {
			if (preg_match('#^(?:(.*)[\/\\\\])?([^\/\\\\]+)?$#', $path, $matches)) {
				if (!empty($matches[1])) {
					$return[0] = rtrim($matches[1], KE_PATH_NOISE);
				}
				if (!empty($matches[2])) {
					if ($parseFormat && ($pos = strrpos($matches[2], '.')) > 0) {
						$return[1] = substr($matches[2], 0, $pos);
						$return[2] = strtolower(substr($matches[2], $pos + 1));
					}
					else {
						$return[1] = $matches[2];
					}
				}
			}
		}
		return $return;
	}
}

if (!function_exists('compare_path')) {
	/**
	 * 比较两个路径，返回相同的部分
	 * 必须确保两个传入的路径都是被净化处理过的路径名，不包含类如/../，并且请确保传入的路径都有一致的目录分隔符。
	 * 本函数不会自动调用purge的函数，请调用前自己执行
	 * <code>
	 * compare_path('/aa/bb/cc', '/aa/bb/dd'); // => aa/bb
	 * </code>
	 * 这个函数还可以用于挑出两个字符串相同的部分
	 * <code>
	 * compare_path('ab-cd-ef-gh-ij', 'ab-cd-ef-gh-abc', '-'); // => ab-cd-ef-gh
	 * </code>
	 *
	 * @param string      $source
	 * @param string      $target
	 * @param string      $delimiter
	 * @param string|null $prefix
	 * @return string
	 */
	function compare_path(string $source, string $target, string $delimiter = KE_DS_UNIX, string $prefix = null): string
	{
		if (empty($source) || empty($target))
			return false;
		if (empty($delimiter))
			$delimiter = KE_DS_UNIX;
		$source = trim($source, KE_PATH_NOISE);
		$target = trim($target, KE_PATH_NOISE);
		$result = [];
		$splitSource = explode($delimiter, $source);
		$splitTarget = explode($delimiter, $target);
		if (!empty($splitSource) && !empty($splitTarget)) {
			foreach ($splitSource as $index => $str) {
				if (!isset($splitSource[$index]) ||
				    !isset($splitTarget[$index]) ||
				    strcasecmp($splitSource[$index], $splitTarget[$index]) !== 0
				) {
					break;
				}
				$result[] = $str;
			}
		}
		if (!empty($result))
			return $prefix . implode($delimiter, $result);
		return false;
	}
}


/** 点（./../）删除处理 */
const KE_PATH_DOT_REMOVE = 0b00;
/** 点（./../）转为正确的路径的处理 */
const KE_PATH_DOT_NORMALIZE = 0b01;
/** 保持点（./../），不做任何处理 */
const KE_PATH_DOT_KEEP = 0b10;
/** 最开头的路径分隔符强制清除 */
const KE_PATH_LEFT_TRIM = 0b0000;
/** 最开头的路径分隔符强制保留（如果没有会自动补充） */
const KE_PATH_LEFT_REMAIN = 0b0100;
/** 最开头的路径分隔符维持原样 */
const KE_PATH_LEFT_NATIVE = 0b1000;

if (!function_exists('purge_path')) {
	function purge_path(string $path, int $mode = 0, string $spr = DS, $noise = null): string
	{
		// 路径左边的处理模式
		$left = ($mode >> 2) << 2;
		// 路径的.处理模式
		$dot = $mode ^ $left;
		// 过滤$spr，基于spr来确定noise
		if (empty($spr))
			$spr = DS;
		elseif ($spr !== DS) {
			$len = mb_strlen($spr); // 这里要用mb来判断，因为可能输出的非ascii字符
			if ($len <= 0)
				$spr = DS;
			elseif ($len > 1)
				$spr = mb_substr($spr, 0, 1);
		}
		// 这里只能针对特定的情况下，补充noise
		if (empty($noise)) {
			if ($spr === KE_DS_WIN)
				$noise = KE_DS_UNIX;
			elseif ($spr === KE_DS_UNIX)
				$noise = KE_DS_WIN;
		}
		// 噪音不为空，则先将路径值中的噪音去掉
		if (!empty($noise))
			$path = str_replace($noise, $spr, $path);

		$isWinPath = false;
		$isAbsPath = false;
		$head = null;
		if ($isWinPath = preg_match('#^([a-z]\:)[\/\\\\]#i', $path, $matches)) {
			$size = strlen($matches[0]);
			$head = substr($path, 0, $size);
			$path = substr($path, $size);
			$path = trim($path, KE_PATH_NOISE);
			$isAbsPath = true; // 符合windows风格的路径名，必然是绝对路径
		}
		else {
			if (isset($path[0]) && $path[0] === $spr)
				$isAbsPath = true;
			$path = trim($path, KE_PATH_NOISE);
		}

		if (!empty($path) && $path !== $spr) {
			$path = urldecode($path);
			$sprQuote = preg_quote($spr);
			if ($dot === KE_PATH_DOT_NORMALIZE) {
				$split = explode($spr, $path);
				$temp = [];
				foreach ($split as $index => $part) {
					if ($part === '.' || $part === $spr || empty($part))
						continue;
					elseif ($part === '..') {
						array_pop($temp);
						continue;
					}
					else {
						$temp[] = $part;
					}
				}
				$path = implode($spr, $temp);
			}
			elseif ($dot === KE_PATH_DOT_REMOVE) {
				if (preg_match('#(\.{1,}[' . $sprQuote . ']{1,})#', $path))
					$path = preg_replace('#(\.{1,}[' . $sprQuote . ']{1,})#', $spr, $path);
				$path = preg_replace('#[' . $sprQuote . ']+#', $spr, $path);
			}
			else {
				$path = preg_replace('#[' . $sprQuote . ']+#', $spr, $path);
			}
		}

		if ($isWinPath) {
			// windows的路径风格，就忽略$left的设置了
			$path = $head . $path;
		}
		else {
			if ($left === KE_PATH_LEFT_NATIVE) {
				if ($isAbsPath && $path[0] !== $spr)
					$path = $spr . $path;
			}
			elseif ($left === KE_PATH_LEFT_TRIM) {
				if (!empty($path) && $path[0] === $spr)
					$path = ltrim($path, $spr);
			}
			else {
				if (empty($path))
					$path = $spr;
				elseif ($path[0] !== $spr)
					$path = $spr . $path;
			}
		}

		return $path;
	}
}

if (!function_exists('predir')) {
	/**
	 * 预先创建指定路径的目录。
	 *
	 * 常用于保存文件前检查文件的路径的目录是否存在。
	 *
	 * <code>
	 * $savePath = '/var/www/myapp/log/abc.log';
	 * file_put_contents(predir($savePath), 'anything...');
	 * </code>
	 *
	 * @param string $path
	 * @param int    $mode
	 * @return string
	 */
	function predir(string $path, $mode = 0755)
	{
		$dir = dirname($path);
		if (!empty($dir) && $dir !== '.' && $dir !== '/' && $dir !== '\\' && !is_dir($dir))
			mkdir($dir, $mode, true);
		return $path;
	}
}

