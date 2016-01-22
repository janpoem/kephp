<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 17:40
 */

namespace Ke\Web;


class Writer extends Renderer
{

	private $content = null;

	public function __construct($content)
	{
		$this->content = $content;
	}

	protected function rendering(Web $web)
	{
		if (!empty($this->format))
			$web->setFormat($this->format);
		$web->sendHeaders();
		exit($this->content);
	}
}