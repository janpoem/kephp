<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/12
 * Time: 22:23
 */

namespace Ke\Cli\Command;

use Ke\Cli\ReflectionCommand;

/**
 * Class NewApp
 * @package Ke\Cli\Command
 */
class NewApp extends ReflectionCommand
{

	protected static $commandName = 'new_app';

	protected static $commandDescription = 'Create a new application with kephp!';

	/**
	 * @type string
	 * @require true
	 * @field 1
	 */
	protected $appName = '';

	/**
	 * @type dir
	 * @require true
	 * @shortcut d
	 */
	protected $dir = false;

	protected function onExecute(\Ke\Cli\Console $console, $argv = null)
	{
		var_dump($argv);
	}
}