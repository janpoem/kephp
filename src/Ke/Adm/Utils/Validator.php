<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm\Utils;

use Ke\Adm\Model;

class Validator
{

	const UNKNOWN = 0;
	const NOT_NUMERIC = 10;
	const NOT_FLOAT = 11;
	const NUMERIC_LESS_THAN = 12;
	const NUMERIC_GREET_THAN = 13;
	const NOT_ALLOW_EMPTY = 20;
	const NOT_EMAIL = 21;
	const NOT_MATCH = 22;
	const NOT_MATCH_SAMPLE = 23;
	const STR_LEN_LESS_THAN = 24;
	const STR_LEN_GREET_THAN = 25;
	const NOT_EQUAL = 26;
	const NOT_IN_RANGE = 27;
	const DUPLICATE = 28;

	private static $instances = [];

	protected static $stdMessages = [
		self::UNKNOWN            => '{label}存在未知的{key}错误！',
		self::NOT_FLOAT          => '{label}不是一个有效的浮点数！',
		self::NOT_NUMERIC        => '{label}不是一个有效的数值类型！',
		self::NUMERIC_LESS_THAN  => '{label}的数值不得小于{min}！',
		self::NUMERIC_GREET_THAN => '{label}的数值不得大于{max}！',
		self::NOT_ALLOW_EMPTY    => '{label}为必填字段，不得为空！',
		self::NOT_EMAIL          => '{label}不是一个有效邮箱地址！',
		self::NOT_MATCH          => '{label}不符合指定格式！',
		self::NOT_MATCH_SAMPLE   => '{label}不符合指定格式，正确格式为：{sample}！',
		self::STR_LEN_LESS_THAN  => '{label}的长度不能少于{min}位！',
		self::STR_LEN_GREET_THAN => '{label}的长度不能超过{max}位！',
		self::NOT_EQUAL          => '{label}与{equalLabel}的值不相同！',
		self::NOT_IN_RANGE       => '{label}不在指定的取值范围内！',
		self::DUPLICATE          => '{label}已经存在值为"{value}"的记录！',
	];

	protected $messages = [];

	public static function getInstance($class = null)
	{
		if (empty($class) || !is_string($class) || !class_exists($class) || !is_subclass_of($class, static::class))
			$class = static::class;
		if (!isset(self::$instances[$class]))
			self::$instances[$class] = new $class();
		return self::$instances[$class];
	}

	public function __construct()
	{
		$this->setMessages(static::$stdMessages);
	}

	public function setMessages(array $messages)
	{
		if (empty($this->messages))
			$this->messages = $messages;
		else
			$this->messages = array_merge($this->messages, $messages);
		return $this;
	}

	public function getMessage($key)
	{
		if (isset($this->messages[$key]))
			return $this->messages[$key];
		return $this->messages[self::UNKNOWN];
	}

	public function mkErrorMessage(Model $obj, $field, array $error)
	{
		$message = null;
		if (isset($error[0])) {
			$message = array_shift($error);
		} elseif (isset($error['msg'])) {
			$message = $error['msg'];
			unset($error['msg']);
		}
		if (!isset($error['label']))
			$error['label'] = $obj->getLabel($field);
		return substitute($this->getMessage($message), $error);
	}

	public function validateModelData(Model $obj, array &$data, $process = null, $isStrict = false)
	{
		$filter = $obj->getFilter();
		$errors = null;
		$shadow = [];
		$default = $obj->getGroupColumns('default');
		if ($isStrict) {
			if ($process === Model::ON_UPDATE)
				$shadow = $obj->getShadowData();
		}
		foreach (array_keys($data) as $field) {
			$column = $obj->getColumn($field, $process);
			$data[$field] = $filter->filterColumn($column, $data[$field], $obj, $process);
			if ($isStrict) {
				if (!empty($column['dummy']))
					unset($data[$field]);
				if (!isset($default[$field]))
					unset($data[$field]);
				if ($process === Model::ON_UPDATE) {
					if (isset($shadow[$field]) && equals($shadow[$field], $data[$field]))
						unset($data[$field]);
				}
			}
			if (array_key_exists($field, $data)) {
				$error = $this->validateColumn($column, $field, $data[$field], $data, $obj, $process, $isStrict);
				if (!empty($error)) {
					$errors[$field] = $this->mkErrorMessage($obj, $field, $error);
				}
			}
		}
		return $errors;
	}

	public function validateColumn(
		array $column,
		$field,
		$value,
		array $data,
		Model $obj,
		$process = null,
		$isStrict = false
	) {
		$require = isset($column['require']) && (bool)$column['require'];
		$allowEmpty = isset($column['empty']) ? (bool)$column['empty'] : !$require;
		$isEmail = isset($column['email']) && (bool)$column['email'] ? true : false;
		$error = false;

		if (!empty($column['numeric'])) {
			if (!is_numeric($value))
				$error = [self::NOT_NUMERIC];
			elseif ($column['numeric'] >= 3 && !is_float($value))
				$error = [self::NOT_FLOAT];
			elseif (isset($column['min']) && is_numeric($column['min']) && $value < $column['min'])
				$error = [self::NUMERIC_LESS_THAN, 'min' => $column['min']];
			elseif (isset($column['max']) && is_numeric($column['max']) && $value > $column['max'])
				$error = [self::NUMERIC_GREET_THAN, 'max' => $column['max']];
		} else {
			$length = mb_strlen($value);
			if (!$allowEmpty && $length <= 0)
				$error = [self::NOT_ALLOW_EMPTY];
//			elseif ($length > 0 || !$allowEmpty) {
			else {
				// 字符最小长度
				if (!$allowEmpty && !empty($column['min']) && is_numeric($column['min']) && $length < $column['min'])
					$error = [self::STR_LEN_LESS_THAN, 'min' => $column['min']];
				// 字符最大长度
				elseif (!$allowEmpty && !empty($column['max']) && is_numeric($column['max']) && $length > $column['max'])
					$error = [self::STR_LEN_GREET_THAN, 'max' => $column['max']];
				// 邮箱
				elseif ($isEmail && !$this->isEmail($value, $obj, $process))
					$error = [self::NOT_EMAIL];
				elseif (!empty($column['pattern']) && !$this->isMatch($column['pattern'], $value, $obj, $process)) {
					if (!empty($column['sample']))
						$error = [self::NOT_MATCH_SAMPLE, 'sample' => $column['sample']];
					else
						$error = [self::NOT_MATCH];
				} elseif (!empty($column['equal']) && (!isset($data[$column['equal']]) || !equals($data[$column['equal']], $value))) {
					$error = [self::NOT_EQUAL, 'equalLabel' => $obj->getLabel($column['equal'])];
				} elseif (!empty($column['options']) && is_array($column['options']) && !empty($column['inRange']) && !isset($column['options'][$value])) {
					$error = [self::NOT_IN_RANGE];
				} elseif ($isStrict && !empty($column['unique']) && !$this->isUnique($column, $field, $value, $obj, $process)) {
					$error = [self::DUPLICATE, 'value' => $value];
				}
			}
		}
		return $error;
	}

	public function isEmail($value, Model $obj = null, $process = null)
	{
		return preg_match('/^[0-9a-z][a-z0-9\._-]{1,}@[a-z0-9-]{1,}[a-z0-9]\.[a-z\.]{1,}[a-z]$/i', $value);
	}

	public function isMatch($pattern, $value, Model $obj = null, $process = null)
	{
		if (!empty($pattern) && is_string($pattern)) {
			$pattern = '#' . $pattern . '#i';
			return preg_match($pattern, $value);
		}
		return true;
	}

	public function isUnique($column, $field, $value, Model $obj, $process = null)
	{
		/** @var Model $model */
		$model = get_class($obj);
		if ($process === Model::ON_CREATE) {
			$count = $model::rsCountIn([$field => $value]);
			if ($count > 0)
				return false;
		}
		elseif ($process === Model::ON_UPDATE) {
			$count = $model::rsCount([
				'in' => [$field => $value],
				'notin' => [$obj->getPkField() => $obj->getPk()],
			]);
			if ($count > 0)
				return false;
		}

		return true;
	}
}