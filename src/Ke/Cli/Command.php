<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Cli;

abstract class Command
{


	private static $initCommands = [];

	protected static $commandName = '';

	protected static $commandDescription = '';

	protected static $commandUsage = null;

	protected static $columns = [
		'command' => [
			'type'    => KE_STR,
			'default' => '',
			'field'   => 0,
		],
		'help'    => [
			'shortcut' => 'h',
			'type'     => 'single',
			'default'  => false,
		],
	];

	protected static $columnMaps = [];

	protected static $defaultValues = [];

	public static function init()
	{
		if (!isset(self::$initCommands[static::class])) {
			static::initColumns(static::loadColumns());
			self::$initCommands[static::class] = true;
		}
	}

	protected static function loadColumns()
	{
		return array_merge(self::$columns, static::$columns);
	}

	protected static function initColumns(array $inputColumns = null)
	{
		if (empty($inputColumns))
			return;
		$columns = [];
		$maps = static::$columnMaps;
		$defaultValues = static::$defaultValues;
		foreach ($inputColumns as $name => $options) {
			$field = trim((string)$name, '-_.');
			$name = camelcase($name);
			if (empty($name) || isset($columns[$name]) || is_numeric($name))
				continue;
			$type = isset($options['type']) ? $options['type'] : KE_STR;
			$default = isset($options['default']) ? $options['default'] : null;
			$column = [
				'name'     => $name,
				'field'    => $field,
				'shortcut' => false,
				'type'     => $type,
				'default'  => $default,
				'args'     => [],
				'require'  => !empty($options['require']),
			];
			if ($type === 'concat' || $type === 'dirs' || $type === 'files') {
				if (isset($column['spr']))
					$column['args'][0] = $column['spr'];
				else
					$column['args'][0] = ',';
			}
			$column['type'] = $type;
			$column['default'] = $default;

			if (isset($options['field'])) {
				if (is_numeric($options['field']) && $options['field'] >= 0) {
					$column['field'] = (int)$options['field'];
				}
				elseif (!empty($options['field']) && is_string($options['field'])) {
					$column['field'] = trim($options['field'], '-_');
					if (empty($options['field']) || !is_string($column['field']))
						$column['field'] = $field;
				}
			}
			if (!empty($options['shortcut']) && is_string($options['shortcut'])) {
				$column['shortcut'] = trim(strtolower($options['shortcut']), '-');
			}
			if (empty($column['shortcut']) || !is_string($column['shortcut'])) {
				$column['shortcut'] = false;
			}
//				$column['shortcut'] = $options['shortcut'];
			$defaultValues[$name] = $column['default'] = static::verifyValue($column['type'], $column['default'],
				$column);
			$columns[$name] = $column;
//			$maps[$column['name']] = $name;
			$maps[$column['field']] = $name;
			if ($column['shortcut'] !== false)
				$maps[$column['shortcut']] = $name;
//				$this->{$name} = $column['default'];
		}
		static::$columns = &$columns;
		static::$columnMaps = &$maps;
		static::$defaultValues = &$defaultValues;
	}

	public static function verifyValue($type, $value = null, array $column = [])
	{
		if ($type === KE_STR) {
			return (string)$value;
		}
		elseif ($type === KE_BOOL || $type === 'bool' || $type === 'single') {
			if ($value === 'false' || $value === '0' || $value === 0 || $value === 0.00)
				return false;
			if (strtolower($value) === 'off')
				return false;
			if ($type === 'single' && $value === '')
				return !$column['default'];
			return (bool)$value;
		}
		elseif ($type === KE_INT) {
			return (int)$value;
		}
		elseif ($type === KE_FLOAT) {
			return (float)$value;
		}
		elseif ($type === 'array') {
			if (is_string($value)) {
				if (strpos($value, ',') > 0) {
					$result = [];
					foreach (explode(',', $value) as $item) {
						if (!empty(($item = trim($item))))
							$result[] = $item;
					}
					return $result;
				}
			}
			return (array)$value;
		}
		elseif ($type === 'dir') {
			if (empty($value))
				return false;
			$value = realpath($value);
			if (is_dir($value))
				return $value;
			return false;
		}
		elseif ($type === 'file') {
			if (empty($value))
				return false;
			$value = realpath($value);
			if (is_file($value) && is_readable($value))
				return $value;
			return false;
		}
		elseif ($type === 'realpath') {
			if (empty($value))
				return KE_SCRIPT_DIR;
			return realpath($value);
		}
		elseif ($type === 'json') {
			$decode = json_decode($value, true);
			return $decode;
		}
		elseif (($type === 'concat' || $type === 'dirs' || $type === 'files') && isset($column['args'][0])) {
			if (empty($value))
				return [];
			$value = explode($column['args'][0], $value);
			$value = array_filter($value); // 过滤空值
			if ($type === 'dirs') {
				foreach ($value as & $item) {
					$item = static::verifyValue($item, 'dir', $column);
				}
			}
			elseif ($type === 'files') {
				foreach ($value as & $item) {
					$item = static::verifyValue($item, 'file', $column);
				}
			}
			return $value;
		}
		else {
			if ($value === 'false')
				return false;
			if ($value === 'true')
				return true;
			if ($value === 'null')
				return null;
			if (is_float($value))
				return (float)$value;
			if (is_int($value))
				return (int)$value;
			return $value;
		}
	}

	public static function getColumns()
	{
		if (!isset(self::$initCommands[static::class]))
			static::init();
		return static::$columns;
	}

	public static function getDefaultColumn($field)
	{
		return [
			'name'    => camelcase($field),
			'field'   => $field,
			'type'    => 'any',
			'default' => null,
			'args'    => [],
		];
	}

	public static function getName()
	{
		if (empty(static::$commandName)) {
			$class = static::class;
			$name = $class;
			$pos = strrpos($class, '\\');
			if ($pos !== false) {
				$name = substr($class, $pos + 1);
			}
			$name = strtolower($name);
			static::$commandName = &$name;
		}
		return static::$commandName;
	}

	public static function getUsage()
	{
		if (empty(static::$commandUsage)) {
			$usage = 'usage: php ' . (PHP_SAPI === KE_CLI ? KE_SCRIPT_FILE : 'ke.php') . ' ' . static::getName();
			$total = $length = strlen($usage);
			$padding = str_repeat(' ', $length);
			$sortIndex = [];
			$sortColumns = [];
			$unsortColumns = [];
			foreach (static::getColumns() as $name => $column) {
				if ($column['require']) {
					$sortIndex[$name] = $column['field'];
					$sortColumns[$name] = $column;
				}
				else
					$unsortColumns[$name] = $column;
			}
			array_multisort($sortIndex, SORT_ASC, SORT_STRING, $sortColumns);
			foreach (array_merge($sortColumns, $unsortColumns) as $name => $column) {
				if ($name === 'command' || $name === 'help')
					continue;
				if (!empty($column['hide']))
					continue;
				if (is_numeric($column['field'])) {
					$temp = "<{$name}>";
				}
				else {
					$field = "--{$column['field']}";
					if (!empty($column['shortcut']))
						$field .= '|-' . $column['shortcut'];
					if ($column['type'] === 'single')
						$temp = "{$field}";
					else
						$temp = "{$field}=<{$name}>";
				}
				if (!$column['require'])
					$temp = "[$temp]";
				$tempLength = strlen($temp) + 1;
				if ($total + $tempLength >= 120) {
					$usage .= PHP_EOL . $padding;
					$total = $length;
				}
				else {
					$total += $tempLength;
				}
				$usage .= ' ' . $temp;
			}
			static::$commandUsage = &$usage;
		}
		return static::$commandUsage;
	}

	public static function showHelp($message = null)
	{
		$console = Console::getConsole();
		$console->print(static::getName(), '(' . static::class . ')');
		if (!empty(static::$commandDescription))
			$console->print('-', static::$commandDescription);
		$console->println();
		$console->println(static::getUsage());
		if (!empty($message))
			$console->println($message);
	}

	private $_argv = null;

	protected $command = null;

	protected $help = false;

	protected $console = null;

	public function __construct($argv = null)
	{
		if (!isset(self::$initCommands[static::class]))
			static::init();
		$this->assign(static::$defaultValues);
		if (isset($argv)) {
			$this->_argv = $argv;
			$this->assign($argv);
		}
		$this->console = Console::getConsole();
		$this->onConstruct($argv);
	}

	protected function onConstruct($argv = null)
	{
	}

	public function assign($key, $value = null)
	{
		$type = gettype($key);
		if ($type === KE_ARY || $type === KE_OBJ) {
			foreach ($key as $name => $value) {
				$this->assign($name, $value);
			}
		}
		else {
			if (isset(static::$columns[$key]))
				$column = static::$columns[$key];
			elseif (isset(static::$columnMaps[$key]))
				$column = static::$columns[static::$columnMaps[$key]];
			else
				$column = static::getDefaultColumn($key);
			$this->{$column['name']} = static::verifyValue($column['type'], $value, $column);
		}
		return $this;
	}

	protected function verifyRequire()
	{
		foreach (static::$columns as $name => $column) {
			if ($column['require']) {
				if (!isset($this->{$name}) || empty($this->{$name})) {
					static::showHelp(substitute('Must specify the argument "{field}"', [
						'command' => static::getName(),
						'field'   => $name,
					]));
					exit();
					break;
				}
			}
			continue;
		}
		return true;
	}

	public function execute($argv = null)
	{
		if (isset($argv))
			$this->assign($argv);
		else
			$argv = $this->_argv;
		if ($this->help) {
			static::showHelp();
		}
		else {
			$this->verifyRequire();
			$this->onPrepare($argv);
			$this->onExecute($argv);
		}
	}

	protected function onPrepare($argv = null)
	{
	}

	abstract protected function onExecute($argv = null);
}