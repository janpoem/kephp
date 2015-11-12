<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/9 0009
 * Time: 10:01
 */

namespace Ke\Cli;


class Command
{

	private static $defaultTypes = [
		KE_NULL    => true,
		KE_BOOL    => true,
		KE_INT     => true,
		KE_FLOAT   => true,
		KE_STR     => true,
		//		KE_ARY,
		'dir'      => true,
		'dirs'     => true,
		'file'     => true,
		'files'    => true,
		'realpath' => true,
		'json'     => true,
		'any'      => true,
		'concat'   => true,
	];

	private $isInitGuide = false;

	/** @var Console */
	protected $console = null;

	protected $guide = [];

	/** @var Argv */
	protected $argv = null;

	protected $allowTypes = null;

	public function __construct(Argv $argv)
	{
		$this->console = Console::getContext();
		if (empty($this->allowTypes) || !is_array($this->allowTypes))
			$this->allowTypes = self::$defaultTypes;
		else
			$this->allowTypes = array_merge($this->allowTypes, self::$defaultTypes);
		$this->argv = $argv;
		$this->initGuide($this->argv);
		$this->onConstruct();
	}

	protected function onConstruct()
	{
	}

	protected function initOptions(array $options)
	{

	}

	protected function initGuide(Argv $argv = null)
	{
		if ($this->isInitGuide)
			return $this;
		$this->isInitGuide = true;
		$data = false;
		$newData = [];
		$used = [];
		if (isset($argv))
			$data = $argv->getData();
		if (empty($this->guide))
			$this->console->warn('Command class "' . get_class($this) . '" argv guide is empty!');
		foreach ($this->guide as $name => $options) {
			$name = trim((string)$name, '-');
			$type = KE_STR;
			$typeHandle = [$this, 'verifyTypeValue'];
			if (isset($options['type']) && isset($this->allowTypes[$options['type']])) {
				if (!empty($this->allowTypes[$options['type']])) {
					$type = $options['type'];
					$handle = $this->allowTypes[$options['type']];
					if (is_string($handle) && is_callable([$this, $handle])) {
						$typeHandle = [$this, $handle];
					}
				}
			}
			$default = isset($options['default']) ? $options['default'] : null;
			$field = [
				'type'     => '',
				'default'  => $default,
				'field'    => '',
				'shortcut' => false,
				'args'     => [],
			];
			if (!empty($options['single'])) {
				$field['type'] = KE_BOOL;
				$field['default'] = $default;
			} else {
				if ($type === 'concat' || $type === 'dirs' || $type === 'files') {
					if (isset($field['spr']))
						$field['args'][0] = $field['spr'];
				}
				$field['type'] = $type;
				$field['default'] = $default;
			}
			$hasField = false;
			if (isset($options['field'])) {
				if (is_numeric($options['field']) && $options['field'] >= 0) {
					$options['field'] = (int)$options['field'];
					$hasField = true;
				} elseif (!empty($options['field']) && is_string($options['field'])) {
					$options['field'] = trim($options['field'], '-');
					if (!empty($options['field']))
						$hasField = true;
				}
			}
			if (!$hasField) {
				$options['field'] = preg_replace_callback('#([A-Z])#', function ($matches) {
					return '-' . strtolower($matches[1]);
				}, $name);
			}
			$field['field'] = $options['field'];
			if (!empty($options['shortcut']) && is_string($options['shortcut'])) {
				$options['shortcut'] = trim(strtolower($options['shortcut']), '-');
			}
			if (empty($options['shortcut']) || !is_string($options['shortcut'])) {
				$options['shortcut'] = false;
			}
			$field['shortcut'] = $options['shortcut'];
			$field['default'] = call_user_func($typeHandle, $field['type'], $field['default'], $field['args']);
			$this->guide[$name] = $field;

			// 顺便过滤argv的数据
			if (isset($argv) && !empty($data)) {
				$hasValue = true;
				$newData[$name] = $field['default'];
				if (isset($field['field']) && isset($data[$field['field']])) {
					$newData[$name] = $data[$field['field']];
					$used[$field['field']] = 1;
				} elseif (isset($field['shortcut']) && isset($data[$field['shortcut']])) {
					$newData[$name] = $data[$field['shortcut']];
					$used[$field['shortcut']] = 1;
				} else {
					$hasValue = false;
				}
				if ($hasValue) {
					$newData[$name] = call_user_func($typeHandle, $field['type'], $newData[$name], $field['args']);
				}
			}
		}

		if (isset($argv) && !empty($data)) {
			$diff = array_diff_key($data, $used);
			foreach ($diff as $field => $value) {
				if (is_string($field))
					$field = trim($field, '-');
				if (!isset($newData[$field]))
					$newData[$field] = $this->verifyTypeValue('any', $value);
			}
			if (!empty($newData))
				$this->argv->setData($newData);
		}
		return $this;
	}

	protected function verifyTypeValue($type, $value, array $args = null)
	{
		if ($type === KE_STR) {
			return (string)$value;
		} elseif ($type === KE_BOOL) {
			if ($value === 'false' || $value === '0' || $value === 0 || $value === 0.00)
				return true;
			return (bool)$value;
		} elseif ($type === KE_INT) {
			return (int)$value;
		} elseif ($type === KE_FLOAT) {
			return (float)$value;
		} elseif ($type === KE_ARY) {
			return (array)$value;
		} elseif ($type === 'dir') {
			if (empty($value))
				return false;
			$value = realpath($value);
			if (is_dir($value))
				return $value;
			return false;
		} elseif ($type === 'file') {
			if (empty($value))
				return false;
			$value = realpath($value);
			if (is_file($value) && is_readable($value))
				return $value;
			return false;
		} elseif ($type === 'realpath') {
			if (empty($value))
				return KE_SCRIPT_DIR;
			return realpath($value);
		} elseif ($type === 'json') {
			$decode = json_decode($value, true);
			return $decode;
		} elseif (($type === 'concat' || $type === 'dirs' || $type === 'files') && isset($args[0])) {
			if (empty($value))
				return [];
			$value = explode($args[0], $value);
			$value = array_filter($value); // 过滤空值
			if ($type === 'dirs') {
				foreach ($value as & $item) {
					$item = $this->verifyTypeValue($item, 'dir', $args);
				}
			} elseif ($type === 'files') {
				foreach ($value as & $item) {
					$item = $this->verifyTypeValue($item, 'file', $args);
				}
			}
			return $value;
		} else {
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

	public function getGuide()
	{
		if (!$this->isInitGuide)
			$this->initGuide();
		return $this->guide;
	}

	public function execute()
	{
		return $this;
	}
}