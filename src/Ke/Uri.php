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
 * Uri类
 *
 * 部分接口遵照PSR-7规范，但不完全遵守PSR-7规范的要求。
 *
 * 本类经过精心的调整，可支持任意的继承扩展，如/Ke/Web/Params则是继承自该类而实现
 *
 * @package Ke\Core
 * @link    https://github.com/php-fig/http-message/blob/master/src/UriInterface.php (PSR-7 UriInterface规范)
 * @link    http://tools.ietf.org/html/rfc3986 (URI规范)
 * @property string $scheme
 * @property string $host
 * @property string $user
 * @property string $pass
 * @property int    $port
 * @property string $path
 * @property array  $query
 * @property string $fragment
 * @property string $hostPort
 * @property string $authority
 * @property string $userInfo
 * @property string $queryString
 * @property string $uri
 */
class Uri
{

	/** @var bool 是否已经完成预备过程 */
	private static $isPrepare = false;

	/** @var array 存放已经解析过的路径 */
	private static $purgePaths = [];

	private static $filterPaths = [];

	private static $current = null;

	/** @var array 已知的协议、端口号 */
	private static $stdPorts = [
		'http'  => 80,
		'https' => 443,
		'ftp'   => 21,
		'ssh'   => 22,
		'sftp'  => 22,
	];

	private $isHideAuthority = false;

	// data 不填充默认值，这样能节省一点内存
	/** @var array 数据容器 */
	protected $data = [
		'scheme'   => '',
		'host'     => '',
		'user'     => '',
		'pass'     => '',
		'port'     => null,
		'path'     => '',
		'query'    => '',
		'fragment' => '',
	];

	/** @var array */
	protected $queryData = [];

//	protected $withAuthorityUri = null;
//
//	protected $withoutAuthorityUri = null;

	/**
	 * 检查端口号是否为相关协议的标准端口
	 *
	 * <code>
	 * Uri::isStdPort(80, 'http'); // true
	 * Uri::isStdPort(8080, 'http'); // false
	 * </code>
	 *
	 * @param string $scheme scheme，必须是小写的格式
	 * @param int    $port   端口号
	 * @return bool
	 */
	public static function isStdPort($port, $scheme = null)
	{
		return !empty($scheme) && isset(self::$stdPorts[$scheme]) && self::$stdPorts[$scheme] === intval($port);
	}

	public static function filterPath($path, array $excludes = null)
	{
		if (empty($path))
			return '';
		if (!isset(self::$filterPaths[$path])) {
			$isAbsolute = false;
			$split = explode('/', $path);
			$segments = [];
			foreach ($split as $index => $segment) {
				if (empty($segment) || $segment === '.' || $segment === KE_DS_UNIX || $segment === KE_DS_WIN) {
					if ($index === 0)
						$isAbsolute = true;
					continue;
				}
				if (!empty($excludes) && isset($excludes[$segment]))
					continue;
				$segments[] = urldecode($segment);
			}
			$result = implode('/', $segments);
			if ($isAbsolute)
				$result = '/' . $result;
			self::$filterPaths[$path] = $result;
		}
		return self::$filterPaths[$path];
	}

	/**
	 * 全局的Uri预备函数
	 *
	 * 将当前的请求（包括执行的脚本），解析为一个合乎规范的uri。并将uri拆分成几个常量来保存：
	 *
	 * KE_REQUEST_SCHEME => 当前请求的协议（CLI模式下，为cli）
	 * KE_REQUEST_HOST   => 当前请求的主机名，如果端口为非标准端口，该常量将包含端口号（CLI模式，则根据env文件）
	 * KE_REQUEST_URI    => 当前请求的URI，这个URI表示为本地主机的URI，即不包含scheme和host的部分，包含queryString
	 * KE_REQUEST_PATH   => URI的路径部分（排除queryString）
	 *
	 * 在cli模式，假定执行文件为：php /var/www/kephp/tests/hello.php
	 *
	 * 在这个执行文件中，已经定义了项目的根目录为：/var/www/kephp
	 *
	 * 则当前的cli模式下，完整的URI为：cli://localhost/kephp/tests/hello.php?argv
	 *
	 * 对应上述的常量：
	 * KE_REQUEST_SCHEME => cli
	 * KE_REQUEST_HOST   => localhost
	 * KE_REQUEST_URI    => /kephp/tests/hello.php?argv
	 * KE_REQUEST_PATH   => /kephp/tests/hello.php
	 *
	 * cli模式下，KE_REQUEST_PATH的第一段，代表的就是这个项目的根目录，在执行替换的时候，可以将第一段（/kephp）替换为KE_APP常量的内容
	 *
	 * cli模式下，queryString即为$_SERVER['argv']
	 *
	 * @return bool
	 */
	public static function prepare()
	{
		if (self::$isPrepare === true)
			return false;
		self::$isPrepare = true;
		$ptcVer = null;
		$query = [];
		if (PHP_SAPI === 'cli') {
			$_SERVER['REQUEST_SCHEME'] = 'cli';
			if (empty($_SERVER['SERVER_NAME']))
				$_SERVER['SERVER_NAME'] = 'localhost';
			if (empty($_SERVER['HTTP_HOST']))
				$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'];
			// @todo cli的queryString即为执行时的参数，具体为：$_SERVER['argv'] 1 .. n => $query，后续会增加
			$path = substr(KE_SCRIPT_PATH, strlen(KE_APP_ROOT));
			if (KE_IS_WIN)
				$path = str_replace('\\', '/', $path);
			$path = '/' . KE_APP_DIR . $path;
			$_SERVER['REQUEST_URI'] = $path;
		} else {
			$ptcVer = substr($_SERVER['SERVER_PROTOCOL'], strpos($_SERVER['SERVER_PROTOCOL'], '/') + 1);
			if (!isset($_SERVER['REQUEST_SCHEME'])) {
				$_SERVER['REQUEST_SCHEME'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
			} else {
				$_SERVER['REQUEST_SCHEME'] = strtolower($_SERVER['REQUEST_SCHEME']);
			}
			if (!isset($_SERVER['HTTP_HOST'])) {
				$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'];
				if (!static::isStdPort((int)$_SERVER['SERVER_PORT'], $_SERVER['REQUEST_SCHEME']))
					$_SERVER['HTTP_HOST'] .= ':' . $_SERVER['SERVER_PORT'];
			} else {
				$_SERVER['HTTP_HOST'] = strtolower($_SERVER['HTTP_HOST']);
			}
			$parse = parse_url($_SERVER['REQUEST_URI']);
			if (empty($parse['path']))
				$parse['path'];
			elseif ($parse['path'] !== '/')
				$parse['path'] = purgePath($parse['path'], KE_PATH_DOT_NORMALIZE, KE_PATH_LEFT_REMAIN, '/');
			$path = $parse['path'];
			if (!empty($parse['query']))
				parse_str($parse['query'], $query);
			$_SERVER['QUERY_STRING'] = empty($query) ? '' : http_build_query($query, null, '&', PHP_QUERY_RFC3986);
			$_SERVER['REQUEST_URI'] = $path . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']);
		}

		self::$purgePaths[$path] = 1;
		define('KE_REQUEST_SCHEME', $_SERVER['REQUEST_SCHEME']);
		define('KE_REQUEST_HOST', $_SERVER['HTTP_HOST']);
		define('KE_REQUEST_URI', $_SERVER['REQUEST_URI']);
		define('KE_REQUEST_PATH', $path);
		define('KE_PROTOCOL_VER', $ptcVer);

		return true;
	}

	/**
	 * @return static
	 */
	public static function current()
	{
		if (!isset(self::$current)) {
			self::$current = new static([
				'scheme' => KE_REQUEST_SCHEME,
				'host'   => KE_REQUEST_HOST,
				'uri'    => KE_REQUEST_URI,
			]);
		}
		return self::$current;
	}

	/**
	 * Uri构建函数
	 *
	 * @param Uri|string|array|object $uri 传入的uri数据，可以是一个URi的实例，也可以是一个字符串、数组或对象。
	 */
	public function __construct($uri = null)
	{
		if (self::$isPrepare === false)
			static::prepare();
		if (isset($uri))
			$this->setData($uri);
	}

	/**
	 * 设置uri数据
	 *
	 * @todo 注意，如果传入的是：http://www.kephp.com/ ，表示的scheme: http, host: www.kephp.com, path: /，这仍然有一些问题。
	 *
	 * <code>
	 * $uri = new Uri( 'http://www.baidu.com' );
	 * // 等价于 setScheme
	 * $uri->setData('https:'); // => https://www.baidu.com
	 * // 等价于setHost, setPort，所以请注意如果你设置的是一个path，请不要以//开头
	 * $uri->setData('//www.163.com:90'); // => https://www.163.com:90
	 * // 等价于合并两组queryString
	 * $uri->setData('?id=1&keyword=mobile', 'keyword=email'); // https://www.163.com:90/?id=1&keyword=email
	 * </code>
	 *
	 * @param Uri|string|array|object       $input      输入的uri数据，可以是一个URi的实例，也可以是一个字符串、数组或对象。
	 * @param null|bool|array|string|object $mergeQuery 是否要合并查询数据，
	 *                                                  当为true|false类型时，表示的是否合并Query。
	 *                                                  false，表示的是替换已经存在的queryString（清空）
	 *                                                  为非空数据时，则是否合并Query为true，且同时再合并处理这个数据。
	 *                                                  如果希望去除某个字段的query，可将此字段的值设为null，如: ['a' => null]
	 * @return $this 返回当前的Uri实例
	 */
	public function setData($input, $mergeQuery = null)
	{
		if ($input instanceof static) {
			$input->cloneTo($this);
			return $this;
		}
		$type = gettype($input);
		if ($type === KE_STR) {
			$input = parse_url($input);
		} elseif ($type === KE_OBJ) {
			$input = get_object_vars($input);
		}
		if (isset($input['uri'])) {
//			$input = array_merge($input, parse_url($input['uri']));
//			unset($input['uri']);
//			return $this->setData($input, $mergeQuery);
			$uri = $input['uri'];
			unset($input['uri']);
			return $this->setData($input, $mergeQuery)->setData($uri, $mergeQuery);
		}
		$isMergeQuery = !empty($mergeQuery);
		if ($mergeQuery === true || $mergeQuery === false) {
			$isMergeQuery = $mergeQuery;
			if ($mergeQuery === false) {
				if (!isset($input['query']))
					$input['query'] = [];
			}
			$mergeQuery = null;
		}

		// host
		if (!empty($input['host'])) {
			if ($input['host'] !== KE_REQUEST_HOST)
				$input['host'] = strtolower(trim($input['host'], '/'));
			// http://localhost => http, localhost
			// //localhost => localhost
			if (($index = strpos($input['host'], '//')) !== false) {
				if ($index > 1)
					$input['scheme'] = substr($input['host'], 0, $index - 1);
				$input['host'] = substr($input['host'], $index + 2);
			}
			// localhost:90 => localhost, 90
			if (($index = strpos($input['host'], ':')) !== false) {
				$input['port'] = substr($input['host'], $index + 1);
				$input['host'] = substr($input['host'], 0, $index);
			}
		}
		// scheme
		if (!empty($input['scheme'])) {
			if (!isset(self::$stdPorts[$input['scheme']])) {
				// ftp: => ftp
				if (($scheme = strstr($input['scheme'], ':', true)) !== false)
					$input['scheme'] = $scheme;
				$input['scheme'] = strtolower($input['scheme']);
			}
		}

		// port
		if (isset($input['port']) && is_numeric($input['port']) && $input['port'] > 0 && $input['port'] < 65536) {
			$input['port'] = (int)$input['port'];
		}

		if (!empty($input['path'])) {
			// @todo path是否可能会传入一个数组格式的？
			$isAbsolute = $input['path'][0] === '/';
			// 这个路径处理，还是太损耗运算时间了。
			// 暂时只过滤了多余部分
			if (strpos($input['path'], '/') === false) {
				$input['path'] = urldecode($input['path']);
			} else {
				$input['path'] = static::filterPath($input['path']);
			}
			if (!empty($input['path'])) {
				if (!$isAbsolute && !empty($this->data['path']))
					$input['path'] = $this->data['path'] . '/' . $input['path'];
			}
		}

		if (isset($input['query'])) {
			$query = $input['query'];
			if (empty($query)) {
				$query = [];
			} else {
				$type = gettype($input['query']);
				if ($type === KE_OBJ) {
					$query = get_object_vars($query);
				}
				elseif ($type === KE_ARY) {
					// @todo 严格来说，当query为一个数组的时候，应该循环遍历，并执行key, value的urlencode
				} else {
					// 强制转为字符串类型
					if ($type !== KE_STR)
						$query = (string)$query;
					if ($query[0] === '?')
						$query = ltrim($query, '?');
					parse_str($query, $query);
				}
			}
			// 合并query，先合并
			$isChangeQuery = false;

			if ($this->queryData !== $query) {
				$isChangeQuery = true;
				if ($isMergeQuery)
					$this->queryData = array_merge($this->queryData, $query);
				else
					$this->queryData = $query;
			}

//			if (empty($this->queryData)) {
//				if (!empty($query)) {
//					$isChangeQuery = true;
//					$this->queryData = $query;
//				}
//			} elseif ($this->queryData !== $query) {
//				$isChangeQuery = true;
//				if ($isMergeQuery)
//					$this->queryData = array_merge($this->queryData, $query);
//				else
//					$this->queryData = $query;
//			}
			if ($isChangeQuery) {
				if (empty($this->queryData))
					$input['query'] = '';
				else
					$input['query'] = http_build_query($this->queryData);
			}
		}

		if (isset($input['fragment'])) {
			$input['fragment'] = (string)$input['fragment'];
			if (isset($input['fragment'][0]) && $input['fragment'][0] === '#')
				$input['fragment'] = ltrim($input['fragment'], '#');
		}

		if (!empty($input)) {
			$this->data = array_merge($this->data, $input);
		}

		if (isset($mergeQuery)) {
			$this->setData(['query' => $mergeQuery]);
		}

		return $this;
	}

	public function getData()
	{
		return $this->data;
	}

	public function __get($field)
	{
		if ($field === 'port') {
			return $this->getPort();
		} elseif ($field === 'hostPort') {
			return $this->getHost(true);
		} elseif ($field === 'authority') {
			return $this->getAuthority();
		} elseif ($field === 'uri') {
			return $this->getUri();
		} elseif ($field === 'fullUri') {
			return $this->getUri(true);
		} elseif ($field === 'userInfo') {
			return $this->getUserInfo();
		} elseif ($field === 'query') {
			return $this->queryData;
		} elseif ($field === 'queryString') {
			return empty($this->data['query']) ? '' : $this->data['query'];
		} else {
			return empty($this->data[$field]) ? '' : $this->data[$field];
		}
	}

	public function __set($field, $value)
	{
		if ($field === 'mergeQuery' || $field === 'query') {
			$this->mergeQuery($value);
		} elseif ($field === 'queryString') {
			$this->setData(['query' => $value]);
		} else {
			$this->setData([$field => $value]);
		}
	}

	public function cloneTo(Uri $clone)
	{
		$clone->data = array_intersect_key($this->data, $clone->data);
		$clone->queryData = $this->queryData;
		return $clone;
	}

	public function newUri($uri = null, $mergeQuery = null)
	{
		$clone = $this->cloneTo(new Uri());
		if (isset($uri)) {
			$clone->setData($uri, $mergeQuery);
		}
		return $clone;
	}

	public function getScheme()
	{
		return empty($this->data['scheme']) ? '' : $this->data['scheme'];
	}

	public function getHost($withPort = false)
	{
		$host = empty($this->data['host']) ? '' : $this->data['host'];
		if ($withPort) {
			$port = $this->getPort();
			if (!empty($port))
				$host .= ':' . $port;
		}
		return $host;
	}

	public function getPort()
	{
		if (isset($this->data['port']) && !$this->isStdPort($this->data['port'], $this->getScheme()))
			return $this->data['port'];
		return null;
	}

	public function getUserInfo()
	{
		if (empty($this->data['user']))
			return '';
		return $this->data['user'] . (empty($this->data['pass']) ? '' : ':' . $this->data['pass']);
	}

	public function getAuthority()
	{
		if (empty($this->data['host']))
			return '';
		$result = $this->getUserInfo();
		if (!empty($result))
			$result .= '@';
		$result .= $this->data['host'];
		$port = $this->getPort();
		if (!empty($port))
			$result .= ':' . $port;
		return $result;
	}

	public function getPath()
	{
		if (empty($this->data['path']))
			return '';
		return $this->data['path'];
	}

	public function getQuery()
	{
		return empty($this->data['query']) ? '' : $this->data['query'];
	}

	public function getQueryData()
	{
		return $this->queryData;
	}

	public function query($keys, $default = null)
	{
		if (isset($this->queryData[$keys]))
			return $this->queryData[$keys];
		return depthQuery($this->queryData, $keys, $default);
	}

	public function getFragment()
	{
		return empty($this->data['fragment']) ? '' : $this->data['fragment'];
	}

	public function setScheme($scheme)
	{
		return $this->setData(['scheme' => $scheme]);
	}

	public function setHost($host)
	{
		return $this->setData(['host' => $host]);
	}

	public function setPort($port)
	{
		return $this->setData(['port' => $port]);
	}

	public function setUserInfo($user, $pass)
	{
		return $this->setData(['user' => $user, 'pass' => $pass]);
	}

	public function setPath($path)
	{
		return $this->setData(['path' => $path]);
	}

	public function setQuery($query, $mergeQuery = null)
	{
		return $this->setData(['query' => $query], $mergeQuery);
	}

	public function mergeQuery($query)
	{
		$this->setData(['query' => $query], true);
		return $this;
	}

	public function setFragment($fragment)
	{
		return $this->setData(['fragment' => $fragment]);
	}

	public function __toString()
	{
		return $this->getUri();
	}

	public function getUri($isWithAuthority = null)
	{
		if (!isset($isWithAuthority))
			$isWithAuthority = !$this->isHideAuthority();
		$uri = '';
		if ($isWithAuthority) {
			$uri = $this->getScheme();
			if (!empty($this->_uri))
				$uri .= ':';
			$authority = $this->getAuthority();
			if (!empty($authority)) {
				$uri .= '//' . $authority;
			}
		}
		$path = $this->getPath();
		if (!empty($this->data['host']) && (empty($path) || isset($path[0]) && $path[0] !== '/'))
			$uri .= '/';
		$uri .= $path;
		if (!empty($this->data['query']))
			$uri .= '?' . $this->data['query'];
		if (!empty($this->data['fragment']))
			$uri .= '#' . $this->data['fragment'];
		return $uri;
	}

	public function isLocalhost()
	{
		if (isset($this->data['scheme']) && $this->data['scheme'] === KE_REQUEST_SCHEME &&
			$this->getHost(true) === KE_REQUEST_HOST
		) {
			return true;
		}
		return false;
	}

	public function isHideAuthority()
	{
		if ($this->isHideAuthority)
			return true;
		if (empty($this->data['host']))
			return true;
		if ($this->isLocalhost())
			return true;
		return false;
	}

	public function setHideAuthority($isHide)
	{
		$this->isHideAuthority = (bool)$isHide;
		return $this;
	}
}