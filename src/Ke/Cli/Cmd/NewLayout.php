<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 20:14
 */

namespace Ke\Cli\Cmd;

class NewLayout extends NewWidget
{

	protected static $commandName = 'newLayout';

	protected static $commandDescription = '';

	protected $desc = 'layout';

	protected $template = 'Layout.tp';

	public function getPath(bool $checkDir = false)
	{
		$path = $this->dir . DS . 'Layout' . DS . $this->name . '.phtml';
		if ($checkDir) {
			$dir = dirname($path);
			if (!is_dir($dir))
				mkdir($dir, 0755, true);
		}
		return $path;
	}

}