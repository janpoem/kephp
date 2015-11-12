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
 * 命令行的参数列表
 *
 * @package Ke\Cli
 */
class Argv implements InputImpl
{

	private static $current = null;

	protected $file = '';

	protected $command = '';

	protected $data = [];

	public static function current()
	{
		if (!isset(self::$current)) {
			$_SERVER['argv'][0] = KE_SCRIPT_FILE;
			self::$current = new static();
			self::$current->setRawData($_SERVER['argv']);
		}
		return self::$current;
	}

	public function setRawData($input)
	{
		$type = gettype($input);
		if ($type === KE_STR) {
			$type = KE_ARY;
			$input = explode(' ', $input);
		} elseif ($type === KE_OBJ) {
			// 一个对象无法做有效的转换
			return $this;
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
			} elseif ($naturalIndex === 1) {
				$this->command = $item;
			} else {
				$this->data[] = $item;
			}
		}
		return $this;
	}

	public function setData($input)
	{
		$type = gettype($input);
		if ($type === KE_STR) {
			$type = KE_ARY;
			parse_str($input, $input);
		} elseif ($type === KE_OBJ) {
			$type = KE_ARY;
			$input = get_object_vars($input);
		}
		if (!empty($input) && $type === KE_ARY)
			$this->data = $input;
		return $this;
	}

	public function getData()
	{
		return $this->data;
	}

	public function __get($field)
	{
		return isset($this->data[$field]) ? $this->data[$field] : null;
	}

	public function __set($field, $value)
	{
		return $this->data[$field] = $value;
	}

	public function getCommand()
	{
		return $this->command;
	}

	public function mkCommands()
	{
		$result = [];
		if (empty($this->command))
			return $result;
		$base = str_replace([/*'\\',*/
		                     '-',
		                     '.',
		], [/*'/',*/
		    '_',
		    '_',
		], trim($this->command, KE_PATH_NOISE));
		$result[$base] = $base;
		$lower = strtolower($this->command);
		if (!isset($result[$lower]))
			$result[$lower] = $lower;
		$lowerNoUnder = str_replace('_', '', $lower);
		if (!isset($result[$lowerNoUnder]))
			$result[$lowerNoUnder] = $lowerNoUnder;
		// 这里暂时这么处理
		$camelCase = preg_replace_callback('#([\-\_\/\.\\\\])([a-z])#', function ($matches) {
			if ($matches[1] === '/' || $matches[1] === '\\')
				return strtoupper($matches[0]);
			else
				return '_' . strtoupper($matches[2]);
		}, ucfirst($lower));
		if (!isset($result[$camelCase]))
			$result[$camelCase] = $camelCase;
		$camelCaseNoUnder = str_replace('_', '', $camelCase);
		if (!isset($result[$camelCaseNoUnder]))
			$result[$camelCaseNoUnder] = $camelCaseNoUnder;
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