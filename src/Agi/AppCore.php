<?php

namespace Agi;

use Agi\Action\Exception;
use Agi\Action\BaseController;
use Agi\Action\Parameters;
use Agi\Http\Request;
use Agi\Http\Response;
use Agi\Output\Buffer;
use Agi\Output\ViewRenderer;
use Agi\Output\WidgetRenderer;
use Agi\Util\String;

/**
 * Class AppCore
 * @package Agi
 * @author Janpoem<janpoem@163.com>
 */
class AppCore
{

    /** CLI标识 */
    const CLI = 'cli';

    /** Web标识 */
    const WEB = 'web';

    /** 开发环境 */
    const ENV_DEV = 'development';

    /** 测试环境 */
    const ENV_TEST = 'test';

    /** 产品发布环境 */
    const ENV_PRO = 'production';

    /** 框架运行过程：初始化阶段 */
    const PRO_INIT = 'init';

    /** 框架运行过程：引导阶段 */
    const PRO_BOOTSTRAP = 'bootstrap';

    /** 框架运行过程：正式启动阶段 */
    const PRO_STARTUP = 'start_up';

    /** 框架运行过程：PHP退出阶段 */
    const PRO_EXITING = 'exiting';

    const DEFAULT_ENCODE = 'UTF-8';

    const DEFAULT_TIMEZONE = 'Asia/Shanghai';

    const DEFAULT_LANGUAGE = 'zh-cn';

    /** @var string 框架当前运行的阶段 */
    private static $process = self::PRO_INIT;

    /** @var string 调用bootstrap的Class */
    private static $calledClass = __CLASS__;

    /**
     * @var array agimvc的核心类库，loadClass的时候，会自己补充AGI_DIR前缀
     */
    private static $coreClasses = array();

    /**
     * @var array 用户自定义类库，允许通过registerClasses添加更多的类，类的路径必须是绝对路径
     */
    private static $userClasses = array();

    /** @var array 已经加载的类 */
    private static $loadedClasses = array();

    /** @var AppConfig */
    private static $config = null;

    /** @var Buffer */
    private static $outputBuffer = null;

    /** @var \Easy_Language */
    private static $lang = null;

    /** @var \DateTimeZone */
    private static $timezone = null;

    private static $encodings = array();

    /** @var bool 是否已经分发 */
    private static $isDispatch = false;

    private static $exceptionLayout = false;

    private static $logBuffers = array();


    /** 引导阶段接口 */
    protected static function onBootstrap()
    {
    }

    /** PHP退出接口 */
    protected static function onExiting()
    {
    }

    /**
     * 框架引导函数，在bootstrap.php中自动调用，全局只被执行一次，第二次调用将返回false
     *
     * @return bool
     */
    final public static function bootstrap()
    {
        if (self::$process !== self::PRO_INIT)
            return false;
        self::$process = self::PRO_BOOTSTRAP;
        self::$calledClass = get_called_class();
        self::$coreClasses = import(AGI_DIR . '/classes.php');
        self::$userClasses = import(LIB_DIR . '/classes.php');
        // 注册退出函数
        register_shutdown_function(array(self::$calledClass, 'exiting'));
        // 注册错误接管函数和异常处理函数
        set_error_handler(array(self::$calledClass, 'errorHandle'));
        set_exception_handler(array(self::$calledClass, 'exceptionHandle'));
        // 注册autoload
        spl_autoload_register(array(self::$calledClass, 'loadClass'), false, true);

        // 全局实例，保存在App的静态变量上，做一个快捷引用
        // AppConfig改为单例调用
        self::$config = AppConfig::getInstance();
        // 输出缓冲启动
        self::$outputBuffer = Buffer::getInstance()->start(true);

//        self::$environment = APP_ENV;
//        self::$outputBuffer = new OutputBuffer();
//        self::$outputBuffer->start(true);
        self::$lang = new \Easy_Language(PROJECT_LANG);
        self::$timezone = new \DateTimeZone(PROJECT_TIMEZONE);
        ini_set('date.timezone', PROJECT_TIMEZONE);

        static::onBootstrap();
        self::$process = self::PRO_STARTUP;

        return true;
    }

    /**
     * 框架绑定PHP退出函数
     */
    final public static function exiting()
    {
        if (self::$process !== self::PRO_EXITING) {
            self::$process = self::PRO_EXITING;
            static::onExiting();
            if (!empty(self::$logBuffers)) {
                static::handleLogBuffers(self::$logBuffers);
                self::$logBuffers = null;
            }
        }
    }


    /**
     * 取得当前的运行环境，可直接使用全局静态常量APP_ENV来取得
     *
     * @return string
     */
    final public static function getEnv()
    {
        return APP_ENV;
    }

    /**
     * 判断当前的环境是否指定的值
     *
     * @param string $equal
     * @return bool
     */
    final public static function isEnv($equal)
    {
        return APP_ENV === $equal;
    }

    /**
     * 取得当前运行的状态
     *
     * @return string
     */
    final public static function getProcess()
    {
        return self::$process;
    }

    /**
     * 判断当前的处理过程是不是指定的值
     *
     * @param string $equal
     * @return bool
     */
    final public static function isProcess($equal)
    {
        return self::$process === $equal;
    }

    /**
     * 取得当前的运行模式
     *
     * @return string
     */
    final public static function getMode()
    {
        return PHP_SAPI === self::CLI ? self::CLI : self::WEB;
    }

    /**
     * 检测运行模式是不是指定的值
     *
     * @param string $equal
     * @return bool
     */
    final public static function isMode($equal)
    {
        return self::getMode() === $equal;
    }

    /**
     * 错误处理接口
     *
     * @param $no
     * @param $str
     * @param $file
     * @param $line
     * @param $context
     * @throws Exception
     */
    public static function errorHandle($no, $str, $file, $line, $context)
    {
        $msg = array('php.runtime_error', 'str' => $str, 'file' => $file, 'line' => $line);
        throw new Exception($msg);
    }

    /**
     * 异常处理接口
     *
     * @param \Exception $exception
     */
    public static function exceptionHandle(\Exception $exception)
    {
        $lang = static::getLang();
        if (static::isMode(self::WEB)) {
            $renderer = new ViewRenderer(ViewRenderer::WIDGET, '@error?', array(
                'exception' => $exception,
            ));
            $renderer->assign(array(
                'title'  => $lang['Err#error_occurred'],
                'layout' => self::$exceptionLayout,
            ));
            $code = 500;
            if ($exception instanceof Exception)
                $code = 404;
            Response::getInstance()->setStatusCode($code)->removeHeader()->respond($renderer);
        }
    }

    public static function setExceptionLayout($layout)
    {
        if (!empty($layout) && is_string($layout))
            self::$exceptionLayout = $layout;
    }

    public static function getExceptionLayout()
    {
        return self::$exceptionLayout;
    }

    /**
     * 注册Classes，只能添加未添加进类库的类，不能覆盖已经定义的类
     *
     * @param $classes
     */
    final public static function registerClasses($classes)
    {
        if (!empty($classes) && is_array($classes))
            self::$userClasses += $classes;
    }

    /**
     * 解析className为绝对路径
     *
     * @param $class
     * @return string
     */
    public static function parseClass($class)
    {
        $class = ltrim($class, PATH_NOISE);
        if (isset(self::$coreClasses[$class]))
            return AGI_DIR . DS . self::$coreClasses[$class];
        else if (isset(self::$userClasses[$class]))
            return self::$userClasses[$class];
        else {
            $path = $class;
            if ($controller = static::filterControllerClass($class))
                return static::getControllerPath($controller);
            elseif ($helper = static::filterHelperClass($class))
                return static::getHelperPath($helper);
            elseif ($cli = static::filterCliClass($class))
                return static::getCliPath($cli);
            else {
                if (strpos($path, SPR_NS_PHP53) !== false)
                    $path = str_replace(SPR_NS_PHP53, DS, $path);
                else if (strpos($path, SPR_NS_PEAR) !== false)
                    $path = str_replace(SPR_NS_PEAR, DS, $path);
                return APP_DIR . "/models/{$path}.php";
            }
        }
    }

//    /**
//     * 根据$params生成controller name
//     *
//     * 该方法允许重载，请确保该方法能返回一个有效的className
//     *
//     * @param string $namespace
//     * @param string $controller
//     * @return bool|string
//     */
//    public static function mkControllerName($namespace, $controller)
//    {
//        if (empty($controller))
//            return false;
//        $name = 'Controller';
//        if (!empty($namespace))
//            $name .= SPR_NS_PHP53 . String::path2class($namespace);
//        $name .= SPR_NS_PHP53 . String::path2class($controller);
//        return $name;
//    }

    /**
     * 过滤是否为controller的类名，如是返回截取掉_controller后剩余的class，如否，返回flase
     *
     * 在过去的版本中，当url为：/hello_world，会转化出HelloWorldController的类名出来
     * 但这里就存在一个问题，就是当url为：/HelloWorld，或者/Hello-World的时候，也能匹配到这个HelloWorldController
     * 这个就非常不合理了。
     *
     * 所以4.0版本，在根据url匹配得到controller的时候，会严格转换为小写格式，即：
     * /hello_world => hello_world_controller
     * /HelloWorld => helloworld_controller
     *
     * 因为URL协议里面，路径名是同时允许-和_的，所以这次对于_和-之间，还是做了一个妥协，就是
     * /hello-world => hello_world_controller
     *
     * 如果要严格限制，可以在routes配置中，调整controller和action匹配模式，目前为：
     * $routes->tokens['controller'] = [a-z][a-z0-9\_\-]*
     * 在config/routes.php中添加如下：
     * $routes->tokens['controller'] = [a-z][a-z0-9\_]* （注意，应该action也要按照此模式）
     *
     * 或者添加config/common.php的配置：
     * $config->httpStrictMatch = false
     * 上述表示http router匹配不使用严格模式——即当发生不匹配的时候，不发生异常，而尝试按照默认的Parameters去匹配。
     *
     *
     * @param string $class
     * @return bool|string
     */
    public static function filterControllerClass($class)
    {
        if (substr($class, -11) === '_controller') {
            return substr($class, 0, -11);
        }
        return false;
    }

    /**
     * 取得controller的存放路径
     *
     * @param string $class
     * @return string
     */
    public static function getControllerPath($class)
    {
        $class = strtr($class, SPR_NS_PEAR, DS);
        return APP_DIR . "/controllers/{$class}.php";
    }

    /**
     * 过滤Helper的类名，如果类名结尾为_Helper，返回截取了_Helper的类名
     * 如：Admin_Session_Helper => Admin_Session
     *
     * 如果不是，则返回false
     *
     * 这个方法允许重载，但请确保重载后返回的值是有效值
     *
     * @param string $class
     * @return bool|string
     */
    public static function filterHelperClass($class)
    {
        if (substr($class, -7) === '_Helper') {
            return substr($class, 0, -7);
        }
        return false;
    }

    /**
     * 根据传入的HelperClassName，取得对应的Helper的存放路径。
     *
     * Helper的命名规范为：
     * 1. 类名必须以_Helper结束，如：class Hello_World_Helper
     * 2. 类名对应的文件名，忽略结尾的_Helper，如：class User_Helper，存放文件为：User.php
     * 3. 以_作为目录的分隔符，如：class Hello_World_Helper，存放路径为：Hello/World.php
     *
     * @param string $class
     * @return string
     */
    public static function getHelperPath($class)
    {
        $class = strtr($class, SPR_NS_PEAR, DS);
        return APP_DIR . "/helpers/{$class}.php";
    }

    public static function filterCliClass($class)
    {
        if (strpos($class, 'cli\\') === 0) {
            return $class;
        }
        return false;
    }

    public static function getCliPath($class)
    {
        $class = strtr($class, SPR_NS_PHP53, DS);
        return PROJECT_DIR . "/{$class}.php";
    }


    /**
     * PHP autoload的实现接口
     *
     * @param string $class
     * @param null|string $path
     * @throws Exception
     */
    final public static function loadClass($class, $path = null)
    {
        if (!isset(self::$loadedClasses[$class])) {
            if (empty($path))
                $path = static::parseClass($class);
            self::$loadedClasses[$class] = import($path) === false ? false : $path;
        }
        // PSR-4，禁止在loadClass中抛出异常，抛出错误，或者返回任何值
        if (self::$loadedClasses[$class] !== false && class_exists($class, false)) {
            if (is_subclass_of($class, '\\Agi\\Impl\\AutoLoad'))
                call_user_func(array($class, 'onAutoLoad'), $class);
        }
    }

    /**
     * 取得已经加载过的classes
     *
     * @return array
     */
    final public static function getLoadedClasses()
    {
        return self::$loadedClasses;
    }

    /**
     * 检测是否已经加载过指定的class
     *
     * @param string $class
     * @return bool
     */
    final public static function isLoadedClass($class)
    {
        $class = ltrim($class, PATH_NOISE);
        return isset(self::$loadedClasses[$class]) && self::$loadedClasses[$class] !== false;
    }

    /**
     * 取得全局的语言实例
     *
     * @return \Easy_Language
     */
    final public static function getLang()
    {
        return self::$lang;
    }

    /**
     * 4.0新添加，返回全局的时区实例
     *
     * @return \DateTimeZone
     */
    final public static function getTimeZone()
    {
        return self::$timezone;
    }

    /**
     * 判断当前环境是否已经分发了
     *
     * @return bool
     */
    final public static function isDispatch()
    {
        return self::$isDispatch;
    }

    final public static function dispatch(Http\Request $request = null)
    {
        if (self::$isDispatch)
            return false;
        BaseController::invoke(Parameters::getInstance()->routing($request));
        // 去掉这一层Try Catch，简化一层捕捉
//        try {
//            BaseController::invoke(Router::getInstance('routes')->routing($request));
//        } catch (\Exception $ex) {
//            throw new Action\Exception('action.dispatch_error', 0, $ex);
//        }
        return true;
        /*
        $context = self::getContext();
        $result = $context->getRouter()->routing($context->getRequest());
        $params = new Agi_Action_Parameters($result);
        $context->setParams($params);

        Agi_Action_Controller::getInstance($params);
        */
    }


    /**
     * 取得PHP mbstring所支持的编码列表
     *
     * @return array
     */
    final public static function getEncodings()
    {
        if (!isset(self::$encodings)) {
            self::$encodings = array_fill_keys(mb_list_encodings(), true);
        }
        return self::$encodings;
    }

    /**
     * 过滤传入的编码名称，如果属于本地支持的列表，则返回编码本身，否则就返回默认编码
     *
     * @param string $encode
     * @return string
     */
    final public static function filterEncode($encode)
    {
        $encode = strtoupper($encode);
        if (!isset(self::$encodings))
            self::getEncodings();
        return isset(self::$encodings[$encode]) ? $encode : self::DEFAULT_ENCODE;
    }

    public static function mkHttpVerCode($code)
    {
        return String::encrypt(array(microtime(true), $code, PROJECT_HASH), HTTP_V_HASH);
    }

    public static function parseHttpVerCode($cipher)
    {
        return String::decrypt($cipher, HTTP_V_HASH);
    }

    public static function validHttpVerCode($cipher, $compareCode, $expire = 0)
    {
        list($timestamp, $rawCode, $projectHash) = static::parseHttpVerCode($cipher);
        // 满足ProjectHash和code比较后，才进一步比较事件差异
        if ($projectHash === PROJECT_HASH && $rawCode === $compareCode) {
            $expire = !is_numeric($expire) || $expire < 0 ? 0 : round(floatval($expire), 4);
            // 呃，精确到毫秒级的验证——有必要嘛？不知道
            if ($expire > 0) {
                $diff = round(microtime(true) - $timestamp);
                return $diff <= $expire;
            }
            return true;
        }
        return false;
    }

    public static function warning($msg, array $args = null)
    {
        $msg = static::getLang()->deepSub($msg, $args);
        if (static::isMode(self::WEB)) {
            echo "<div class=\"agi-warning\"><strong>{$msg}</strong>";
            if (APP_ENV === self::ENV_DEV) {
                $debug = debug_backtrace();
                array_shift($debug);
                traceDebug($debug);
            }
            echo '</div>';
        }
        else {
            echo $msg;
        }
    }

    public static function getLogPath($file)
    {
        return PROJECT_DIR . "/log/" . ext($file, 'log');
    }

    public static function log()
    {
        $number = func_num_args();
        if ($number <= 1)
            return false;
        $args = func_get_args();
        $handle = array_shift($args);
        $buffer = array();
        foreach ($args as $item) {
            $item = preg_replace('#[\r\n\t]#m', '', var_export($item, 1));
            $buffer[] = $item;
        }

        self::$logBuffers[$handle][] = implode("\r\n", $buffer);
        return true;
    }

    public static function getLogBuffers($handle, $isRemove = false)
    {
        $buffer = array();
        if (isset(self::$logBuffers[$handle])) {
            $buffer = self::$logBuffers[$handle];
            if ($isRemove)
                unset(self::$logBuffers[$handle]);
        }
        return $buffer;
    }

    public static function handleLogBuffers(array & $buffers)
    {
        $dt = date('Y-m-d H:i:s', NOW_TS);
        $head = sprintf("[%s][%s]", PHP_SAPI, $dt);
        if (PHP_SAPI !== self::CLI) {
            $req = Request::current();
            $head .= ' ' . $req->method . ' ' . $req->url;
            if ($req->method === 'POST')
                $head .= PHP_EOL . $req->getRawPost();
        } else {
            $head .= ' ' . implode(' ', $_SERVER['argv']);
        }
        $close = str_repeat('=', 80);
        foreach ($buffers as $handle => $buffer) {
            $file = static::getLogPath($handle);
            $dir = dirname($file);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);
            $content = $head . PHP_EOL . implode("\r\n", $buffer) . PHP_EOL . $close . PHP_EOL;
            file_put_contents($file, $content, FILE_APPEND);
        }
    }

}