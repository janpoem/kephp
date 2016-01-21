<?php

namespace Agi\Action;

use Agi\Http\Response;
use Agi\Output\Responder;
use Agi\Output\TextRenderer;
use Agi\Output\TextFormatter;
use Agi\Output\ViewRenderer;
use Agi\Output\UrlRedirection;
use Agi\Util\String;

/**
 * Class BaseController
 *
 * @package Agi\Action
 * @author Janpoem created at 2014/9/25 12:03
 */
class BaseController
{

    const ACTION_INIT = 0;

    const ACTION_FOUND = 1;

    const ACTION_DENIED = 2;

    const ACTION_MISSED = 3;

    const ACTION_COMPLETE = 4;

    const ACTION_RENDER = 5;

    private static $instances = array();

    private $executedActions = array();

    private $actionStatus = self::ACTION_INIT;

    /** @var Parameters */
    protected $params = null;

    /** @var \Agi\Http\Request */
    protected $req = null;

    /** @var \Agi\Http\Response */
    protected $resp = null;

    /** @var \Agi\Output\Responder */
    protected $renderer = false;

    public $title = '';

    public $layout = false;

    public $format = 'html';

    /**
     * @param Parameters $params
     * @return BaseController
     * @throws Exception
     */
    public static function invoke(Parameters $params)
    {
        if (HTTP_STRICT_MATCH && !$params->isMatched())
            throw new Exception(array('action.unmatched', $params->getRequest()->purePath));
        list(, $class, $path) = $params->getControllerData();
        if (empty($class) || empty($path))
            throw new Exception('action.invalid_ctrl_name');
        if (!isset(self::$instances[$class])) {
            \App::loadClass($class, $path); // 越过App::parseClass的流程
            if (!class_exists($class, false))
                throw new Exception(array('action.no_ctrl', $class));
            $ref = new \ReflectionClass($class);
            if (!is_subclass_of($class, __CLASS__) || $ref->isAbstract())
                throw new Exception(array('action.invalid_ctrl', $class));
            self::$instances[$class] = new $class($params);
            self::$instances[$class]->action($params->action);
        }
        return self::$instances[$class];
    }

    /**
     * 这个接口主要提供给抽象Controller做初始化使用，Controller/Base会占用__construct，
     * 后继的类需要自己定制onConstruct接口来实现一些后继的处理
     */
    protected function onConstruct()
    {

    }

    /**
     * 构建函数，由Parameters构建一个Controller的实例
     *
     * @param Parameters $params
     */
    final private function __construct(Parameters $params)
    {
        $this->params = $params;
        $this->req    = $params->getRequest();
        $this->resp   = $params->getResponse();
        $this->title  = $params->getActionName();
        $this->onConstruct();
    }

    /**
     * @param $action
     * @throws Exception
     */
    protected function onMissing($action)
    {
        throw new Exception(array('action.missing', $this->params->getActionName()), 404);
    }

    protected function onRender(Responder $responder)
    {
    }

    final protected function action($action)
    {
        // 方法不存在，会进入onMissing流程，empty action，重复执行，才是致命错误，需要立刻退出
        if (empty($action))
            throw new Exception('action.invalid_name');
        if (isset($this->executedActions[$action]))
            throw new Exception(array('action.executed', $action));
        $return = null;
        if ($this->actionStatus === self::ACTION_INIT) {
            $this->actionStatus = self::ACTION_FOUND;
            if (!method_exists($this, $action))
                $this->actionStatus = self::ACTION_MISSED; // action不存在
            else {
                $ref = new \ReflectionMethod($this, $action);
                if (!$ref->isPublic() || $ref->isStatic()) {
                    $this->actionStatus = self::ACTION_DENIED; // 拒绝访问
                }
            }
            if ($this->actionStatus !== self::ACTION_FOUND)
                $return = $this->onMissing($action);
            else
                $return = $this->$action();
            if ($return === false)
                $this->renderNone();
        }
        if ($this->actionStatus < self::ACTION_COMPLETE) {
            $this->complete($return);
        }
        return $this;
    }

    protected function getDefaultResponder($return = null)
    {
        if (empty($return))
            $return = $this->params->getViewFile();
        return $this->renderView($return);
    }

    final protected function complete($return = null)
    {
        if ($this->actionStatus < self::ACTION_COMPLETE) {
            $this->actionStatus = self::ACTION_COMPLETE;
            if (!($return instanceof Responder))
                $return = $this->getDefaultResponder($return);
            if ($return instanceof Responder && $this->actionStatus < self::ACTION_RENDER) {
                $this->actionStatus = self::ACTION_RENDER;
                $this->renderer     = $return;
                $return->assign($this); // 最后输出的时候才把当前的Controller绑定给renderer
                $this->onRender($return);
                $this->resp->setFormat($this->format)->respond($return);
            } else {
                throw new Exception(array('action.invalid_render', $this->params->getActionName()));
            }
        }
        return $this;
    }

    protected function redirect($url, array $query = null)
    {
        $redirection = new UrlRedirection($url, $query);
        $this->complete($redirection);
        return $redirection;
    }

    protected function renderWidget($widget, array $locals = null)
    {
        $renderer = new ViewRenderer(ViewRenderer::WIDGET, $widget, $locals);
        $this->complete($renderer);
        return $renderer;
    }

    protected function renderView($view)
    {
        $renderer = new ViewRenderer(ViewRenderer::VIEW, $view);
        $this->complete($renderer);
        return $renderer;
    }

    protected function renderNone()
    {
        $renderer = new ViewRenderer(ViewRenderer::VIEW, null);
        $this->complete($renderer);
        return $renderer;
    }

    protected function renderText($content, $format = 'txt')
    {
        $this->format = $format;
        $renderer     = new TextRenderer($content);
        $this->complete($renderer);
        return $renderer;
    }

    protected function renderFormat($content, array $replacement = null, $format = 'txt', $formatter = null)
    {
        $this->format = $format;
        $renderer     = new TextFormatter($content, $replacement, $formatter);
        $this->complete($renderer);
        return $renderer;
    }

    protected function renderJson($data)
    {
        if (!is_string($data))
            $data = json_encode($data);
        return $this->renderText($data, 'json');
    }

    protected function renderJsonStatus($status, $message = null, array $data = null)
    {
        if (!($status instanceof \Easy_Status))
            $status = new \Easy_Status(empty($status) || !$status ? false : true, String::from($message), $data);
        return $this->renderText($status->toJSON(), 'json');
    }

    protected function sendXhrStatus($status)
    {
        if ($this->req->isXHR)
            Response::getInstance()->addHeader('X-Responded-Status: ' . intval($status));
        return $this;
    }

    protected function setHttpStatus($code)
    {
        Response::getInstance()->setStatusCode($code);
        return $this;
    }

    /**
     * @return \Agi\Http\Request
     */
    public function getRequest()
    {
        return $this->req;
    }

    /**
     * @return Parameters
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return Responder|bool
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    public function isRender()
    {
        return isset($this->renderer) && ($this->renderer instanceof Responder);
    }
}
