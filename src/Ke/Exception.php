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

use Exception as PhpException;

/**
 * 基础的异常类，KePHP的异常类，都应该继承自这个异常类，而不应该直接继承PhpException
 *
 * @package Ke\Core
 */
class Exception extends PhpException
{

	protected $args = [];

	protected $messages = [];

	public function __construct($message, array $args = [], PhpException $exception = null)
	{
		$message = substitute($this->getStdMessage($message), $args);
		$this->args = $args;
		parent::__construct($message, 0, $exception);
	}

	public function getStdMessage($message)
	{
		return $message;
	}

	public function getArgs()
	{
		return $this->args;
	}
}