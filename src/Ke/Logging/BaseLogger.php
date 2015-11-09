<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/9
 * Time: 3:18
 */

namespace Ke\Logging;


class BaseLogger implements LoggerImpl
{

	use LoggerOps;

	public function __construct($name)
	{
		$this->setLoggerName($name);
	}
}