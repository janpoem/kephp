<?php

namespace Agi\Action;

use Agi\Route\Router;
use App;
use Agi\Http\Request;
use Agi\Http\Response;
use Agi\Route\Result;

/**
 * Class Parameters
 *
 * @package Agi\Action
 * @author Janpoem<janpoem@163.com>
 *
 * 常见属性
 * @property \Agi\Http\Request $request
 * @property string $namespace
 * @property string $controller
 * @property string $action
 * @property string $tail
 * @property int $id
 * @property string $name
 */
class Parameters
{

    /** @var Parameters */
    private static $instance = null;

    /** @var string 这个用来判断是否在某个controller */
    private $controllerName = 'index';

    /** @var string controller的真实className */
    private $controllerClass = 'index_controller';

    /** @var string controller的真实存放路径，用于越过parseClass的流程 */
    private $controllerPath = null;

    private $viewFile = 'index/index';

    /** @var \Agi\Route\Result */
    protected $result = null;

    /** @var \Agi\Route\Router */
    protected $router = null;

    /** @var bool 是否已经分发 */
    protected $isRouted = false;

    /** @var bool 是否已经命中Url Router */
    protected $isMatched = false;

    protected $matches = array();

    /** @var \Agi\Http\Request */
    protected $request = null;

    /** @var array url匹配后的尾部值 */
    protected $tailParse = false;

    protected $data = array(
        'namespace'  => null,
        'controller' => 'index',
        'action'     => 'index',
        'tail'       => null,
    );

    public static function filterName($name, $default = null)
    {
        $name = trim(mb_strtolower($name), '-_\\/.');
        if (empty($name))
            return $default;
        return str_replace(array('-', '.'), '_', $name);
    }

    /**
     * 改为全局单例
     *
     * @return Parameters
     */
    final public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $class = get_called_class();
            self::$instance = new $class();
        }
        return self::$instance;
    }

    final private function __construct()
    {

    }

    /**
     * 设置HttpRequest，只允许在Routing以前设置
     *
     * @param Request $request
     * @return Parameters
     */
    public function setRequest(Request $request)
    {
        if (!$this->isRouted)
            $this->request = $request;
        return $this;
    }

    /**
     * 取得Params的HttpRequest
     *
     * @return Request
     */
    public function getRequest()
    {
        if (!isset($this->request) || !($this->request instanceof Request))
            $this->request = Request::current();
        return $this->request;
    }

    /**
     * 取得Response实例，HttpResponse是全局单例的
     *
     * @return Response
     */
    public function getResponse()
    {
        return Response::getInstance();
    }

    /**
     * 设置Router，需要在Routing以前才可以设置
     *
     * @param Router $router
     * @return Parameters
     */
    public function setRouter(Router $router)
    {
        if (!$this->isRouted)
            $this->router = $router;
        return $this;
    }

    /**
     * 取得当前的Router
     *
     * @return Router
     */
    public function getRouter()
    {
        if (!isset($this->router) || !($this->router instanceof Router))
            $this->router = Router::getInstance('routes');
        return $this->router;
    }

    /**
     * 判断是否已经分发了
     *
     * @return bool
     */
    public function isRouted()
    {
        return $this->isRouted;
    }

    public function routing(Request $request = null)
    {
        if ($this->isRouted)
            return $this;
        // 如果指定了Request对象，则以这个对象为当前Parameters的Request
        if (isset($request))
            $this->setRequest($request);
        else // 否则，则取得当前的Request实例
            $request = $this->getRequest();
        $this->isRouted = true;
        $this->setRouteResult($this->getRouter()->routing($request));
        return $this;
    }

    /**
     * 检查当前Routed的结果是否命中Routes设定
     *
     * @return bool
     */
    public function isMatched()
    {
        return $this->isMatched;
    }

    /**
     * 设置Route匹配的结果
     *
     * 这个方法应该只被执行一次
     *
     * @param Result $result
     * @return Parameters
     */
    protected function setRouteResult(Result $result)
    {
        $this->result = $result;
        $this->isMatched = $result->matched;
        $this->matches = $result->matches;
        $params = $result->params;
        if (!empty($params)) {
            if (isset($params['namespace']))
                $params['namespace'] = $this->filterName($params['namespace'], null);
            if (isset($params['controller']))
                $params['controller'] = $this->filterName($params['controller'], 'index');
            if (isset($params['action']))
                $params['action'] = $this->filterName($params['action'], 'index');
            $this->data = array_merge($this->data, $params);
        }
        if (!empty($this->data['controller'])) {
            if (!empty($this->data['namespace'])) {
                $this->controllerName = "{$this->data['namespace']}/{$this->data['controller']}";
                $this->controllerClass =
                    strtr($this->data['namespace'], DS, '_') . "_{$this->data['controller']}_controller";
            } else {
                $this->controllerName = $this->data['controller'];
                $this->controllerClass = "{$this->data['controller']}_controller";
            }
            $this->viewFile = "{$this->controllerName}/{$this->data['action']}"; // namespace/controller/action
        }
        return $this;
    }

    final public function __get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    final public function getData()
    {
        return $this->data;
    }

    final public function getControllerData()
    {
        if (!isset($this->controllerPath))
            $this->controllerPath = App::getControllerPath($this->controllerName);
        return array($this->controllerName, $this->controllerClass, $this->controllerPath);
    }

    final public function getActionName()
    {
        return "{$this->controllerName}#{$this->data['action']}";
    }

    public function getViewFile()
    {
        return $this->viewFile;
    }

    public function tail($index = -1, $default = null)
    {
        if ($index < 0)
            return $this->data['tail'];
        if ($this->tailParse === false) {
            if (empty($this->data['tail']))
                $this->tailParse = array();
            else
                $this->tailParse = explode('/', $this->data['tail']);
        }
        return isset($this->tailParse[$index]) ? $this->tailParse[$index] : $default;
    }

    public function isCtrl($name, $true = null, $false = null, $isRender = false)
    {
        $is = $this->controllerName === $name;
        if (isset($true))
            $is = $is ? $true : $false;
        if ($isRender)
            echo $is;
        return $is;
    }

    public function isAct($name, $true = null, $false = null, $isRender = false)
    {
        $is = "{$this->controllerName}#{$this->data['action']}" === $name;
        if (isset($true))
            $is = $is ? $true : $false;
        if ($isRender)
            echo $is;
        return $is;
    }

    public function inAct(array $actions, $true = null, $false = null, $isRender = false)
    {
        $compare = "{$this->controllerName}#{$this->data['action']}";
        $is = false;
        foreach ($actions as $action) {
            if ($action === $compare) {
                $is = true;
                break;
            }
        }
        if (isset($true))
            $is = $is ? $true : $false;
        if ($isRender)
            echo $is;
        return $is;
    }
}