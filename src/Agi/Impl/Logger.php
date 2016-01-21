<?php

namespace Agi\Impl;


/**
 * Class Logger
 *
 * @package Agi\Impl
 * @author Janpoem created at 2014/9/27 3:07
 */
interface Logger {

    /** 错误等级，可以无限制的输出 */
    const ERROR = 0;

    /** 信息输出 */
    const INFO = 1;

    /** 警告输出 */
    const WARN = 2;

    /** 调试输出 */
    const DEBUG = 3;

    /** 最低等级的输出 */
    const MIN_LEVEL = self::INFO;

    /**
     * 设置日志等级，应该限制日志等级最低不得低于MIN_LEVEL
     *
     * @param $level
     * @return $this
     */
    public function setLevel($level);

    public function trace($msg, $level = self::MIN_LEVEL);

    public function error($msg);

    public function warn($msg);

    public function debug($msg);

    public function info($msg);

}