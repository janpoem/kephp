<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/9 0009
 * Time: 9:14
 */

namespace Ke\Cli;

use Ke\Exception;
use Ke\InputImpl;

/**
 * 命令行的参数
 *
 * @package Ke\Cli
 */
class Args implements InputImpl
{

	private static $current = null;

	protected $data = [];

	protected $command = '';

	public static function current()
	{
		if (!isset(self::$current)) {
			$argv = $_SERVER['argv']; // clone a new one
			array_shift($argv);
			self::$current = new static($argv);
		}
		return self::$current;
	}

	public function __construct($input = null)
	{
		if (isset($input))
			$this->setData($input);
	}

	public function setData($input)
	{
		if (!is_array($input))
			throw new Exception('The {class} input data should be an array!', ['class' => static::class]);
		if (empty($this->data))
			$this->data = $input;
		else
			$this->data = array_merge($this->data, $input);
		return $this;
	}

	public function getData()
	{
		return $this->data;
	}
}