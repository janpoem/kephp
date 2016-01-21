<?php
/**
 * Created by IntelliJ IDEA.
 * User: Janpoem
 * Date: 2015/3/20
 * Time: 13:26
 */

namespace Agi\Event;

/**
 * 事件触发类
 *
 * @package Agi\Event
 * @author Janpoem <janpoem@163.com>
 *
 * @property bool $break 是否中断事件循环
 * @property string $name 事件名称
 * @property array $args 事件的参数
 */
class Emitter
{
    /** @var bool 事件是否中断 */
    private $isBreak = false;

    /** @var string 事件的名称 */
    public $name = null;

    /** @var array 事件触发的参数容器 */
    public $args = array();

    /**
     * 构造函数
     *
     * @param $name
     * @param array $args
     */
    public function __construct($name, array $args = null)
    {
        $this->name = $name;
        if (isset($args))
            $this->args = $args;
    }

    /**
     * 取得传入的参数
     *
     * <code>
     * $listener = new EventListener();
     * $listener->on('test', function(Emitter $emit) {
     *     var_dump($emit->get(0)); // => 'Hello'
     *     var_dump($emit->get(1)); // => 'World'
     *     var_dump($emit->get('what', true)); // => true
     * });
     * $listener->emit('test', array('Hello', 'World'));
     * </code>
     *
     * @param string|int $keys 查询的字段，可以是 'name', 'name->what', 0
     * @param mixed $default 如果查询不到指定的字段，则返回的默认值
     * @return mixed
     */
    public function get($keys, $default = null)
    {
        if (isset($this->args[$keys]))
            return $this->args[$keys];
        return depthQuery($this->args, $keys, $default);
    }

    /**
     * 中断事件的循环
     *
     * @return $this
     */
    public function doBreak()
    {
        if (!$this->isBreak)
            $this->isBreak = true;
        return $this;
    }

    /**
     * 魔术方法重载
     *
     * @param string $field
     * @return mixed
     */
    public function __get($field)
    {
        if ($field === 'break')
            return $this->isBreak;
        return null;
    }
}