<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 11:49
 */

namespace Ke\Web;


abstract class Renderer
{

	private $isRender = false;

	public function isRender(): bool
	{
		return $this->isRender;
	}

	public function render(array $vars = null)
	{
		if ($this->isRender)
			return $this;
		$this->isRender = true;
		$web = Web::getWeb();
		$web->registerRenderer($this);
		$this->rendering($web);
		return $this;
	}

	abstract protected function rendering(Web $web);
}