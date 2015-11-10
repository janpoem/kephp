<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke;

/**
 * 自动加载类的接口，用于在这个Class被加载时，附加执行onLoadClass接口（只对Ke\ClassLoader有效）
 *
 * @package Ke
 */
interface AutoLoadClassImpl
{

	public static function onLoadClass($class, $path);
}


interface ContextImpl
{

	/**
	 * @return ContextImpl
	 */
	public static function context();

	/**
	 * @param InputImpl $input
	 * @return ContextImpl
	 */
	public function setInput(InputImpl $input);

	/**
	 * @return InputImpl
	 */
	public function getInput();

	/**
	 * @param OutputImpl $output
	 * @return ContextImpl
	 */
	public function setOutput(OutputImpl $output);

	/**
	 * @return InputImpl
	 */
	public function getOutput();
}

interface InputImpl
{

	public function setData($input);

	public function getData();
}


interface OutputImpl
{

	public function isOutput();

	public function output();
}

