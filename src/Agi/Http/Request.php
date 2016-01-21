<?php

namespace Agi\Http;

use App;

/**
 * Class Request
 * @package Agi\Http
 * @author Janpoem <janpoem@163.com>
 *
 * 固定的属性
 * @property array $get
 * @property array $post
 * @property array $files
 *
 * 魔术属性
 * @property string $method
 * @property string $uri
 * @property string $protocol
 * @property string $host
 * @property string $ua
 * @property string $referer
 * @property string $scheme
 * @property string $path
 * @property array $query
 * @property string $base
 * @property string $purePath
 * @property string $format
 * @property string $url
 * @property boolean $isXHR
 * @property boolean $isFlash
 * @property string $remoteIp
 * @property string $remotePort
 */
class Request
{

    const GET = 'GET';

    const POST = 'POST';

    /** @var Request */
    private static $current;

    private $isCurrent = false;

    public $get = array();

    public $post = array();

    public $files = array();

    protected $rawPost = false;

    protected $data = array(
        // GET /pm/ HTTP/1.1
        'method'     => self::GET,
        'uri'        => SPR_HTTP_DIR,
        'protocol'   => 'HTTP/1.1',
        // Host
        'host'       => null,
        // User-Agent
        'ua'         => '',
        // Referer
        'referer'    => null,
        // parse
        'scheme'     => 'http',
        'path'       => SPR_HTTP_DIR,
        'query'      => null,
        'base'       => SPR_HTTP_DIR,
        'purePath'   => SPR_HTTP_DIR,
        'format'     => null,
        // build from parse
        'url'        => SPR_HTTP_DIR,
        // HTTP_X_REQUESTED_WITH === xmlhttprequest
        'isXHR'      => false,
        // HTTP_USER_AGENT === Shockwave Flash
        'isFlash'    => false,
        'remoteIp'   => null,
        'remotePort' => null,
    );

    /** @var array */
    protected $parse;

    protected $response;

    /**
     * 获得当前客户端的IP
     *
     * @param bool $isLong
     * @return int|string
     */
    public static function getRemoteIP($isLong = false)
    {
        $ip = '0.0.0.0';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ip = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ip = $_SERVER['REMOTE_ADDR'];

        return $isLong ? (int)ip2long($ip) : $ip;
    }

    /**
     * 取得当前请求的Request实例
     *
     * @return Request
     */
    public static function current()
    {
        if (!isset(self::$current)) {
            $class = get_called_class();
            /** @var Request $req */
            $req = new $class(array(
                'method'     => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : self::GET,
                'url'        => HTTP_URI,
                'protocol'   => isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1',
                'host'       => $_SERVER['HTTP_HOST'],
                'ua'         => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
                'referer'    => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
                'scheme'     => isset($_SERVER['HTTPS']) ? 'https' : 'http',
                'base'       => HTTP_BASE,
                'isXHR'      => isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest',
                'isFlash'    => isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == 'Shockwave Flash',
                'remoteIp'   => static::getRemoteIP(),
                'remotePort' => isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : null,
            ));
            // 绑定GET/POST/FILES
            $req->get = &$_GET;
            $req->post = &$_POST;
            $req->files = &$_FILES;
            $req->isCurrent = true;
            self::$current = $req;
        }
        return self::$current;
    }

    /**
     * 基于URL创建Request实例
     *
     * @param string $url
     * @param string $method
     * @param array $data
     * @return Request
     */
    public static function fromUrl($url, $method = self::GET, array $data = array())
    {
        if (empty($url))
            $url = SPR_HTTP_DIR;
        $class = get_called_class();
        $data['url'] = $url;
        $data['method'] = $method;
        $instance = new $class($data);
        if (isset($data['post'])) {
            $instance->post = $data['post'];
            unset($data['post']);
        }
        return $instance;
    }

    /**
     * 解析http的path，根据传入的base(前缀)，拆分出base, purePath, format
     *
     * @param string $path
     * @param string $base
     * @return array
     */
    public static function parsePath($path, $base = SPR_HTTP_DIR)
    {
        // 最终的返回结果
        $result = array($base, SPR_HTTP_DIR, null);
        if (empty($path) || $path === SPR_HTTP_DIR || $base === $path)
            return $result;
        if ($path !== SPR_HTTP_DIR && $base !== $path) {
            // 先取出pure path
            if (stripos($path, $base) === 0)
                $result[1] = substr($path, strlen($base));
            // 取出后缀名
            if (!empty($result[1]) && preg_match('#[^\/\.]+(?:\.([a-z0-9]+))$#i', $result[1], $matches)) {
                $result[2] = mb_strtolower($matches[1]);
                $result[1] = substr($result[1], 0, -(strlen($result[2]) + 1));
            }
            if (!empty($result[1]) && $result[1] !== SPR_HTTP_DIR) {
                if ($result[1][0] !== SPR_HTTP_DIR)
                    $result[1] = SPR_HTTP_DIR . $result[1];
            }
        }
        return $result;
    }

    /**
     * Request不通过直接使用new的方式创建实例，而通过Request::current(), Request::fromUrl()的方法创建
     *
     * @param array $data
     */
    final private function __construct(array $data = null)
    {
        if (!empty($data)) {
            // 以url为判断基准
            if (empty($data['url']))
                $data['url'] = SPR_HTTP_DIR;
            // 2. base过滤，以HTTP_BASE为基准参照值
            if (empty($data['base']))
                $data['base'] = SPR_HTTP_DIR;
            elseif ($data['base'] !== HTTP_BASE && $data['uri'] !== SPR_HTTP_DIR)
                $data['base'] = purgePath($data['base']);
            $parse = parse_url($data['url']);
            $data['uri'] = $data['path'] = empty($parse['path']) ? SPR_HTTP_DIR : urldecode($parse['path']);
            if (!empty($parse['scheme']))
                $data['scheme'] = $parse['scheme'];
            if (!empty($parse['host']))
                $data['host'] = $parse['host'];
            list(, $data['purePath'], $format) = static::parsePath($data['path'], $data['base']);
            if (!empty($format)) {
                $data['format'] = mb_strtolower($format);
            }
            if (!empty($parse['query'])) {
                $data['uri'] .= "?{$parse['query']}";
                $data['query'] = $parse['query'];
                parse_str($data['query'], $this->get);
            }
            $this->data = array_merge($this->data, $data);
            $this->data['url'] = "{$this->data['scheme']}://{$this->data['host']}{$this->data['uri']}";
        }
    }

    /**
     * 虽然通过__get方法，的确性能上有损，但这种机制可以保证数据不被修改。
     *
     * @param $field
     * @return mixed|null
     */
    public function __get($field)
    {
        if (isset($this->data[$field]))
            return $this->data[$field];
        return null;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * 是否当前的HTTP请求
     *
     * @return bool
     */
    public function isCurrent()
    {
        return $this->isCurrent;
    }

    /**
     * 取得当前请求的queryString值value1
     *
     * $request = new Agi\Http\Request('/hello/world?arg1=value1&arg2[key1]=value2&arg3[]=value3');
     * $request->get('arg1'); // return value1
     * $request->get('arg2'); // return array('key1' => 'value2');
     * $request->get('arg2->key1'); // return value2
     * $request->get('arg3->0'); // return value2
     *
     * @param string|null $keys
     * @param string|null $default
     *
     * @return mixed
     */
    public function get($keys = null, $default = null)
    {
        return depthQuery($this->get, $keys, $default);
    }

    /**
     * 判断是否取得当前请求的queryString值value1
     *
     * @param null $keys
     *
     * @return bool
     */
    public function hasGet($keys = null) {
        return !is_null($this->get($keys)) && !empty($this->get[$keys]);
    }

    /**
     * 取得请求中的Post值，同get方法
     *
     * @param null $keys
     * @param null $default
     *
     * @return mixed
     */
    public function post($keys = null, $default = null)
    {
        return depthQuery($this->post, $keys, $default);
    }

    /**
     * 取得POST请求的原始字符串
     *
     * @return bool|string
     */
    public function getRawPost()
    {
        if ($this->rawPost === false) {
            // 当前请求
            if ($this->isCurrent)
                $this->rawPost = file_get_contents('php://input');
            else
                $this->rawPost = http_build_query($this->post, null);
        }
        return $this->rawPost;
    }

    public function isPost($flag = null, $expire = 0)
    {
        $isPost = $this->data['method'] === self::POST;
        if (isset($flag)) {
            if (!isset($this->post[HTTP_V_FIELD]))
                return false;
            return App::validHttpVerCode($this->post[HTTP_V_FIELD], $flag, $expire);
        }
        return $isPost;
    }

    public function pureUriMatch($pattern, & $match = null, $flag = 0, $offset = 0) {
        return preg_match($pattern, $this->purePath, $match, $flag, $offset);
    }
}

