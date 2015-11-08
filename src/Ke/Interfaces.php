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

	public function setInput($input);

	public function getInput();

	public function output();
}


interface InputImpl
{

	public function setData($data);

	public function getData();
}

interface OutputImpl
{

	public function isOutput();

	public function output();
}

