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
 * 替换原来的实现，原来的实现setData太过复杂，调试不易。
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

	private static $isPrepare = false;

	/** @var array 存放已经解析过的路径 */
	private static $purgePaths = [];

	private static $filterPaths = [];

	private static $currentUri = null;

	/** @var array 已知的协议、端口号 */
	private static $stdPorts = [
		'http'  => 80,
		'https' => 443,
		'ftp'   => 21,
		'ssh'   => 22,
		'sftp'  => 22,
	];

	/** @var array 数据容器 */
	protected $data = [
		'scheme'   => null,
		'host'     => null,
		'user'     => null,
		'pass'     => null,
		'port'     => null,
		'path'     => null,
		'query'    => null,
		'fragment' => null,
	];

	/** @var array */
	protected $queryData = [];

	protected $isHideAuthority = false;


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
	public static function isStdPort($port, $scheme = null): bool
	{
		return !empty($scheme) && isset(self::$stdPorts[$scheme]) && self::$stdPorts[$scheme] === intval($port);
	}

	public static function filterPath($path, array $excludes = null): string
	{
		if (empty($path))
			return '';
		if (!isset(self::$filterPaths[$path])) {
			$isAbsolute = false;
			$split = explode('/', $path);
			$segments = [];
			foreach ($split as $index => $segment) {
				if (empty($segment) || $segment === KE_DS_UNIX || $segment === KE_DS_WIN) {
					if ($index === 0)
						$isAbsolute = true;
					continue;
				}
				if ($segment === '.')
					continue;
				$segment = preg_replace('#^\.{2,}#', '..', $segment);
				$segment = urldecode($segment);
				if (!empty($excludes) && isset($excludes[$segment]))
					continue;
				$segments[] = $segment;
			}
			$count = count($segments);
			if ($count > 0) {
				$last = $segments[$count - 1];
				if (!preg_match('#[^.]+\.[^.]+#', $last)) {
					$segments[] = '';
				}
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
	public static function prepare(): bool
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
				$parse['path'] = static::filterPath($parse['path']);
			$path = $parse['path'];
			if (!empty($parse['query']))
				parse_str($parse['query'], $query);
			$_SERVER['QUERY_STRING'] = empty($query) ? '' : http_build_query($query, null, '&', PHP_QUERY_RFC3986);
			$_SERVER['REQUEST_URI'] = $path . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']);
			// 这个只在HTTP模式有用，这个SCRIPT_NAME可能会出现/aa///bb，但一定不会出现/../aabb/./
			// 所以这里只亮小路径过滤，而不用大路径过滤方法
			// 而且他也是HTTP的路径，也符合
			$_SERVER['SCRIPT_NAME'] = static::filterPath($_SERVER['SCRIPT_NAME']);
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
	 * @return Uri
	 */
	public static function current()
	{
		if (!isset(self::$currentUri)) {
			self::$currentUri = new static([
				'scheme' => KE_REQUEST_SCHEME,
				'host'   => KE_REQUEST_HOST,
				'uri'    => KE_REQUEST_URI,
			]);
		}
		return self::$currentUri;
	}

	public function __construct($data = null)
	{
		if (self::$isPrepare === false)
			static::prepare();
		if (isset($data))
			$this->setData($data);
	}

	public function setData($data, $mergeQuery = null)
	{
		if ($data instanceof static) {
			return $data->cloneTo($this);
		}
		$type = gettype($data);
		if ($type === KE_STR) {
			$type = KE_ARY;
			$data = parse_url($data);
		} elseif ($type === KE_OBJ) {
			$type = KE_ARY;
			$data = get_object_vars($data);
		}
		if (empty($data) || $type !== KE_ARY)
			return $this;
		if (isset($data['uri'])) {
			$uri = $data['uri'];
			unset($data['uri']);
			return $this->setData($data, $mergeQuery)->setData($uri, $mergeQuery);
		}

		foreach (['scheme', 'host', 'port', 'path', 'fragment'] as $name) {
			if (isset($data[$name])) {
				call_user_func([$this, 'set' . $name], $data[$name]);
				unset($data[$name]);
			}
		}

		if (isset($data['query'])) {
			$this->setQuery($data['query'], $mergeQuery);
			unset($data['query']);
		}

		if (isset($data['user'])) {
			$this->setUserInfo($data['user'], $data['pass'] ?? null);
			unset($data['user'], $data['pass']);
		}

		if (!empty($data))
			$this->filterData($data);

		return $this;
	}

	protected function filterData(array $data)
	{
		$this->data = array_merge($this->data, $data);
		return $this;
	}

	public function getData(): array
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
			return $this->toUri();
		} elseif ($field === 'fullUri') {
			return $this->toUri(true);
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
		if ($field === 'mergeQuery') {
			$this->mergeQuery($value);
		} elseif ($field === 'query') {
			$this->setQuery($value, false);
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

	/**
	 * 设置uri的协议
	 *
	 * <code>
	 * $uri->setScheme('http://www.163.com/')
	 * $uri->setScheme('https')
	 * </code>
	 *
	 * @param string $scheme uri的协议
	 * @return $this
	 */
	public function setScheme($scheme)
	{
		if ($scheme !== $this->data['scheme']) {
			if (isset(self::$stdPorts[$scheme]))
				$this->data['scheme'] = $scheme;
			else {
				if (($scheme = strstr($scheme, ':', true)) !== false)
					$this->data['scheme'] = $scheme;
				$this->data['scheme'] = strtolower($this->data['scheme']);
			}
		}
		return $this;
	}

	public function getScheme()
	{
		return empty($this->data['scheme']) ? '' : $this->data['scheme'];
	}

	public function setHost($host, $port = null)
	{
		if ($host !== $this->data['host']) {
			// 过滤 //www.163.com/
			$host = strtolower(trim($host, '/'));
			$scheme = null;
			if (($index = strpos($host, '//')) !== false) {
				// http://localhost => http, localhost
				// //localhost => localhost
				if ($index > 1)
					$scheme = substr($host, 0, $index - 1);
				$host = substr($host, $index + 2);
			}
			if (($index = strpos($host, ':')) !== false) {
				// localhost:90 => localhost, 90
				$port = substr($host, $index + 1);
				$host = substr($host, 0, $index);
			}
			$this->data['host'] = $host;
			if (!empty($scheme))
				$this->setScheme($scheme);
			if (isset($port))
				$this->setPort($port);
		}
		return $this;
	}

	public function getHost($withPort = false)
	{
		$host = empty($this->data['host']) ? '' : $this->data['host'];
		if ($withPort) {
			if (isset($this->data['port']))
				$host .= ':' . $this->data['port'];
		}
		return $host;
	}

	public function setPort($port)
	{
		if (is_numeric($port))
			$this->data['port'] = (int)$port;
		return $this;
	}

	public function getPort()
	{
		return isset($this->data['port']) ? $this->data['port'] : null;
	}

	public function setUserInfo($user, $pass = null)
	{
		if ($user !== $this->data['user'])
			$this->data['user'] = $user;
		if (isset($pass) && $pass !== $this->data['pass'])
			$this->data['pass'] = $pass;
		return $this;
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
		$result .= $this->getHost(true);
		return $result;
	}

	public function setPath($path, $isMergeQuery = false)
	{
		// 先对路径进行过滤
		$query = $fragment = null;
		if (($index = strpos($path, '#')) !== false) {
			$fragment = substr($path, $index + 1);
			$path = substr($path, 0, $index);
		}
		if (($index = strpos($path, '?')) !== false) {
			$query = substr($path, $index + 1);
			$path = substr($path, 0, $index);
		}
		// 判断路径是否为绝对路径
		$path = static::filterPath($path);
		$isAbsolute = isset($path[0]) && $path[0] === '/';
		if (!$isAbsolute) {
			if (!empty($path)) {
				$len = strlen($this->data['path']);
				if ($len === 0 || ($len > 0 && $this->data['path'][$len - 1] !== '/'))
					$this->data['path'] .= '/';
				$this->data['path'] .= $path;
			}
		} else {
			$this->data['path'] = $path;
		}
		if (!empty($query))
			$this->setQuery($query, $isMergeQuery);
		if (!empty($fragment))
			$this->setFragment($fragment);
		return $this;
	}

	public function setAbsPath($path)
	{
		if (!isset($path[0]) || $path[0] !== '/')
			$path = '/' . $path;
		return $this->setPath($path);
	}

	public function getPath()
	{
		return empty($this->data['path']) ? '' : $this->data['path'];
	}

	public function setQuery($query, $mergeQuery = false)
	{
		$isMerge = false;
		$mergeData = [];
		if ($mergeQuery === true || $mergeQuery === false)
			$isMerge = $mergeQuery;
		elseif (!empty($mergeQuery))
			$mergeData = $mergeQuery;
		if (empty($query)) {
			$query = [];
		} else {
			$type = gettype($query);
			if ($type === KE_OBJ) {
				$query = get_object_vars($query);
			} elseif ($type === KE_ARY) {
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
			if ($isMerge)
				$this->queryData = array_merge($this->queryData, $query);
			else
				$this->queryData = $query;
		}

		if ($isChangeQuery) {
			if (empty($this->queryData))
				$this->data['query'] = '';
			else
				$this->data['query'] = http_build_query($this->queryData, null, '&', PHP_QUERY_RFC3986);
		}
		if (!empty($mergeData))
			$this->setQuery($mergeData, true);
		return $this;
	}

	public function mergeQuery($query)
	{
		return $this->setQuery($query, true);
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
		return depth_query($this->queryData, $keys, $default);
	}

	public function setFragment($fragment)
	{
		if (isset($fragment[0]) && $fragment[0] === '#')
			$fragment = ltrim($fragment, '#');
		$this->data['fragment'] = $fragment;
		return $this;
	}

	public function getFragment()
	{
		return empty($this->data['fragment']) ? '' : $this->data['fragment'];
	}

	public function __toString()
	{
		return $this->toUri();
	}

	public function toUri($isWithAuthority = null)
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