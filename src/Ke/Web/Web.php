<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Web;


use Throwable;
use ReflectionClass;

use Ke\App;
use Ke\Uri;
use Ke\MimeType;
use Ke\OutputBuffer;
use Ke\Web\Route\Router;
use Ke\Web\Route\Result;

class Web
{

	/** @var Web */
	private static $web = null;

	///////// passed /////////

	/** @var OutputBuffer */
	public $ob = null;

	/** @var App */
	public $app = null;

	/** @var MimeType */
	public $mime = null;

	/** @var Http */
	public $http = null;

	/** @var Component */
	public $component = null;

	protected $defaultController = 'index';

	protected $defaultAction = 'index';

	protected $defaultFormat = 'html';

	protected $controllerNamespace = 'Controller';

	/** @var Controller */
	protected $controller = null;

	protected $helpers = [];

	protected $autoDetectFormats = [];

	protected $statusCode = 200;

	protected $format = 'html';

	protected $headers = [];

	protected $params = [
		'class'      => '',
		'controller' => '',
		'action'     => '',
		'tail'       => '',
		'format'     => '',
		'data'       => [],
	];

	private $requireHelpers = ['string'];

	/** @var Router|null */
	private $router = null;

	/** @var Uri 网站基础的uri */
	private $baseUri = null;

	/** @var Uri 网站资源的uri */
	private $resourceUri = null;

	/** @var bool|Result */
	private $dispatch = false;

	/** @var Renderer */
	private $renderer = false;

	/** @var Context */
	private $context = null;

	/**
	 * @param Http $http
	 * @return Web|static
	 */
	final public static function getWeb(Http $http = null)
	{
		if (!isset(self::$web)) {
			self::$web = new static();
		}
		return self::$web;
	}

	final public function __construct(Http $http = null)
	{
		// 绑定当前的默认的上下文环境实例
		if (!isset(self::$web))
			self::$web = $this;

		$this->ob = OutputBuffer::getInstance()->start('web');
		$this->app = App::getApp();
		$this->mime = $this->app->getMime();
		$this->http = $http ?? Http::current();
		$this->component = (new Component())->setDirs([
			'appView'      => [$this->app->appNs('View'), 100, Component::VIEW],
			'appComponent' => [$this->app->appNs('Component'), 100],
			'keComponent'  => [$this->app->kephp('Ke/Component'), 1000],
		]);

		$this->prepare();

//		exit();
//
//
//		exit();
//
//
//		$this->loader = $this->app->getLoader();
//		if (!empty($this->helpers))
//			$this->loader->loadHelper(...$this->helpers);
//		$this->http = Request::current();
//
//		$this->component = new Component();
//		$this->component->setDirs([
//			'app_view'      => [KE_APP_NS_PATH . '/View', 100, Component::VIEW],
//			'app_component' => [KE_APP_NS_PATH . '/Component', 100],
//			'ke_component'  => [KE_NS_ROOT . '/Component', 1000],
//		]);
//
//		$this->setGlobalNamespace($this->globalNamespace);
//		$this->setDefault($this->defaultController, $this->defaultAction, $this->defaultFormat);
//

	}

	final private function prepare()
	{
		// 加载helpers
		$helpers = $this->requireHelpers;
		if (!empty($this->helpers))
			array_push($helpers, ...$this->helpers);
		$this->loadHelper(...$helpers);
		// 初始化各种属性
		$this->setControllerNamespace($this->controllerNamespace);
		$this->setDefault($this->defaultController, $this->defaultAction);
		$this->setDefaultFormat($this->defaultFormat);
		if (KE_APP_MODE === KE_WEB_MODE) {
			register_shutdown_function(function () {
				$this->onExiting();
			});
			set_error_handler([$this, 'errorHandle']);
			set_exception_handler([$this, 'exceptionHandle']);
		}
	}

	protected function onExiting()
	{

	}

	/**
	 * PHP错误的处理的接管函数
	 */
	public function errorHandle($err, $msg, $file, $line, $context)
	{
		$this->error('Runtime error', [
			'errorType' => error_name($err),
			'message'   => $msg,
			'file'      => $file,
			'line'      => $line,
			'time'      => date('Y-m-d H:i:s'),
			'throw'     => null,
		]);
	}

	/**
	 * @param Throwable $throw
	 */
	public function exceptionHandle(Throwable $throw)
	{
		$this->error('Throwable caught', [
			'errorType' => get_class($throw),
			'message'   => $throw->getMessage(),
			'file'      => $throw->getFile(),
			'line'      => $throw->getLine(),
			'time'      => date('Y-m-d H:i:s'),
			'throw'     => $throw,
		]);
	}

	public function error($title = null, array $vars = null)
	{
		$context = $this->getContext();
		$context->title = $title;
		$layout = empty($context->layout) ? 'default' : $context->layout;
		$context->render('error', $layout, $vars);
		return $this;
	}

	public function loadHelper(...$helpers)
	{
		$this->app->getLoader()->loadHelper(...$helpers);
		return $this;
	}

	###################################################
	# controller, namespace, action, format
	###################################################

	public function setControllerNamespace(string $namespace)
	{
		$namespace = trim($namespace, KE_PATH_NOISE);
		if (empty($namespace))
			$namespace = 'Controller';
		if (!empty(KE_APP_NS))
			$namespace = add_namespace($namespace, KE_APP_NS);
		$this->controllerNamespace = $namespace;
		return $this;
	}

	public function getControllerNamespace(): string
	{
		return $this->controllerNamespace;
	}

	public function filterController(string $controller, bool $returnDefault = true): string
	{
		$controller = strtolower(str_replace(['-', '.', '/'], ['_', '_', '\\'], $controller));
		$namespace = $this->getControllerNamespace();
		if (($namespaceLength = strlen($namespace)) > 0) {
			if (stripos($controller, $namespace . '\\') === 0)
				$controller = substr($controller, $namespaceLength + 1);
			$controller = str_replace('\\', '/', $controller);
		}
		if (empty($controller) && $returnDefault)
			$controller = $this->getDefaultController();
		return $controller;
	}

	public function filterAction(string $action, bool $returnDefault = true): string
	{
		$action = strtolower(str_replace(['-', '.', '/', '\\'], '_', trim($action, '_# ')));
		if (empty($action) && $returnDefault)
			$action = $this->getDefaultAction();
		return $action;
	}

	public function setDefault(string $controller = null, string $action = null)
	{
		if (isset($controller)) {
			$controller = $this->filterController($controller, false);
			if (empty($controller))
				$controller = 'index';
			$this->defaultController = $controller;
			if (!$this->isDispatch())
				$this->params['controller'] = $this->defaultController;
		}
		if (isset($action)) {
			$action = $this->filterAction($action, false);
			if (empty($action))
				$action = 'index';
			$this->defaultAction = $action;
			if (!$this->isDispatch())
				$this->params['action'] = $this->defaultAction;
		}
		return $this;
	}

	public function getDefaultController(): string
	{
		return $this->defaultController;
	}

	public function getDefaultAction(): string
	{
		return $this->defaultAction;
	}

	public function setDefaultFormat(string $format)
	{
		$format = trim(strtolower($format), KE_PATH_NOISE . '.');
		if (empty($format))
			$format = 'html';
		$this->defaultFormat = $format;
		if (!$this->isDispatch())
			$this->params['format'] = $this->defaultFormat;
		return $this;
	}

	public function getDefaultFormat(): string
	{
		return $this->defaultFormat;
	}

	###################################################
	# router, dispatch, getParams
	###################################################

	public function getRouter()
	{
		if (!isset($this->router))
			$this->router = new Router($this->app->config('routes', 'php'));
		return $this->router;
	}

	public function isDispatch()
	{
		return $this->dispatch !== false;
	}

	public function isMatch()
	{
		return $this->dispatch !== false && $this->dispatch->matched;
	}

	public function dispatch()
	{
		if ($this->dispatch !== false)
			return $this;
		$this->dispatch = $this->getRouter()->routing($this->http);
		$params = $this->filterRouterResult($this->dispatch);
		if (!empty($params))
			$this->params = array_merge($this->params, $params);
		$class = $this->getControllerClass();

		if (!empty($this->autoDetectFormats[$this->params['format']]))
			$this->format = $this->params['format'];

		// 种种原因，我们不能允许controller的不命中
		// 因为整个涉及到很多基础的变量的获取
		// todo 但未来版本，还是希望实现不需要class命中的模式

		// 做法1，严格检查controller的class是否存在
		if (!class_exists($class, true))
			throw new \Exception("Controller {$class} not found!");
		if (!is_subclass_of($class, Controller::class))
			throw new \Exception("{$class} is not a controller class!");
		$reflection = new ReflectionClass($class);
		if (!$reflection->isInstantiable())
			throw new \Exception("Class {$class} is not instantiable!");
		/** @var Controller $controller */
		$this->controller = new $class();
		$this->controller->setReflection($reflection);
		$this->controller->action($params['action']);

		// 做法2，即使class不存在，也可以继续往下执行
//		$controller = null;
//		if (class_exists($class, true) && is_subclass_of($class, Controller::class)) {
//
//			if ($reflection->isInstantiable()) {
//				/** @var Controller $controller */
//				$controller = new $class();
//				$controller->setReflection($reflection);
//				$controller->action($params['action']);
//			}
//		}
		return $this;
	}

	public function filterRouterResult(Result $result): array
	{
		$params = [];
		// controller过滤
		if (!empty($result->class)) {
			$params['class'] = $result->class;
			// 暂定
			$params['controller'] = strtolower(str_replace('\\', '/', $result->class));
		}
		else {
			$controller = $this->filterController($result->controller, true);
			if (!empty($result->namespace)) {
				if (strpos($controller, $result->namespace . '/') !== 0)
					$controller = $result->namespace . '/' . $controller;
			}
			$params['controller'] = $controller;
		}
		// action过滤
		$action = $this->filterAction($result->action, true);
		$params['action'] = $action;
		// format
		if (!empty($result->format))
			$params['format'] = $result->format;
		if (!empty(($tail = trim($result->tail, KE_PATH_NOISE))))
			$params['tail'] = $tail;
		// data
		if (!empty($result->data)) {
			$params['data'] = array_merge($this->params['data'], $result->data);
		}
		return $params;
	}

	public function getParams(): array
	{
		return $this->params;
	}

	public function param(string $field, $default = null)
	{
		return $this->params[$field] ?? $default;
	}

	public function makeControllerClass(string $controller)
	{
		$class = path2class($controller);
		$class = add_namespace($class, $this->controllerNamespace);
		return $class;
	}

	public function getControllerClass()
	{
		if (!empty($this->params['class']))
			return $this->params['class'];
		return $this->makeControllerClass($this->params['controller']);
	}

	public function getActionView()
	{
		if (empty($this->params['controller']))
			return "{$this->params['action']}";
		return "view/{$this->params['controller']}/{$this->params['action']}";
	}

	###################################################
	# render
	###################################################

	public function isRender()
	{
		if ($this->renderer === false)
			return false;
		return $this->renderer->isRender();
	}

	public function registerRenderer(Renderer $renderer, $assign = null)
	{
		if ($this->renderer === false) {
			$this->renderer = $renderer;
			if (isset($assign))
				$this->assign($assign);
		}
		return $this;
	}

	public function getRenderer()
	{
		if ($this->renderer === false)
			return null;
		return $this->renderer;
	}

	public function assign($key, $value = null)
	{
		$context = $this->getContext();
		if (is_array($key) || is_object($key)) {
			foreach ($key as $k => $v) {
				$context->{$k} = $v;
			}
		}
		else {
			$context->{$key} = $value;
		}
		return $this;
	}

	public function getContext()
	{
		if (!isset($this->context))
			$this->context = new Context($this);
		return $this->context;
	}

	public function setContext(Context $context)
	{
		if (isset($this->context)) {
			// 转移数据
			foreach ($this->context as $key => $value) {
				$context->{$key} = $value;
			}
		}
		$this->context = $context;
		return $this;
	}

	/**
	 * 获取一个零件(View\Widget\Layout)的路径
	 *
	 * @param string      $path
	 * @param string|null $scope
	 * @return bool|string
	 */
	public function getComponentPath(string $path, string $scope = null)
	{
		$path = trim($path, KE_PATH_NOISE . '.');
		if (empty($path))
			return false;
		$scope = $scope ?? Component::WIDGET;
		if (($index = strpos($path, '/')) > 0) {
			$scope = substr($path, 0, $index);
			$path = substr($path, $index + 1);
		}
		return $this->component->seek($scope, $path);
	}

	public function setFormat(string $format)
	{
		$this->format = $format;
		return $this;
	}

	public function setStatusCode(int $code)
	{
		$this->statusCode = $code;
		return $this;
	}

	public function addHeaders(array $headers)
	{
		$this->headers += $headers;
		return $this;
	}

	public function setHeaders(array $headers)
	{
		$this->headers = array_merge($this->headers, $headers);
		return $this;
	}

	public function sendHeaders()
	{
		if (headers_sent())
			return $this;
		if ($this->statusCode > 200 && $this->statusCode < 600)
			header($this->statusCode, true);
		$contentType = $this->mime->makeContentType($this->format);
		if (!empty($contentType))
			header("Content-Type: {$contentType}", true);
		if (!empty($this->headers)) {
			foreach ($this->headers as $field => $header) {
				if (!empty($header) && is_string($header)) {
					header($header);
				}
			}
		}
		return $this;
	}

	###################################################
	# misc
	###################################################

	public function setBaseUri(Uri $uri)
	{
		$this->baseUri = $uri;
		return $this;
	}

	public function getBaseUri()
	{
		if (!isset($this->baseUri)) {
			$this->baseUri = new Uri([
				'scheme' => KE_REQUEST_SCHEME,
				'host'   => KE_REQUEST_HOST,
				'uri'    => KE_HTTP_BASE,
			]);
		}
		return $this->baseUri;
	}

	public function linkTo($uri, $query = null)
	{
		return $this->getBaseUri()->newUri($uri, $query);
	}

}
