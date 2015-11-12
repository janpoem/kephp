<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/11
 * Time: 3:33
 */

namespace Ke\Cli;


use Ke\OutputBuffer;
use Ke\OutputImpl;

class Writer implements OutputImpl
{

	public function isOutput()
	{
		// TODO: Implement isOutput() method.
	}

	public function output()
	{
		$buffer = '';
		foreach (func_get_args() as $index => $arg) {
			$buffer .= print_r($arg, true);
			if ($arg !== PHP_EOL)
				$buffer .= ' ';
		}
		fwrite(STDOUT, $buffer);
	}
}
