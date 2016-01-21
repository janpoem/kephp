<?php

namespace Agi\Output;

use Agi\Util\String;


/**
 * Class TextFormatter
 *
 * 文本排版器
 *
 * @package Agi\Output
 * @author Janpoem created at 2014/9/26 22:08
 */
class TextFormatter implements Responder
{

    private $content = '';

    private $replacement = array();

    private $formatter = null;

    public function __construct($content = null, array $replacement = null, $formatter = null)
    {
        $this->content = $content;
        if (!empty($replacement))
            $this->replacement = $replacement;
        $this->formatter = $formatter;
    }

    public function assign($key, $value = null)
    {
        // TextWriter 就不需要assign了
        return $this;
    }

    public function getContent()
    {
        if (is_callable($this->formatter)) {
            return call_user_func($this->formatter, $this->content, $this->replacement);
        } elseif (!empty($this->replacement)) {
            return String::deepSub($this->content, $this->replacement);
        }
        return String::from($this->content);
    }

    public function output()
    {
        echo $this->getContent();
        return $this;
    }
}

