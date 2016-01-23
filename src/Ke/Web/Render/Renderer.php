<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/23 0023
 * Time: 23:52
 */

namespace Ke\Web\Render;

use Ke\Web\Web;
use Ke\Web\Context;

abstract class Renderer
{

	/** @var Web */
	protected $web;

	/** @var Context */
	protected $context;

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

	public function render()
	{
		if ($this->isRender)
			return $this;
		$this->isRender = true;
		// make sure clean all buffer
		$this->web->ob->clean('webStart');
		$this->web->ob->start('webRender');
		$this->web->registerRenderer($this);
		$this->rendering();
		return $this;
	}

	abstract protected function rendering();

	abstract public function getContent();

	abstract public function setContent($content);

}