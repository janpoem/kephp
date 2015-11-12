<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/12
 * Time: 22:23
 */

namespace Ke\Cli\Command;

use Ke\Cli\Command;
use Ke\Cli\Console;
use Ke\Cli\Argv;

class NewApp extends Command
{

	protected $name = 'new_app';

	protected $description = 'Create a new application with kephp!';

	protected $guide = [
		'name' => [
			'field' => 0,
			'type'  => KE_STR,
		],
		'dir'  => [
			'type'     => 'dir',
			'shortcut' => 'd',
		],
	];

	protected function onExecute(Console $console, Argv $argv)
	{
		// TODO: Implement onExecute() method.
	}
}