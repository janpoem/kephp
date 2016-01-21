<?php

namespace Agi;

use \App;

/**
 * Class AppConfig
 * @package Agi
 * @author Janpoem <janpoem@163.com>
 */
class AppConfig
{

    const INIT = -1;

    const LOADED = 1;

    const UNLOADED = 0;

    /** @var \Agi\AppConfig */
    private static $instance = null;

    /** @var array 已经加载过的config标记 */
    private static $loadedConfig = array();

    private static $encodings = null;

    public $name = '';

    public $hash = '';

    public $language = App::DEFAULT_LANGUAGE;

    public $encode = App::DEFAULT_ENCODE;

    public $timezone = App::DEFAULT_TIMEZONE;

    public $requestValidField = '';

    public $requestValidHash = '';

    public $formats = array(
        'date'     => 'Y-m-d', // 日期格式
        'time'     => 'H:i:s', // 时间格式
        'datetime' => null, // 为空，表示由format->date和format->time组合成
    );

    public $httpBase = null;

    public $httpHosts = array(
        ''          => App::ENV_DEV,
        '0.0.0.0'   => App::ENV_DEV,
        'localhost' => App::ENV_DEV,
        '127.0.0.1' => App::ENV_DEV,
    );

    public $httpCharset = 'utf-8';

    /** @var bool Router是否严格匹配 */
    public $httpStrictMatch = true;

    public $detectEnv = null;

    final public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $class = get_called_class();
            self::$instance = new $class;
        }
        return self::$instance;
    }

    final public static function loadConfig($flag, AppConfig $config)
    {
        self::$loadedConfig[$flag] = self::INIT;
        $file = CONF_DIR . DS . $flag . '.php';
        if (is_file($file) && is_readable($file)) {
            require $file;
            self::$loadedConfig[$flag] = self::LOADED;
        } else {
            self::$loadedConfig[$flag] = self::UNLOADED;
        }
        return $config;
    }

    /**
     * 为了能针对性的做单元测试，Config被限制为全局单例模式，请使用AppConfig::getInstance的方法取得全局的配置。
     *
     * 每一次的运行环境中，Config都应该只被初始化一次。
     */
    final private function __construct()
    {
        self::loadConfig('common', $this);

        if (empty($this->name) || !is_string($this->name))
            $this->name = basename(PROJECT_DIR);
        // 项目的标记
        $flag = hash('crc32b', $this->name);
        if (empty($this->hash))
            $this->hash = $this->name;
        $this->hash .= "#{$flag}";
        $this->hash = hash('sha256', $this->hash);

        if (empty($this->requestValidField))
            $this->requestValidField = '__' . mb_strtolower($this->name) . '_req__';
        if (empty($this->requestValidHash))
            $this->requestValidHash = "{$this->name}#{$this->hash}#{$this->requestValidField}";
        $this->requestValidHash .= "#{$flag}";
        $this->requestValidHash = hash('sha256', $this->requestValidHash, true);

        if ($this->encode !== App::DEFAULT_ENCODE)
            $this->encode = App::filterEncode($this->encode);

        if (empty($this->formats['date']))
            $this->formats['date'] = 'Y-m-d';
        if (empty($this->formats['time']))
            $this->formats['time'] = 'H:i:s';
        if (empty($this->formats['datetime']))
            $this->formats['datetime'] = "{$this->formats['date']} {$this->formats['time']}";

        if (!defined('AGIMVC_INIT')) {
            define('AGIMVC_INIT', true, true);
            define('PROJECT_NAME', $this->name, true);
            define('PROJECT_HASH', $this->hash, true);
            define('PROJECT_LANG', $this->language, true);
            define('PROJECT_ENCODE', $this->encode, true);
            define('PROJECT_TIMEZONE', $this->timezone, true);
            define('PROJECT_FLAG', $flag, true);
            // 全局化一些常用的格式
            define('FORMAT_DATE', $this->formats['date'], true);
            define('FORMAT_TIME', $this->formats['time'], true);
            define('FORMAT_DATETIME', $this->formats['datetime'], true);
            // 全局化一些常用的值
            define('NOW_TS', time(), true); // 现在的时间戳
            define('NOW_DT', date(FORMAT_DATETIME, NOW_TS), true); // 现在的时间，包含日期
            define('NOW', date(FORMAT_TIME, NOW_TS), true); // 现在时间，不包含日期
            define('TODAY', date(FORMAT_DATE, NOW_TS), true); // 今天日期

            mb_language(PROJECT_LANG);
            mb_internal_encoding(PROJECT_ENCODE);
            mb_detect_order(['ASCII', PROJECT_ENCODE, 'GB2312']);
            mb_http_output(PROJECT_ENCODE);
            mb_http_input('I');

            // php 5.6 以下无效
//            ini_set('mbstring.language', PROJECT_LANG);
//            ini_set('mbstring.internal_encoding', PROJECT_ENCODE);
//            ini_set('mbstring.http_input', PROJECT_ENCODE);
//            ini_set('mbstring.http_output', PROJECT_ENCODE);

            ini_set('default_charset', PROJECT_ENCODE);
            ini_set('default_mimetype', 'text/html');

            $env = App::ENV_DEV;
            $httpDir = '/';
            $httpScript = '';
            if (PHP_SAPI === 'cli') {
                $env = PROJECT_DIR . '/env';
                if (is_file($env) && is_readable($env)) {
                    $_SERVER['SERVER_NAME'] = trim(file_get_contents($env));
                }
                if (empty($_SERVER['SERVER_NAME']))
                    $_SERVER['SERVER_NAME'] = 'localhost';
//                if (!isset($_SERVER['SERVER_NAME'])) {
//                    fwrite(STDOUT, 'Please input $_SERVER[SERVER_NAME] :');
//                    $_SERVER['SERVER_NAME'] = trim(fgets(STDIN));
//                }
                if (!isset($_SERVER['REQUEST_URI']))
                    $_SERVER['REQUEST_URI'] = '/';
                if (!isset($_SERVER['HTTP_HOST']))
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'];
                $uri = $_SERVER['REQUEST_URI'];
            } else {
                $uri = purgePath($_SERVER['REQUEST_URI']); // uri有可能会被外部修改，所以优先过滤一次
                $this->httpBase = empty($this->httpBase)
                    ? compareUriBase($uri, $_SERVER['SCRIPT_NAME'])
                    : purgePath($this->httpBase);
                $httpScript = basename($_SERVER['SCRIPT_NAME']);
                $httpDir = basename($this->httpBase) === $httpScript ? dirname($this->httpBase) : $this->httpBase;
            }
	        $isHttpStrictMatch = (bool)$this->httpStrictMatch;
            define('HTTP_URI', $uri, true);
            define('HTTP_DIR', $httpDir, true);
            define('HTTP_SCRIPT', $httpScript, true);
            define('HTTP_BASE', $this->httpBase, true);
            define('HTTP_CHARSET', $this->httpCharset, true);
            define('HTTP_STRICT_MATCH', $isHttpStrictMatch, true);
            define('HTTP_V_FIELD', $this->requestValidField, true);
            define('HTTP_V_HASH', $this->requestValidHash, true);

            if (isset($this->httpHosts[$_SERVER['HTTP_HOST']]))
                $env = $this->httpHosts[$_SERVER['HTTP_HOST']];
            if (is_callable($this->detectEnv)) {
                $detect = $this->detectEnv($env);
                if (!empty($detect))
                    $env = $detect;
            }
            if ($env !== App::ENV_PRO && $env !== App::ENV_TEST)
                $env = App::ENV_DEV;
            define('APP_ENV', $env, true);
        }

        self::loadConfig(APP_ENV, $this);
    }

    /**
     * 用于外部判断是不是加载了某个指定的配置文件
     *
     * @param $flag
     * @return bool
     */
    public function isLoaded($flag)
    {
        return isset(self::$loadedConfig[$flag]) && self::$loadedConfig[$flag] === self::LOADED;
    }

    /**
     * 取得flag被加载的状态，如果指定了第二个参数equal的话，则检查flag的状态是否与equal相同
     *
     * null     表示未被使用
     * -1       表示被使用，单未知状态
     * 0        表示加载失败
     * 1        表示加载成功
     *
     * @param string $flag
     * @param null|int $equal
     * @return bool|null
     */
    public function getLoadStatus($flag, $equal = null)
    {
        $status = isset(self::$loadedConfig[$flag]) ? self::$loadedConfig[$flag] : null;
        if (isset($equal))
            return $status === $equal;
        return $status;
    }
}