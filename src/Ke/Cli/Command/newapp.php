<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/12
 * Time: 22:23
 */

namespace Ke\Cli\Command;

use Ke\Cli\Command;

class newapp extends Command
{

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

	public function execute()
	{
		$this->console->info('hello world');
	}
}