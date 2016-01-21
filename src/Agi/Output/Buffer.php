<?php

namespace Agi\Output;

use App;

/**
 * Class Buffer
 * 输出缓冲控制，从Response中独立出来，CLI下也适用。
 *
 * @package Agi\Output
 * @author Janpoem created at 2014/9/25 12:04
 */
class Buffer
{

    const CLEAN_ALL = -1;

    /** @var Buffer */
    private static $instance = null;

    /** @var bool 是否捕捉buffer */
    protected $isCatch = true;

    protected $buffers = array();

    protected $isRender = null;

    /**
     * @return Buffer
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $class = get_called_class();
            self::$instance = new $class;
        }
        return self::$instance;
    }

    /**
     * 设置是否捕捉缓冲
     *
     * @param boolean $isCache
     * @return $this
     */
    public function setCatch($isCache)
    {
        $this->isCatch = empty($isCache);
        return $this;
    }

    public function isCatch()
    {
        return $this->isCatch;
    }

    public function isRender()
    {
        if (isset($this->isRender))
            return empty($this->isRender);
        return APP_ENV === App::ENV_DEV;
    }

    /**
     * 启动缓冲，允许传入$cleanDeep来控制是否同时清空缓冲
     *
     * @param int $cleanDeep 默认值为0，为0时不清除缓冲，-1时，为全部清除，其他数字为具体清除的层数
     * @return $this
     */
    public function start($cleanDeep = 0)
    {
        if ($cleanDeep !== 0)
            $this->clean($cleanDeep);
        ob_start();
        return $this;
    }

    /**
     * 清除缓冲
     *
     * @param int $deep 清除缓冲的层数
     * @return $this
     */
    public function clean($deep = self::CLEAN_ALL)
    {
        $level = ob_get_level();
        if ($level > 0) {
            if (!is_numeric($deep))
                $deep = -1;
            if ($deep > $level || $deep <= 0)
                $deep = $level;
            while ($deep > 0) {
                $length = ob_get_length();
                if ($length > 0) {
                    if ($this->isCatch)
                        $this->buffers[] = ob_get_contents();
                    ob_end_clean();
                }
                $deep--;
            }
        }
        return $this;
    }

    /**
     * @return int 获得缓冲的层级数
     */
    public function getLevel()
    {
        return ob_get_level();
    }

    /**
     * @return array 取得已经捕获的缓冲
     */
    public function get()
    {
        return $this->buffers;
    }

    public function isEmpty()
    {
        return count($this->buffers) <= 0;
    }

    /**
     * 将所有缓冲组合成字符串输出
     *
     * @param string $spr
     * @return string
     */
    public function mkString($spr = '')
    {
        return implode($spr, $this->buffers);
    }

    public function push(array $buffer)
    {
        $this->buffers[] = '<pre>' .implode('<hr />', $buffer) . '</pre>';
        return $this;
    }
}

 