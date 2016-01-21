<?php

namespace Agi\Html;

use Agi\Html\Builder\Html;
use \App;
use \Agi\Action\Parameters;

/**
 * Class Component
 *
 * @package Agi\Html
 * @author Janpoem created at 2014/10/1 11:07
 */
class Component
{

    const WIDGET = -1;

    const LAYOUT = 0;

    const VIEW = 1;

    protected $referenceName = Reference::GLOBAL_REF;

    protected $html = null;


    /**
     * 根据模式，取得目录名称
     *
     * @param int $mode
     * @return string
     */
    public function getDir($mode)
    {
        if ($mode === self::LAYOUT)
            return 'layouts';
        elseif ($mode === self::WIDGET)
            return 'widgets';
        return 'views';
    }

    /**
     * 取得不同模式下的渲染文件路径
     *
     * @param int $mode
     * @param string $file
     * @return string
     */
    public function getPath($mode, $file)
    {
        $isCore = false;
        $isQuery = false;
        if ($file[0] === '@') {
            $isCore = true;
            $file = ltrim($file, '@');
        }
        if ($file[strlen($file) - 1] === '?') {
            $isQuery = true;
            $file = rtrim($file, '?');
        }
        if ($isCore && !$isQuery) {
            $path = AGI_DIR . "/components";
            if ($mode === self::LAYOUT)
                $path .= '/layouts';
            $path .= "/{$file}.phtml";
        } else {
            $path = APP_DIR . DS . $this->getDir($mode) . DS . "{$file}.phtml";
            if ($isQuery && (!is_file($path) || !is_readable($path))) {
                $path = AGI_DIR . "/components";
                if ($mode === self::LAYOUT)
                    $path .= '/layouts';
                $path .= "/{$file}.phtml";
            }
        }
        return $path;
    }

    /**
     * 加载不同模式下的渲染文件
     *
     * @param int $type
     * @param string $file
     * @param array $locals
     * @return bool
     */
    public function import($type, $file, array $locals = null)
    {
        $path = $this->getPath($type, $file);
        if (is_file($path) && is_readable($path)) {
            if ($type === self::WIDGET && !empty($locals))
                extract($locals);
            require $path;
            return $this;
        } else {
            $field = 'view.no_view';
            if ($type === self::LAYOUT) $field = 'view.no_layout';
            elseif ($type === self::WIDGET) $field = 'view.no_widget';
            App::warning('view.warning', array(
                'warning' => "{{$field}}",
                'file'    => $file,
            ));
        }
        return false;
    }


    /**
     * 在Renderer中加载widget
     *
     * @param string $widget
     * @param array $locals
     * @return $this
     */
    public function widget($widget, array $locals = null)
    {
        if (empty($widget) || !is_string($widget))
            return $this;
        $this->import(self::WIDGET, $widget, $locals);
        return $this;
    }

    public function form($data, array $options = null, $isRender = false)
    {
        $form = new Form($this, $options, $data);
        if ($isRender)
            $form->render(true);
        return $form;
    }

    public function table($data, array $options = null, $isRender = false)
    {
        $form = new Table($this, $options, $data);
        if ($isRender)
            $form->render(true);
        return $form;
    }

    /**
     * @return Reference
     */
    public function ref()
    {
        return Reference::getInstance($this->referenceName);
    }

    public function requires()
    {
        call_user_func_array(array($this->ref(), 'requires'), func_get_args());
        return $this;
    }

    public function load()
    {
        call_user_func_array(array($this->ref(), 'load'), func_get_args());
        return $this;
    }

    public function loadLib($lib)
    {
        $this->ref()->loadLib($lib);
        return $this;
    }

    public function loadHeader()
    {
        $this->ref()->load(Reference::HEADER);
        return $this;
    }

    public function loadFooter()
    {
        $this->ref()->load(Reference::FOOTER);
        return $this;
    }

    public function js($js)
    {
        $this->ref()->loadJs($js);
        return $this;
    }

    public function css($css, $media = null)
    {
        $this->ref()->loadCss($css, $media);
        return $this;
    }

    public function html()
    {
        if (!isset($this->html) || !($this->html instanceof Html))
            $this->html = new Html();
        return $this->html;
    }

    public function linkTo($context, $href = null)
    {
        return $this->html()->hyperlink($context, $href);
    }

    /**
     * 生成Http verification code
     *
     * @param string $flag
     * @param string $type
     * @param bool $isOutput
     * @return string
     */
    public static function httpVerCode($flag, $type = 'hidden', $isOutput = true)
    {
        $code = App::mkHttpVerCode($flag);
        $field = HTTP_V_FIELD;
        $text = null;
        switch ($type) {
            case 'hidden' :
                $text = "<input type=\"hidden\" name=\"{$field}\" value=\"{$code}\" />";
                break;
            case 'input' :
                $text = "<input type=\"text\" name=\"{$field}\" value=\"{$code}\" />";
                break;
            case 'json' :
                $data = array($field => $code);
                $text = json_encode($data);
                break;
            default :
                $text = $code;
        }
        if ($isOutput)
            echo $text;
        return $text;
    }

}