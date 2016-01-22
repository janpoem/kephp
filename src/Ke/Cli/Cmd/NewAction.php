<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 19:06
 */

namespace Ke\Cli\Cmd;


use Ke\Cli\ReflectionCommand;
use Ke\App, Ke\Web\Web, Ke\Web\Controller;

class NewAction extends ReflectionCommand
{

	protected static $commandName = 'newAction';

	protected static $commandDescription = '';

	/**
	 * @var string
	 * @type string
	 * @require true
	 * @field   1
	 */
	protected $name = '';

	/**
	 * @var bool
	 * @type bool
	 * @default true
	 * @field   v
	 */
	protected $addView = true;

	protected $parse = ['controller' => '', 'action' => ''];

	protected $web = null;

	protected $router = null;

	protected $class = null;

	protected $method = null;

	/** @var \ReflectionClass */
	protected $reflection = null;

	protected function onPrepare($argv = null)
	{
		$this->web = Web::getWeb();
		$this->router = $this->web->getRouter();
		$this->parse = $this->router->parseStr($this->name);
		if (empty($this->parse['controller']))
			throw new \Exception('Please specify controller, the right format should "controller#action"!');
		if (empty($this->parse['action']))
			throw new \Exception('Please specify action, the right format should "controller#action"!');
		$this->class = $this->web->makeControllerClass($this->parse['controller']);
		$this->method = $this->web->filterAction($this->parse['action']);
		if (!class_exists($this->class, true))
			throw new \Exception("The class '{$this->class}' not found!");
		if (!is_subclass_of($this->class, Controller::class))
			throw new \Exception("The class '{$this->class}' is exiting, but it's not a controller class!");

		$this->reflection = new \ReflectionClass($this->class);

		if ($this->reflection->hasMethod($this->method))
			throw new \Exception("The method '{$this->class}#{$this->method}' is defined!");
	}

	protected function onExecute($argv = null)
	{
		$rows = $this->buildAllFile();
		$content = implode('', $rows);
		if (file_put_contents($this->getPath(), $content)) {
			$this->console->println("Add method '{$this->class}#{$this->method}' success!");
			if ($this->addView) {
				$command = new NewView(['', $this->name]);
				$command->execute();
			}
		}
		else {
			$this->console->println("Add method '{$this->class}#{$this->method}' lost, please try again.");
		}


	}

	public function getPath()
	{
		return App::getApp()->src("{$this->class}", 'php');
	}

	public function buildMethodBody(): string
	{
		$content = [
			"",
			"\tpublic function {$this->method}()",
			"\t{",
			"\t\t// return",
			"\t}",
		    "",
		];
		return implode(PHP_EOL, $content);
	}

	public function buildAllFile(): array
	{
		$path = $this->getPath();
		if (!is_file($path))
			throw new \Exception("The file {$path} not found!");
		if (!is_readable($path))
			throw new \Exception("The file {$path} not readable!");
		if (!is_writeable($path))
			throw new \Exception("The file {$path} not writable!");
		$endLine = $this->reflection->getEndLine();
		$insertLine = $endLine - 1;
		$handle = @fopen($path, 'r');
		$index = 0;
		$result = [];
		while (($buffer = fgets($handle, 4096)) !== false) {
			$index++;
			$result[] = $buffer;
			if ($index === $insertLine)
				$result[] = $this->buildMethodBody($this->method);
		}
		return $result;
	}
}