<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

///////////////////////////////////////////////////////////////////////////////
// Common.php#1 常量定义
///////////////////////////////////////////////////////////////////////////////

const DS = DIRECTORY_SEPARATOR;

const KE_VERSION = '1.0.0';

/** KE_APP_ENV:开发环境 */
const KE_DEVELOPMENT = 'development';
/** KE_APP_ENV:测试环境 */
const KE_TEST = 'test';
/** KE_APP_ENV:开发环境 */
const KE_PRODUCTION = 'production';
/** KE_APP_MODE:WEB模式 */
const KE_WEB_MODE = 'web';
/** KE_APP_MODE:CLI模式 */
const KE_CLI_MODE = 'cli';

// PHP变量类型的字面值，即为gettype方法返回的结果
/** 字符串类型 */
const KE_STR = 'string';
/** 数组类型 */
const KE_ARY = 'array';
/** 对象类型 */
const KE_OBJ = 'object';
/** 资源类型 */
const KE_RES = 'resource';
/** 整型类型 */
const KE_INT = 'integer';
/** 浮点 */
const KE_FLOAT = 'double';
/** 布尔类型 */
const KE_BOOL = 'boolean';
/** null类型 */
const KE_NULL = 'NULL';
/** 命名空间的分隔符 */
const KE_DS_CLASS = '\\';
/** Windows系统的目录分隔符 */
const KE_DS_WIN = '\\';
/** Unix, Linux系统的目录分隔符 */
const KE_DS_UNIX = '/';
/** 深入查询数据的查询字符串 */
defined('KE_DEPTH_QUERY') || define('KE_DEPTH_QUERY', '->');
/** 是否WINDOWS环境 */
define('KE_IS_WIN', DIRECTORY_SEPARATOR === KE_DS_WIN);
/** 路径名中的噪音值，主要用trim函数中 */
const KE_PATH_NOISE = '/\\.';
/** pathPurge函数，点（./../）删除处理 */
const KE_PATH_DOT_REMOVE = 0;
/** pathPurge函数，点（./../）转为正确的路径的处理 */
const KE_PATH_DOT_NORMALIZE = 1;
/** pathPurge函数，保持点（./../）不做任何处理 */
const KE_PATH_DOT_KEEP = -1;
/** pathPurge函数，最开头的路径分隔符强制清除 */
const KE_PATH_LEFT_TRIM = 0;
/** pathPurge函数，最开头的路径分隔符强制保留（如果没有会自动补充） */
const KE_PATH_LEFT_REMAIN = 1;
/** pathPurge函数，最开头的路径分隔符维持原样 */
const KE_PATH_LEFT_NATIVE = -1;

///////////////////////////////////////////////////////////////////////////////
// Common.php#2 公共函数
///////////////////////////////////////////////////////////////////////////////

/**
 * 补完一个路径的后缀文件名
 * <code>
 * // a.b.c.txt
 * ext('a.b.c', 'txt');
 * // a.b.c.txt
 * ext('a.b.c', '.txt');
 * </code>
 *
 * @param string $path 路径名、或文件名
 * @param string $ext 后缀名
 * @return string
 */
function ext($path, $ext)
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

/**
 * 净化一个路径名
 *
 * @param string $path 路径名
 * @param int $dot 点（./../）的处理方式，`KE_PATH_DOT_REMOVE|KE_PATH_DOT_NORMALIZE|KE_PATH_DOT_KEEP`
 * @param int $left 最左边的路径分隔符的处理方式，`KE_PATH_LEFT_TRIM|KE_PATH_LEFT_REMAIN|KE_PATH_LEFT_NATIVE`
 * @param string $spr 分隔符，如果为多个字符，则只会取出第一个字符，比如：`abc => a`，`中国 => 中`
 * @param null|string|array $noise 路径名需要替换为$spr的噪音值
 * @return mixed|string
 */
function purgePath(
	$path,
	$dot = KE_PATH_DOT_REMOVE,
	$left = KE_PATH_LEFT_NATIVE,
	$spr = KE_DS_UNIX,
	$noise = null
)
{
	// 过滤$spr，基于spr来确定noise
	if ($spr !== DIRECTORY_SEPARATOR) {
		$spr = (string)$spr;
		$len = mb_strlen($spr); // 这里要用mb来判断，因为可能输出的非ascii字符
		if ($len <= 0)
			$spr = DIRECTORY_SEPARATOR;
		elseif ($len > 1)
			$spr = mb_substr($spr, 0, 1);
	}
	if (empty($noise)) {
		// 这里只能针对特定的情况下，补充noise
		if ($spr === KE_DS_WIN)
			$noise = KE_DS_UNIX;
		elseif ($spr === KE_DS_UNIX)
			$noise = KE_DS_WIN;
	}
	// path过滤一下
	$path = (string)$path; // 强制转一次字符
	// 噪音不为空，则先将路径值中的噪音去掉
	if (!empty($noise))
		$path = str_replace($noise, $spr, $path);

	$sprQuote = preg_quote($spr);
	// 判断一下他有没有左边的slash
	$hasLeftSlash = $path[0] === $spr;
	// 是不是windows的路径风格
	$isWinStyle = false;
	$head = null;
	if ($isWinStyle = preg_match('#^([a-z]\:)[\/\\\\]#i', $path, $matches)) {
		$size = strlen($matches[0]);
		$head = substr($path, 0, $size);
		$path = substr($path, $size);
	}

	if ($path !== $spr && mb_strlen($path) > 0) {
		$path = trim($path, $spr);
		if ($dot === KE_PATH_DOT_NORMALIZE) {
			$split = explode($spr, $path);
			$temp = [];
			foreach ($split as $index => $part) {
				if ($part === '.')
					continue;
				elseif ($part === '..') {
					array_pop($temp);
					continue;
				} else {
					$path = trim($part, '.'); // ..abc.. => abc
					if (empty($path)) // ... => ''
						continue;
					$temp[] = $path;
				}
			}
			$path = implode($spr, $temp);
		} elseif ($dot === KE_PATH_DOT_REMOVE) {
			if (preg_match('#(\.{1,}[' . $sprQuote . ']{1,})#', $path))
				$path = preg_replace('#(\.{1,}[' . $sprQuote . ']{1,})#', $spr, $path);
			$path = preg_replace('#[' . $sprQuote . ']+#', $spr, $path);
		} else {
			$quote = preg_quote($spr);
			$path = preg_replace('#[' . $sprQuote . ']+#', $spr, $path);
		}
	}

	if ($isWinStyle) {
		// windows的路径风格，就忽略$left的设置了
		$path = $head . $path;
	} else {
		if ($left === KE_PATH_LEFT_NATIVE) {
			if ($hasLeftSlash && $path[0] !== $spr)
				$path = $spr . $path;
		} elseif ($left === KE_PATH_LEFT_TRIM) {
			if ($path[0] === $spr)
				$path = ltrim($path, $spr);
		} else {
			if ($path[0] !== $spr)
				$path = $spr . $path;
		}
	}
//		if ($path !== $spr && !preg_match('#\.[a-z0-9]+$#', $path)) {
//			$path .= $spr;
//		}
	return $path;
}

/**
 * 比较两个路径，返回相同的部分
 * 必须确保两个传入的路径都是被净化处理过的路径名，不包含类如/../，并且请确保传入的路径都有一致的目录分隔符。
 * 本函数不会自动调用purge的函数，请调用前自己执行
 * <code>
 * Path::compare('/aa/bb/cc', '/aa/bb/dd'); // => aa/bb
 * </code>
 * 这个函数还可以用于挑出两个字符串相同的部分
 * <code>
 * Path::compare('ab-cd-ef-gh-ij', 'ab-cd-ef-gh-abc', null, '-'); // => ab-cd-ef-gh
 * </code>
 *
 * @param string $source
 * @param string $target
 * @param null|string $prefix
 * @param string $spr
 * @return bool|string
 */
function comparePath($source, $target, $prefix = null, $spr = KE_DS_UNIX)
{
	if (empty($source) || empty($target))
		return false;
	if (empty($spr))
		$spr = KE_DS_UNIX;
	$source = trim($source, $spr);
	$target = trim($target, $spr);
	$result = [];
	$splitSource = explode($spr, $source);
	$splitTarget = explode($spr, $target);
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
		return $prefix . implode($spr, $result);
	return false;
}

/**
 * 拼接完整的路径名，支持使用数组格式，批量完成拼接
 *
 * <code>
 * // /var/log/test
 * Path::join('/var/log', 'test');
 * // [ '/var/log/a', '/var/log/b', '/var/log/c', '/var/log/d' ]
 * Path::join('/var/log', ['a', 'b', 'c', 'd'])
 * </code>
 *
 * 为了保证对phar的支持，KePHP默认的目录分隔符为/，本函数会对分隔符做统一过滤，如：
 * <code>
 * // C:\test
 * Path::join('C:\', 'test', Path::SPR_WIN_DIR);
 * // [ 'C:\a', 'C:\b', 'C:\c', 'C:\d' ]
 * Path::join('C:\', ['a', 'b', 'c', 'd'], Path::SPR_WIN_DIR);
 * </code>
 *
 * @param string $prefix 前缀目录
 * @param null|string|array $path 后续要拼接的目录
 * @param string $spr 目录的分隔符
 * @param string|array $noise 路径名需要替换为$spr的噪音值
 * @return string|array
 */
function joinPath($prefix, $path = null, $spr = DIRECTORY_SEPARATOR, $noise = null)
{
	$result = [];
	if (!empty($prefix))
		$prefix = rtrim($prefix, KE_PATH_NOISE);
	if (!empty($prefix))
		$result[] = $prefix;
	if (!empty($path)) {
		$type = gettype($path);
		if ($type === KE_STR) {
			$path = ltrim($path, KE_PATH_NOISE);
			if (!empty($path))
				$result[] = $path;
		} elseif ($type === KE_ARY) {
			foreach ($path as & $item) {
				$item = joinPath($prefix, $item, $spr);
			}
			return $path;
		}
	}
	if (empty($spr))
		$spr = DIRECTORY_SEPARATOR;
	if (empty($result))
		return '';
	$result = implode($spr, $result);
	// 排除结果中的符号
	if (!isset($noise)) {
		// 如果是UNIX风格，则排除win
		if ($spr === KE_DS_UNIX)
			$noise = KE_DS_WIN;
		elseif ($spr === KE_DS_WIN)
			$noise = KE_DS_UNIX;
	}
	// 排除的路径分隔符
	if (!empty($noise)) {
		$result = str_replace($noise, $spr, $result);
	}
	return $result;
}

/**
 * 安全的加载
 *
 * 如果将import方法作为类方法，调用Class::import()或Object->import()，
 * 将获得这个类的全部访问范围，包括私有静态属性、私有属性、私有方法等。这是很不安全的做法。
 *
 * @param string $path
 * @return bool|mixed
 */
function import($path)
{
	if (is_file($path) && is_readable($path))
		return require $path;
	return false;
}

/**
 * 加载项目配置，这个函数类似import，但是需要传入一个$app实例，用于朝加载的配置文件暴露$app对象。
 *
 * @param string $path
 * @param \Ke\App $app
 * @return bool|mixed
 */
function importWithApp($path, \Ke\App $app)
{
	if (is_file($path) && is_readable($path))
		return require $path;
	return false;
}

function importWithVars($_path, array $_vars = [])
{
	if (is_file($_path) && is_readable($_path)) {
		extract($_vars);
		return require $_path;
	}
	return false;
}

/**
 * 比较两个时间戳的差值，返回结果单位为微秒
 *
 * @param string $start
 * @param null|string $end
 * @return float
 */
function diffMicro($start, $end = null)
{
	list($startUS, $startMS) = explode(' ', $start);
	if (empty($end))
		$end = microtime();
	list($endUS, $endMS) = explode(' ', $end);
	return ((float)$endUS + (float)$endMS) - ((float)$startUS + (float)$startMS);
}

/**
 * 比较两个时间戳的差值，返回结果单位为毫秒
 *
 * @param string $start
 * @param null|string $end
 * @return float
 */
function diffMilli($start, $end = null)
{
	return diffMicro($start, $end) * 1000;
}

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
 * @param array|object $data 数据源
 * @param string|array $keys 查询的keys，字符串格式为：`'key1->key2->0'`，数组格式：`array('key1', 'key2', 0)`
 * @param mixed $default 默认值，当查询的keys的值不存在时，返回该默认值。
 * @return mixed
 */
function depthQuery($data, $keys, $default = null)
{
	if (empty($keys))
		return $data;
	$keysType = gettype($keys);
	if ($keysType === KE_STR) {
		if (strpos($keys, KE_DEPTH_QUERY) !== false) {
			$keys = explode(KE_DEPTH_QUERY, $keys);
			$keysType = KE_ARY;
		} else {
			// Janpoem 2014.09.21
			// 调整了一些，原来只是检查isset，现在增加empty的判断
			// 需要做更长时间的监控，是否有副作用
			if (is_array($data))
				return !isset($data[$keys]) || ($data[$keys] != 0 && empty($data[$keys])) ? $default : $data[$keys];

			elseif (is_object($data))
				return !isset($data->{$keys}) || ($data[$keys] != 0 && empty($data->{$keys})) ? $default : $data->{$keys};
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
			} elseif (is_object($data)) {
				if (!isset($data->{$key}) || ($data->{$key} != 0 && empty($data->{$key})))
					return $default;
				else
					$data = $data->{$key};
			}
		}
		return $data;
	} else
		return $default;
}

/**
 * 值内容是否相等
 * 动态类型语言的值类型检查真的蛋疼
 *
 * @param mixed $old
 * @param mixed $new
 * @return bool 是否相等
 */
function equals($old, $new)
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
	} else {
		return $old === $new;
	}
}

const KE_SUB_REGEX = '#\{([^\{\}\r\n]+)\}#';

/**
 * 字符串替换函数，命名源自mootools的String.substitute
 *
 * 原本作为Utils包里面的函数，现在将他提取到Common中。
 *
 * @param string $str
 * @param array $args
 * @param string $regex
 * @param array $matches
 * @return string
 */
function substitute($str, array $args = [], $regex = KE_SUB_REGEX, array & $matches = [])
{
	// 先做基本的类型转换
	$type = gettype($str);
	// resource & array, 无法正常的转换为字符表达的内容
	if ($type === KE_RES || $type === KE_ARY)
		return '';
	elseif ($type !== KE_STR || ($type === KE_OBJ && is_callable($str, '__toString')))
		$str = (string)$str;
	if (strlen($str) <= 0)
		return '';
	if (empty($args)) // 没有参数，就表示无需替换了
		return $str;
	if (empty($regex))
		$regex = KE_SUB_REGEX;
	if (preg_match($regex, $str)) {
		$str = preg_replace_callback($regex, function ($m) use ($args, & $matches) {
			$key = $m[1];
			$matches[$key] = ''; // give a default empty string
			if (isset($args[$key]) || isset($args->$key)) {
				$matches[$key] = $args[$key];
			} else {
				$matches[$key] = depthQuery($args, $key, '');
			}
			return $matches[$key];
		}, $str);
		return substitute($str, $args, $regex, $matches);
	}
	return $str;
}

function getPhpErrorStr($code)
{
	switch ($code) {
		case E_ERROR:
			return 'Error';
			break;
		case E_WARNING:
			return 'Warning';
			break;
		case E_PARSE:
			return 'Parse Error';
			break;
		case E_NOTICE:
			return 'Notice';
			break;
		case E_CORE_ERROR:
			return 'Core Error';
			break;
		case E_CORE_WARNING:
			return 'Core Warning';
			break;
		case E_COMPILE_ERROR:
			return 'Compile Error';
			break;
		case E_COMPILE_WARNING:
			return 'Compile Warning';
			break;
		case E_USER_ERROR:
			return 'User Error';
			break;
		case E_USER_WARNING:
			return 'User Warning';
			break;
		case E_USER_NOTICE:
			return 'User Notice';
			break;
		case E_STRICT:
			return 'Strict Notice';
			break;
		case E_RECOVERABLE_ERROR:
			return 'Recoverable Error';
			break;
		default:
			return "Unknown error#$code";
			break;
	}
}

function remainAppRoot($path)
{
	return str_replace([KE_APP, '\\'], ['/' . KE_APP_DIR, '/'], $path);
}

const KE_ASCII_0 = 48; // 1 => 49
const KE_ASCII_9 = 57;
const KE_ASCII_UPPER_A = 65;
const KE_ASCII_UPPER_Z = 90;
const KE_ASCII_LOWER_A = 97;
const KE_ASCII_LOWER_Z = 122;

function camelcase($str, $tokens = ['-', '_', '.'], $first = false)
{
	$result = ucwords(str_replace($tokens, ' ', strtolower($str)));
	$result = str_replace(' ', '', $result);
	if (isset($result[0]) && !$first) {
		$code = ord($result[0]);
		if ($code >= KE_ASCII_UPPER_A && $code <= KE_ASCII_UPPER_Z)
			$result[0] = strtolower($result[0]);
	}
	return $result;
}

function hyphenate($str, $replace = '-', $first = false)
{
	$str = preg_replace_callback('#([A-Z])#', function($matches) use ($replace) {
		return $replace . strtolower($matches[1]);
	}, (string)$str);
	if (!$first)
		$str = ltrim($str, $replace);
	return $str;
}

function parseClass($class)
{
	$class = trim($class, KE_PATH_NOISE);
	if (($pos = strrpos($class, '\\')) !== false) {
		return [substr($class, 0, $pos), substr($class, $pos + 1)];
	}
	return [null, $class];
}
