<?php

namespace Agi\Output;

use \Agi\Util\String;

/**
 * Class TextRenderer
 *
 * 基础的文本输出类型，不管传入内容是什么，都会直接转换为字符串内容输出
 *
 * @package Agi\Output
 * @author Janpoem created at 2014/10/1 10:25
 */
class TextRenderer implements Responder
{

    protected $content = '';

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function assign($key, $value = null)
    {
        return $this;
    }

    public function getContent()
    {
        return String::from($this->content);
    }

    public function output()
    {
        echo $this->getContent();
        return $this;
    }
}
