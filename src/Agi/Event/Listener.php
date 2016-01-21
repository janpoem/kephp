<?php
/**
 * Created by IntelliJ IDEA.
 * User: Janpoem
 * Date: 2015/3/20
 * Time: 12:06
 */

namespace Agi\Event;

/**
 * 事件监听器
 *
 * @package Agi\Event
 */
class Listener
{
    /** 存储状态的字段 */
    const FIELD_STATUS = 0;
    /** 存储是否只执行一次的字段 */
    const FIELD_ONCE = 1;
    /** 存储执行次数的字段 */
    const FIELD_TIMES = 2;
    /** 存储处理函数的字段 */
    const FIELD_HASH = 3;

//    /** 存储是否只执行一次的字段 */
//    const FIELD_ONCE = 2;
//    /** 存储执行次数的字段 */
//    const FIELD_TIMES = 1;
//    /** 存储处理函数的字段 */
//    const FIELD_HANDLE = 0;

    /** 无效的类型 */
    const TYPE_INVALID = -1;
    /** 闭包类型 */
    const TYPE_CLOSURE = 0;
    /** 可执行的函数 */
    const TYPE_FUNCTION = 1;
    /** 静态类的方法 */
    const TYPE_CLASS_METHOD = 2;
    /** 对象的方法 */
    const TYPE_OBJECT_METHOD = 3;

    /** @var array 事件注册容器 */
    protected $_events = array();

    /** @var array callback的容器 */
    protected $_callbacks = array();

    /** @var array 调试数据的容器 */
    protected $_traces = array();

    /** @var array 已经被屏蔽的事件容器 */
    protected $_disabledEvents = array();

    /**
     * 添加调试的数据
     *
     * @param string $name
     * @param mixed  $message
     *
     * @return $this
     */
    public function trace($name, $message)
    {
        if (APP_ENV === \App::ENV_PRO)
            return $this;
        if (empty($name))
            return $this;
        if (!isset($this->_traces[$name]))
            $this->_traces[$name] = array();
        $this->_traces[$name][] = $message;
        return $this;
    }

    /**
     * 过滤事件名
     *
     * @param string $name
     * @param bool   $firstUpper
     * @param string $spr
     *
     * @return array
     */
    public function filterName($name, $firstUpper = false, $spr = '[\_\-\.\s\t]')
    {
        if (empty($name))
            return array(null, null);
        $name = strtolower($name);
        $once = null;
        if (strpos($name, 'once') === 0) {
            $name = substr($name, 4);
            $once = true;
        } elseif (strpos($name, 'on') === 0) {
            $name = substr($name, 2);
            $once = false;
        }
        $name = preg_replace_callback("#({$spr}+([a-z])?)#", function ($matches) {
            if (isset($matches[2])) {
                return strtoupper($matches[2]);
            }
            return '';
        }, $name);
        $name[0] = $firstUpper ? strtoupper($name[0]) : strtolower($name[0]);
        return array($name, $once);
    }

    /**
     * 检查是否有指定名称的事件
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasEvent($name)
    {
        list($name,) = $this->filterName($name);
        if (isset($this->_events[$name]) && !isset($this->_disabledEvents[$name]))
            return true;
        return false;
    }

    /**
     * 取得某个事件名称的所有事件队列
     *
     * @param string $name
     *
     * @return array|false
     */
    public function getEvent($name)
    {
        if (empty($name))
            return false;
        list($name,) = $this->filterName($name);
        return isset($this->_events[$name]) ? $this->_events[$name] : false;
    }

    /**
     * 取得所有已经注册的事件，这里不考虑事件是否被移除
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->_events;
    }


    /**
     * 注册事件
     *
     * <code>
     * class UserEvents {
     *
     *     public static function register() {
     *
     *     }
     * }
     *
     * function register() {
     * }
     *
     * class PostEvents {
     *
     *     public function changeContent() {
     *
     *     }
     *
     *     public function saveSuccess() {
     *
     *     }
     * }
     *
     * $listener = new Listener();
     *
     * // 注册一个闭包
     * $listener->on('test', function(Emitter $emit) {
     *     // do something
     * });
     *
     * // 注册一个静态类的具体方法
     * $listener->on('register', 'UserEvents::register');
     * // 注册到一个静态类，方法名为时间名，即register
     * $listener->on('register', 'UserEvents::'); // 注意这个注册方法不会生效，因为一个方法只会注册一次，而不能重复注册
     * // 注册到一个叫做register的全局函数上
     * $listener->on('register', 'register');
     *
     * // 注册一个实例类的方法
     * $postEvents = new PostEvents();
     * // 注册到 changeContent 的方法
     * $listener->on('changeContent', $postEvents);
     * // 注册到 saveSuccess 的方法
     * $listener->on('saveSuccess', $postEvents);
     * </code>
     *
     * @param string                       $name 事件名称，如果传入的名称为onEventName, onceEventName，会自动去掉on和once
     * @param string|array|object|\Closure $fn   事件的执行函数，
     *                                           字符串格式，允许'function_name', 'Class::method'
     *                                           数组格式，允许[object, 'method'], ['Class', 'method']
     *                                           闭包。
     * @param bool                         $once 是否只触发一次
     *
     * @return $this|Listener
     */
    public function on($name, $fn, $once = false)
    {
        // 时间名不得为空，且必须为字符串类型
        // callback也不得为空
        if (empty($name) || !is_string($name) || empty($fn))
            return $this;
        list($name, $nameOnce) = $this->filterName($name);
        // 如果事件的名称中，定义了on or once，则用事件名称来覆盖本地的$once变量
        if (isset($nameOnce))
            $once = $nameOnce;
        // 常用的变量
        $once = !empty($once);
        $type = gettype($fn);
        $hash = null;
        $fnType = self::TYPE_INVALID;

        // 注册事件空数组
        if (!isset($this->_events[$name]))
            $this->_events[$name] = array();

        // 对象类型，优先处理
        if ($type === PHP_OBJ) {
            $objHash = spl_object_hash($fn);
            $class = get_class($fn);
            if ($fn instanceof \Closure) {
                // 闭包是最常见的方式注册事件
                $hash = "{$class}#{$objHash}"; // 同一个闭包，不能用于同时注册多个事件
                $fnType = self::TYPE_CLOSURE;
            } elseif (method_exists($fn, $name) && is_callable([$fn, $name])) {
                // 任意对象类型，以事件名称作为调用的方法名称，只要方法存在，且可以执行
                $fn = [$fn, $name];
                $hash = "{$class}#{$objHash}->{$name}"; // 一个对象实例的方法，只能注册一个事件
                $fnType = self::TYPE_OBJECT_METHOD;
            }
        } else {
            // 其它类型的fn，要先进行一个过滤
            // 之后再检查是否可执行的方法或函数
            if ($type === PHP_STR) {
                if (strpos($fn, '::') !== false) {
                    $fn = explode('::', $fn);
                    $type = PHP_ARY; // 擦写这个类型的值，交给下一个流程去处理
                } else {
                    $hash = $fn; // 全局函数，一个事件只能注册一个
                    $fnType = self::TYPE_FUNCTION;
                }
            }
            // 无效的类型，且为数组的格式，其它的就不管了
            if ($type === PHP_ARY) {
                if (empty($fn[0])) {
                    // 主体对象为空？那就是全局的函数咯
                    $fn = $fn[1];
                    $hash = $fn; // 全局函数，一个事件只能注册一个
                    $fnType = self::TYPE_FUNCTION;
                } elseif (empty($fn[1])) {
                    $fn[1] = $name;
                    $hash = implode('::', $fn); // 静态类方法
                    $fnType = self::TYPE_CLASS_METHOD;
                } else {
                    $hash = implode('::', $fn); // 静态类方法
                    $fnType = self::TYPE_CLASS_METHOD;
                }
            }
            // 最后统一检查是否可执行
            if ($fnType !== self::TYPE_INVALID) {
                if (!is_callable($fn))
                    $fnType = self::TYPE_INVALID;
            }
        }

        if ($fnType !== self::TYPE_INVALID) {
            $rawHash = hash('crc32b', $hash);
            if (isset($this->_callbacks[$rawHash]))
                return $this->trace($name, "Event {$hash} was defined!");
            $this->_events[$name][] = array(
                self::FIELD_STATUS => 1,
                self::FIELD_ONCE => $once,
                self::FIELD_TIMES => 0,
                self::FIELD_HASH => $rawHash,
            );
            $this->_callbacks[$rawHash] = $fn;
        }

        return $this;
    }

    /**
     * 注册一个只执行一次的事件
     *
     * @param string                       $name 事件名称，如果传入的名称为onEventName, onceEventName，会自动去掉on和once
     * @param string|array|object|\Closure $fn   事件的执行函数，
     *                                           字符串格式，允许'function_name', 'Class::method'
     *                                           数组格式，允许[object, 'method'], ['Class', 'method']
     *                                           闭包。
     *
     * @return $this|Listener
     */
    public function once($name, $fn)
    {
        return $this->on($name, $fn, true);
    }

    /**
     * 绑定多个事件
     *
     * @param array $events
     *
     * @return $this|Listener
     */
    public function bind(array $events)
    {
        foreach ($events as $name => $handle) {
            $this->on($name, $handle);
        }
        return $this;
    }


    /**
     * 禁用指定名称的事件
     *
     * @param string $name
     *
     * @return $this
     */
    public function disable($name)
    {
        list($name,) = $this->filterName($name);
        if (isset($this->_events[$name]) && !isset($this->_disabledEvents[$name]))
            $this->_disabledEvents[$name] = true;
        return $this;
    }

    /**
     * 启用指定名称的事件
     *
     * @param string $name
     *
     * @return $this
     */
    public function enable($name)
    {
        list($name,) = $this->filterName($name);
        if (isset($this->_events[$name]))
            $this->_disabledEvents[$name] = false;
        return $this;
    }

    /**
     * 激活指定名称的事件
     *
     * @param string $name
     * @param mixed  $args 执行事件的参数，如果不是一个数组，则会转换为一个数组，如果要传多个参数，请手动指定一个数组的格式
     *
     * @return $this
     */
    public function emit($name, $args = null)
    {
        $args = (array)$args; // 转为数组格式
        list($name,) = $this->filterName($name);
        if (isset($this->_events[$name]) && !isset($this->_disabledEvents[$name])) {
            $emitter = new Emitter($name, $args);
            foreach ($this->_events[$name] as $index => $settings) {
                list($status, $isOnce, $times, $hash) = $settings;
                ////////////////////////////////////////////////////////////////////////
                if (!isset($this->_callbacks[$hash]) || $emitter->break || ($isOnce && $times > 0) || $status === 0) {
                    continue;
                }
                // 返回结果暂时预留
                call_user_func($this->_callbacks[$hash], $emitter);
                // 计数器+1
                $this->_events[$name][$index][self::FIELD_TIMES] += 1;
            }
            unset($emitter);
        }
        return $this;
    }
}