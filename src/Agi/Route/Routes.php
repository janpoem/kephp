<?php

namespace Agi\Route;

/**
 * Class Routes
 *
 * 路由配置类，目前该类已经设计成可被缓存的模式
 * 输出缓存：var_export(Agi_Route_Routes::getInstance('routes'))
 * 输入缓存：Agi_Route_Routes::__set_state($data) => 会将routes装载，而越过正常的实例化过程
 *
 * 但框架默认不集成输出、输入缓存的实现，而可以在项目中，自己重载实现App类，在onBootstrap和onExiting接口中实现。
 *
 * 以下代码演示的是基于文件系统，对routes进行缓存的使用，如果使用memcached，效果更佳
 * <code>
 * class App extends Agi_AppCore {
 *
 *     protected static function onBootstrap() {
 *         import(self::getRoutesCacheFile());
 *     }
 *
 *     protected static function onExiting() {
 *         $file = self::getRoutesCacheFile();
 *         $data = self::getRoutesCacheData();
 *         if (!is_file($file) || md5_file($file) !== md5($data)) {
 *             file_put_contents(self::getRoutesCacheFile(), $data);
 *         }
 *     }
 *
 *     public static function getRoutesCacheFile() {
 *         return PROJECT_DIR . '/temp/routes.php';
 *     }
 *
 *     public static function getRoutesCacheData() {
 *         return '<?php' . PHP_EOL . var_export(Agi_Route_Routes::getInstance('routes'), true) . ';';
 *     }
 * }
 * </code>
 *
 * @package Agi\Route
 * @author Janpoem created at 2014/9/22 19:03
 */
class Routes
{

    const DEFAULT_FLAG = 'routes';

    /** 基础的替代模式 */
    const PATTERN_BASE = '[a-z][a-z0-9\_\-]*[a-z0-9]';

    /** Tail的匹配，3.x版本中的Target */
//	const PATTERN_TARGET = '([\w\-]+(?:\/[\w\-]+)*)';
//	const PATTERN_TAIL = '[\w\-]+(?:\/[a-zA-Z0-9\x7f-\xff\-\_]+)*|';
    const PATTERN_TAIL = '.*';

    // 以下为常用的匹配正则
    /** ID的替代模式 */
    const PATTERN_ID = '[\d]+';

    /** Name的替代模式 */
    const PATTERN_NAME = '[a-z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

    const PATTERN_YEAR = '19[\d]{2}|20[\d]{2}';

    const PATTERN_MONTH = '[\d]{1}|0[\d]{1}|1[0-2]';

    const PATTERN_DAY = '[\d]{1}|[0-2][\d]{1}|3[0-1]{1}';

    private static $instances = array();

    private $loaded = false;

    private $flag = null;

    private $source = null;

    private $timestamp = -1;

    private $tokenStart = '{';

    private $tokenEnd = '}';

    private $tokenRegex = '#\{([^\{\}]+)\}#i';

    /** @var array 需要替换的特定符号表 */
    public $symbols = array(
        '(' => '(?:',
        ')' => ')?'
    );

    /** @var array 内定的Tokens */
    public $tokens = array(
        'controller' => self::PATTERN_BASE,
        'action'     => self::PATTERN_BASE,
        'tail'       => self::PATTERN_TAIL,
        'id'         => self::PATTERN_ID,
        'name'       => self::PATTERN_NAME,
        'year'       => self::PATTERN_YEAR,
        'month'      => self::PATTERN_MONTH,
        'day'        => self::PATTERN_DAY,
    );

    public $baseMappings = array(
//            '({controller}(/{action}))',
        '{controller}/{action}',
        '({controller})',
    );

    public $patternFormat = '%s(/{tail}|)';

    public $modules = array();

    public $mappings = array();

    public $defaultParams = array();

    /** @var string 清理Params字符串中多余的/#?符号 */
    public $regexPurgeParamsStr = '#(?:([\/\#/?])\1+)#';

    /**
     * @var string 解析Params字符串为数组格式
     *
     * namespace/controller#action?key=value
     */
    public $regexParseParamsStr = '#^((?:[a-z][a-z0-9_]*\/)*)?(?:([a-z][a-z0-9_]*)?(?:\#([a-z][a-z0-9_]*)(?:\?(.+))?)?)$#i';

    /** @var string 表达式中的变量正则匹配 */
    public $regexSub = '#\{([a-z][a-z0-9_-]+)\}#i';

    /**
     * 取得指定Flag的Routes实例
     *
     * @param string $flag
     * @return Routes
     */
    public static function getInstance($flag = self::DEFAULT_FLAG)
    {
        if (!isset(self::$instances[$flag])) {
            self::$instances[$flag] = self::loadConfig($flag)->prepare();
        }
        return self::$instances[$flag];
    }

    protected static function loadConfig($flag, Routes $routes = null)
    {
        if (!isset($routes)) {
            $class = get_called_class();
            $routes = new $class();
        }
        if (!$routes->loaded) {
            $routes->loaded = true;
            $file = CONF_DIR . "/{$flag}.php";
            if (is_file($file) && is_readable($file)) {
                $routes->flag = $flag;
                $routes->source = $file;
                $routes->timestamp = filemtime($file);
                require $file;
            }
        }
        return $routes;
    }

    /**
     * Routes实例加载接口，主要用于对应var_export输出的内容
     *
     * @param array $data
     * @return Routes
     */
    public static function __set_state(array $data)
    {
        $class = get_called_class();
        if (!empty($data['source']) && is_file($data['source']) && is_readable($data['source'])) {
            if (filemtime($data['source']) > $data['timestamp'])
                return static::getInstance($data['flag']);
        }
        /** @var Routes $instance */
        $instance = new $class;
        foreach ($data as $field => $value) {
            $instance->$field = $value;
        }
        $instance->prepare();
        self::$instances[$instance->flag] = $instance;
        return $instance;
    }

    /**
     * 解析字符串为Params
     *
     * 字符串的格式：namespace/controller#action?key=value
     *
     * @param string $str
     * @return array
     */
    public function parseStr($str)
    {
        $str = preg_replace($this->regexPurgeParamsStr, '$1', $str);
        $params = array(); // 克隆这个默认的参数设置
        if (preg_match($this->regexParseParamsStr, $str, $matches)) {
            if (!empty($matches[1]))
                $params['namespace'] = $matches[1];
            if (!empty($matches[2]))
                $params['controller'] = $matches[2];
            if (!empty($matches[3]))
                $params['action'] = $matches[3];
            if (!empty($matches[4])) {
                parse_str($matches[4], $others);
                $params += $others;
            }
        }
        return $params;
    }

    private function prepare()
    {
        $defaultParams = null;
        if (!empty($this->defaultParams)) {
            $type = gettype($this->defaultParams);
            if ($type === PHP_STR)
                $defaultParams = $this->parseStr($this->defaultParams);
            else if ($type === PHP_OBJ)
                $defaultParams = get_object_vars($this->defaultParams);
        }
        $this->defaultParams = empty($defaultParams) ? array() : $defaultParams;
        // 避开避开
//        if (!empty($defaultParams))
//            Parameters::setDefaultData($defaultParams);
        return $this;
    }

    /**
     * 编译mappings中的route
     *
     * @param mixed $mapping
     * @return array|bool
     */
    public function compileRoute($mapping)
    {
        if (empty($mapping))
            return false;
        // 非array格式，强制转为数组格式
        if (!is_array($mapping))
            $mapping = array($mapping);
        // 0: 无具体值或者非字符串类型，视为无效
        if (empty($mapping[0]) || !is_string($mapping[0]))
            return false;
        // 1: 当前Mapping的tokens
        if (empty($mapping[1]) || !is_array($mapping[1]))
            $mapping[1] = array();
        // 2: 默认的参数
        if (empty($mapping[2]))
            $mapping[2] = array();
        else {
            $type = gettype($mapping[2]);
            if ($type === PHP_STR)
                $mapping[2] = $this->parseStr($mapping[2]);
            else if ($type === PHP_OBJ)
                $mapping[2] = get_object_vars($mapping[2]);
            // 其他类型，直接替换为空的数组格式
            if (!is_array($mapping[2]))
                $mapping[2] = array();
        }
        return $this->mkPattern($mapping);
    }

    // 这个环节还没有完善，转为私有方法
    private function mkPattern(array $mapping)
    {
        $pattern = $mapping[0];
//        $pattern = sprintf($this->patternFormat, $pattern); // 暂时不用这种模式匹配
        $pattern = strtr($pattern, $this->symbols); // 替换符号，生成基本的$pattern
        // 合并私有tokens和公共tokens为可用的tokens
        $tokens = $mapping[1] + $this->tokens;
        // 找出实际声明在表达式中的$tokens
        $usedTokens = array();
        $replacement = array(); // 替换表
        preg_match_all($this->tokenRegex, $pattern, $matches);
        foreach ($matches[1] as $token) {
            $tokenPattern = isset($tokens[$token]) ? "(?<{$token}>{$tokens[$token]})" : '';
            $usedTokens[$token] = $replacement["{$this->tokenStart}{$token}{$this->tokenEnd}"] = $tokenPattern;
        }
        $pattern = strtr($pattern, $replacement);
        // 已用tokens写入mapping缓存
        $mapping['_tokens_'] = $usedTokens;
        // 不再拼接tail，未匹配的path尾部，自动转化为tail，减少正则容量
        $mapping['_pattern_'] = "#^/{$pattern}(?:/|$)#i";
        return $mapping;
    }
}

 