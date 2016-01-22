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

	protected $web = null;

	protected $context = null;

	private $isRender = false;

	public function __construct()
	{
		$this->web = Web::getWeb();
		$this->context = $this->web->getContext();
	}

	public function isRender(): bool
	{
		return $this->isRender;
	}

	public function render(array $vars = null)
	{
		if ($this->isRender)
			return $this;
		$this->isRender = true;
		// make sure clean all buffer
		$this->web->ob->clean('webStart');
		$this->web->registerRenderer($this);
		$this->onRender();
		return $this;
	}

	abstract protected function onRender();
}