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

	protected $file = '';

	protected $command = '';

	public static function current()
	{
		if (!isset(self::$current)) {
			$args = [];
			if (isset($_SERVER['argv'])) {
				$args = $_SERVER['argv'];
				array_shift($args);
			}
			self::$current = new static($args);
			self::$current->file = KE_SCRIPT_FILE;
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
		$type = gettype($input);
		if ($type === KE_STR) {
			$type = KE_ARY;
			$input = explode(' ', $input);
		} elseif ($type === KE_OBJ) {
			$type = KE_ARY;
			$input = get_object_vars($input);
		}

		if (empty($input) || $type !== KE_ARY)
			return $this;

		$inPair = false;
		$naturalIndex = -1;
		foreach ($input as $index => $item) {
			$item = trim($item);
			if (preg_match('#^(\-+)([^\=]+)(?:\=(.*))?#', $item, $matches)) {
				if ($inPair !== false) {
					$inPair = false;
				}
				if ($inPair === false) {
//				    $prefix = $matches[1];
//				    if (strlen($prefix) > 2)
//					    $prefix = '--'; // limit -- should in 2 chars
					$prefix = $matches[2];
					if (isset($matches[3])) {
						$this->data[$prefix] = $matches[3];
					} else {
						$this->data[$prefix] = '';
						$inPair = $prefix;
					}
				}
				continue;
			}
			if ($inPair !== false) {
				$this->data[$inPair] = $item;
				$inPair = false;
				continue;
			}
			$naturalIndex++;
			if ($naturalIndex === 0) {
				$this->command = $item;
			} else {
				$this->data[] = $item;
			}
		}
		return $this;
	}

	public function getData()
	{
		return $this->data;
	}

//	public function __get($field)
//	{
//		if ($field === 'command')
//			return $this->command;
//		elseif ($field === 'file')
//			return $this->file;
//		return null;
//	}

	public function getCommand()
	{
		return $this->command;
	}

	public function getCommands()
	{
		$result = [];
		if (empty($this->command))
			return $result;
		$base = str_replace(['\\', '-', '.'], ['/', '_', '_'], trim($this->command, KE_PATH_NOISE));
		$result[] = $base;
		$lower = strtolower($this->command);
		if ($lower !== $this->command)
			$result[] = $lower;
		// 这里暂时这么处理
		$camelCase = preg_replace_callback('#([\-\_\/\.])([a-z])#', function($matches) {
			if ($matches[1] === '/')
				return strtoupper($matches[0]);
			else
				return '_' . strtoupper($matches[2]);
		}, ucfirst($lower));
		if ($camelCase !== $this->command)
			$result[] = $camelCase;
		$camelCaseNoUnder = str_replace('_', '', $camelCase);
		if ($camelCaseNoUnder !== $this->command)
			$result[] = $camelCaseNoUnder;
		return $result;
	}

	public function getFile()
	{
		return empty($this->file) ? false : $this->file;
	}

	public function query($keys, $default = null)
	{
		if (isset($this->data[$keys]))
			return $this->data[$keys];
		return depthQuery($this->data, $keys, $default);
	}
}