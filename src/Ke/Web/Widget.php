<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/23 0023
 * Time: 13:28
 */

namespace Ke\Web;

/**
 * @package Ke\Web
 */
abstract class Widget extends Context
{

	abstract public function getRenderContent(): string;

	protected function onRender()
	{

	}

	/**
	 * @return Widget|static
	 */
	public function render()
	{
		$this->onRender();
		print $this->getRenderContent();
		return $this;
	}
}