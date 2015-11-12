<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/11
 * Time: 3:33
 */

namespace Ke\Cli;


use Ke\OutputImpl;

class Writer implements OutputImpl
{

	public function isOutput()
	{
		// TODO: Implement isOutput() method.
	}

	public function output()
	{
		$args = func_get_args();
		foreach ($args as $i => $arg) {
			print_r($arg);
			if ($i < count($args))
				echo ' ';
		}
	}
}
