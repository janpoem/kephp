<?php

namespace Agi\Output;

use App;
use Tidy;
use Agi\Html\Component;
use Agi\Action\Parameters;

/**
 * Class ViewRenderer
 *
 * @package Agi\Output
 * @author Janpoem created at 2014/9/25 12:38
 */
class ViewRenderer extends Component implements Responder
{

    private $_mode = self::VIEW;

    private $_file = null;

    private $_locals = array();

    /** @var Parameters */
    protected $params = null;

    /** @var \Agi\Http\Request */
    protected $req = null;

    public $layout = null;

    public $title = '';

    public function  __construct($mode, $file, array $locals = null)
    {
        $this->params = Parameters::getInstance();
        $this->req = $this->params->getRequest();
        $this->setRender($mode, $file, $locals);
    }

    public function getReq()
    {
        return $this->req;
    }

    public function getParams()
    {
        return $this->params;
    }

    /**
     * 设置输出模式和文件
     *
     * @param int $mode
     * @param string $file
     * @param array $locals
     * @return $this
     */
    public function setRender($mode, $file, array $locals = null)
    {
        if ($this->isOutput())
            return $this;
        if ($mode !== self::WIDGET && $mode !== self::VIEW)
            $mode = self::VIEW;
        $this->_mode = $mode;
        if (!empty($file) && is_string($file))
            $this->_file = $file;
        if (isset($locals))
            $this->_locals = $locals;
        return $this;
    }

    /**
     * 绑定本地变量
     *
     * @param object|array|string $key
     * @param mixed $value
     * @return $this
     */
    public function assign($key, $value = null)
    {
        if (!empty($key)) {
            $type = gettype($key);
            if ($type === PHP_OBJ) {
                $type = PHP_ARY;
                $key = get_object_vars($key);
            }
            if ($type === PHP_ARY) {
                foreach ($key as $k => $v) {
                    if (!empty($k) && is_string($k) && $k[0] !== '_')
                        $this->$k = $v;
                }
            } elseif ($type === PHP_STR && $key[0] !== '_') {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
     * 取得常量标记的名称
     *
     * @param string $flag
     * @return string
     */
    final public function getFlag($flag)
    {
        $hash = spl_object_hash($this);
        return "ViewRenderer_{$hash}_{$flag}";
    }

    /**
     * 检查当前Renderer是不是已经输出
     *
     * @return bool
     */
    public function isOutput()
    {
        return defined($this->getFlag('OUTPUT')) || defined($this->getFlag('OUTPUT_CONTENT'));
    }

    /**
     * 检查当前Renderer是不是已经输出了内容
     *
     * @return bool
     */
    public function isOutputContent()
    {
        return defined($this->getFlag('OUTPUT_CONTENT'));
    }

    /**
     * 执行输出接口
     *
     * @return $this
     */
    public function output()
    {
        if ($this->isOutput())
            return $this;
        define($this->getFlag('OUTPUT'), true, true);
        if (!empty($this->layout) && is_string($this->layout)) {
            if ($this->import(self::LAYOUT, $this->layout) === false) {
                $this->content();
            }
        } else {
            $this->content();
        }
        return $this;
    }

    /**
     * 输出内容
     *
     * @return $this
     */
    public function content()
    {
        if ($this->isOutputContent())
            return $this;
        define($this->getFlag('OUTPUT_CONTENT'), true, true);
        if (!empty($this->_file))
            $this->import($this->_mode, $this->_file, $this->_locals);
        $buffer = Buffer::getInstance();
        if ($buffer->isRender()) {
            $logBuffer = App::getLogBuffers(App::WEB, true);
            if (!empty($logBuffer))
                echo '<div class="agi-log-buffer"><span class="agi-buffer-bar"></span><pre>', implode('<hr />', $logBuffer), '</pre></div>';
            if (!$buffer->isEmpty())
                echo '<div class="agi-output-buffer"><span class="agi-buffer-bar"></span>', $buffer->mkString(), '</div>';

        }
        return $this;
    }
}
