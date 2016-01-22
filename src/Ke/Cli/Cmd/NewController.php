<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 18:15
 */

namespace Ke\Cli\Cmd;

use Ke\App;
use Ke\Cli\ReflectionCommand;
use Ke\Web\Web;

class NewController extends ReflectionCommand
{

	protected static $commandName = 'newController';

	protected static $commandDescription = '';

	/**
	 * @var string
	 * @type string
	 * @require true
	 * @field   1
	 */
	protected $name = '';

	protected $class = '';

	/**
	 * @var string
	 * @type string
	 * @field   e
	 */
	protected $extend = '';

	/**
	 * @var string
	 * @type array
	 * @field   a
	 */
	protected $actions = [];

	/** @var Web */
	protected $web = null;

	protected function onPrepare($argv = null)
	{
		$this->web = Web::getWeb();
		$this->name = $this->web->filterController($this->name);
		$this->class = $this->web->makeControllerClass($this->name);
	}

	protected function onExecute($argv = null)
	{
		$path = $this->getPath();
		if (is_file($path))
			throw new \Exception("The file {$path} is existing!");

		$content = $this->buildClass($this->class);
		$dir = dirname($path);
		if (!is_dir($dir))
			mkdir($dir, 0755, true);
		if (file_put_contents($path, $content)) {
			$this->console->println("Add controller '{$this->class}' success!");
			$action = $this->web->getDefaultAction();
			$command = new NewAction(['', "{$this->name}#{$action}"]);
			$command->execute();
		}
		else {
			$this->console->println("Add controller '{$this->class}' lost, please try again.");
		}
	}

	public function getPath()
	{
		return App::getApp()->src("{$this->class}", 'php');
	}

	public function buildClass(string $class)
	{
		$tpl = __DIR__ . '/Templates/Controller.tp';
		$vars = [];
		list($vars['namespace'], $vars['class']) = parse_class($class);
		if (!empty($vars['namespace']))
			$vars['namespace'] = "namespace {$vars['namespace']};";
		if (empty($this->extend)) {
			$vars['extend'] = 'Controller';
			$vars['use'] = 'use Ke\Web\Controller;';
		}
		else {
			$vars['extend'] = $this->extend;
		}

		return substitute(file_get_contents($tpl), $vars);
	}
}