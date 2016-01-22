<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 20:27
 */

namespace Ke\Cli\Cmd;


use Ke\Cli\Command;
use Ke\Cli\ReflectionCommand;

class Add extends ReflectionCommand
{

	protected static $commandName = 'add';

	protected static $commandDescription = 'Add something in application, add model|action|controller|view|cmd ...';

	/**
	 * @var string
	 * @type string
	 * @require true
	 * @field   1
	 */
	protected $name;

	protected $commands = [
		'model'      => NewModel::class,
		'command'    => NewCmd::class,
		'cmd'        => NewCmd::class,
		'controller' => NewController::class,
		'action'     => NewAction::class,
		'view'       => NewView::class,
		'widget'     => NewWidget::class,
		'layout'     => NewLayout::class,
	];

	/** @var Command */
	protected $command = null;

	protected function getTip()
	{
		return  implode('|', array_keys($this->commands));
	}

	protected function onPrepare($argv = null)
	{
		if (!isset($this->commands[$this->name]))
			throw new \Exception("Unknown add name, it should in {$this->getTip()}.");
		$class = $this->commands[$this->name];
		$newArgv = (array)$argv;
		array_shift($newArgv);
		$this->command = new $class($newArgv);
	}

	protected function onExecute($argv = null)
	{
		$this->command->execute();
	}
}