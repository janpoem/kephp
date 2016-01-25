<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
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