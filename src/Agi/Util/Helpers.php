<?php

namespace Agi\Util;

use Agi\Action\BaseController;
use Agi\Action\Parameters;
use Agi\Html\Builder\Html;
use Agi\Http\Request;
use Agi\Output\Responder;
use Agi\Output\ViewRenderer;

/**
 * Class ControllerHelper
 *
 * @package Agi\Util
 * @author easy created at 2015/2/4 15:26
 */
abstract class ControllerHelper
{

    /** @var BaseController */
    protected $controller;

    /** @var Request */
    protected $req;

    /**
     * @var Parameters
     */
    protected $params;

    final public function __construct(BaseController $controller)
    {
        $this->controller = $controller;
        $this->req        = $controller->getRequest();
        $this->params     = $controller->getParams();
        $this->onConstruct();
    }

    abstract protected function onConstruct();

    /**
     * @return BaseController
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return Request
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
        if ($this->controller->isRender())
            return $this->controller->getRenderer();
        return false;
    }

    public function isRender()
    {
        return $this->controller->isRender();
    }

    public function isViewRender()
    {
        $renderer = $this->getRenderer();
        if ($renderer !== false && $renderer instanceof ViewRenderer)
            return true;
        return false;
    }

    public function getHtml()
    {
        if ($this->isViewRender()) {
            return $this->getRenderer()->html();
        }
        return new Html();
    }
}

 