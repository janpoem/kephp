<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 19:46
 */

namespace Ke\Cli\Cmd;


use Ke\Cli\ReflectionCommand;
use Ke\App, Ke\Web\Web, Ke\Web\Controller;

class NewView extends ReflectionCommand
{

	protected static $commandName = 'newView';

	protected static $commandDescription = '';

	/**
	 * @var string
	 * @type string
	 * @require true
	 * @field   1
	 */
	protected $name = '';

	protected $parse = ['controller' => '', 'action' => ''];

	/** @var Web */
	protected $web = null;

	protected $app = null;

	protected $router = null;

	protected $controller = null;

	protected $class = null;

	protected $view = null;

	protected $dir = null;

	/** @var \ReflectionClass */
	protected $reflection = null;

	protected function onPrepare($argv = null)
	{
		$this->web = Web::getWeb();
		$this->app = $this->web->app;
		$this->router = $this->web->getRouter();
		$this->parse = $this->router->parseStr($this->name);
		if (empty($this->parse['controller']))
			throw new \Exception('Please specify controller, the right format should "controller#action"!');
		if (empty($this->parse['action']))
			throw new \Exception('Please specify action, the right format should "controller#action"!');
		$this->controller = $this->web->filterController($this->parse['controller']);
		$this->class = $this->web->makeControllerClass($this->controller);
		$this->view = $this->web->filterAction($this->parse['action']);

		$dirs = $this->web->component->getScopeDirs('view');
		if (!isset($dirs['appView']))
			throw new \Exception("Unknown view folder!");
		$this->dir = $dirs['appView'];

		if (is_file($this->getPath()))
			throw new \Exception("The file '{$this->getPath()}' is existing!");
	}

	protected function onExecute($argv = null)
	{
		if (file_put_contents($this->getPath(true), $this->buildContent())) {
			$this->console->println("Add view '{$this->getPath()}' success!");
		}
		else {
			$this->console->println("Add view '{$this->getPath()}' lost, please try again.");
		}
	}

	public function getPath(bool $checkDir = false)
	{
		$path = $this->dir . DS . $this->controller . DS . $this->view . '.phtml';
		if ($checkDir) {
			$dir = dirname($path);
			if (!is_dir($dir))
				mkdir($dir, 0755, true);
		}
		return $path;
	}

	public function buildContent(): string
	{
		$tpl = __DIR__ . '/Templates/view2.tp';
		$content = file_get_contents($tpl);
		$vars = [
			'path' => "{$this->controller}/{$this->view}",
		    'class' => $this->class,
		];
		return substitute($content, $vars);
	}
}