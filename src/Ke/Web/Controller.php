<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 16:27
 */

namespace Ke\Web;

use Ke\Utils\Status;
use ReflectionClass, ReflectionObject;
use Ke\Web\Render, Ke\Web\Render\Renderer;

class Controller
{

	/** @var ReflectionClass|ReflectionObject */
	private $_reflection = null;

	/** @var Web */
	protected $web = null;

	/** @var Http */
	protected $http = null;

	/** @var string */
	public $title = '';

	/** @var null */
	public $layout = null;

	public function __construct()
	{
		$this->web = Web::getWeb();
		$this->http = $this->web->http;
		$this->onConstruct();
	}

	protected function onConstruct() { }

	/**
	 * @return ReflectionClass|ReflectionObject
	 */
	public function getReflection()
	{
		if (!isset($this->_reflection) || !($this->_reflection instanceof ReflectionClass))
			$this->_reflection = new ReflectionObject($this);
		return $this->_reflection;
	}

	public function setReflection(ReflectionClass $reflectionClass)
	{
		if (!isset($this->_reflection))
			$this->_reflection = $reflectionClass;
		return $this;
	}

	public function makeActions(string $action)
	{
		$actions = [$action];
		$method = $this->http->method;
		if ($method !== Http::GET)
			$actions[] = strtolower($method) . '_' . $action;
		return $actions;
	}

	public function action(string $action)
	{
		if (empty($action))
			throw new \Exception('Empty action name!');
		$objectRef = $this->getReflection();
		$vars = $this->web->param('data', []);
		$methods = $this->makeActions($action);
		$return = null;
		foreach ($methods as $index => $method) {
			if ($objectRef->hasMethod($method)) {
				$methodRef = $this->_reflection->getMethod($method);
				if ($methodRef->isPublic() && !$methodRef->isStatic() && !$this->web->isRender()) {
					$re = $methodRef->invokeArgs($this, $vars);
					if (!($re instanceof Controller) && !($re instanceof Renderer) && $re !== null)
						$return = $re;
				}
			}
			else {
				if ($index === 0)
					throw new \Exception("Action {$method} not found!");
			}
		}

		$this->defaultReturn(...(array)$return);

		return $this;
	}

	protected function defaultReturn(...$return)
	{
		if (!isset($return[0]))
			$return[0] = $this->web->getActionView();
		return $this->view($return[0]);
	}

	protected function onRender(Renderer $renderer)
	{
	}

	final private function rendering(Renderer $renderer)
	{
		if (!$this->web->isRender()) {
			$this->web->assign($this);
			$this->onRender($renderer);
		}
		return $renderer;
	}

	protected function view(string $view = null, string $layout = null)
	{
		if (!isset($layout))
			$layout = $this->layout;
		if (!$this->web->isRender())
			$this->rendering(new Render\View($view, $layout))->render();
		return $this;
	}

	protected function text($text, $format = 'txt')
	{
		if (!$this->web->isRender()) {
			if (!empty($format))
				$this->web->setFormat($format);
			$this->rendering(new Render\Text($text))->render();
		}
		return $this;
	}

	protected function json($data)
	{
		return $this->text(json_encode($data), 'json');
	}

	protected function status($status, $message = '', array $data = null)
	{
		if (!($status instanceof Status)) {
			$status = new Status($status, $message, $data);
		}
		else {
			$status->setMessage($message);
			if (!empty($data))
				$status->setData($data);
		}
		return $this->json($status->export());
	}

	protected function redirect($uri, $query = null)
	{
		if (!$this->web->isRender()) {
			$this->rendering(new Render\Redirection($this->web->linkTo($uri, $query)))->render();
		}
		return $this;
	}
}