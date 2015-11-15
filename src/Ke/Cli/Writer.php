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

	protected $buffers = [];

	protected $line = -1;

	public function isOutput()
	{
		// TODO: Implement isOutput() method.
	}

	public function output($content = null, $isBreakLine = false)
	{
		if ($isBreakLine)
			$content .= PHP_EOL;
		if (PHP_SAPI === KE_CLI_MODE) {
			file_put_contents('php://stdout', $content);
		} else {
			echo nl2br(htmlentities($content));
		}
	}

	public function send($content = null, $isBreakLine = false)
	{
	}
}
