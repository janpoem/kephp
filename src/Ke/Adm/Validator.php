<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm;


class Validator
{

//	public function buildErrorMessage(array $error): string
//	{
//		$message = null;
//		if (isset($error[0])) {
//			$message = array_shift($error);
//		} elseif (isset($error['msg'])) {
//			$message = $error['msg'];
//			unset($error['msg']);
//		}
//		return substitute($this->getMessage($message), $error);
//	}

	public function validateModelObject(Model $object, array &$data, $process = null, $isStrict = false)
	{
		$filter = $object->getFilter();
		$shadow = [];
		$columns = $object->getColumns($process);
		$groupColumns = $object->getGroupColumns();
		if ($isStrict) {
			if ($process === Model::ON_UPDATE)
				$shadow = $object->getShadowData();
		}

		foreach (array_keys($data) as $field) {
			$column = isset($columns[$field]) ? $columns[$field] : [];
			// 过滤值，同时必须更新$data
			$data[$field] = $filter->filterColumn($data[$field], $column);
			$isRemove = false;
			if ($isStrict) {
				if (!empty($column['dummy']))
					$isRemove = true;
//				if (!isset($groupColumns['default'][$field]))
//					$isRemove = true;
				if ($process === Model::ON_UPDATE) {
					if (isset($shadow[$field]) && equals($shadow[$field], $data[$field]))
						$isRemove = true;
				}
			}
			if ($isRemove) {
				unset($data[$field]);
				continue;
			}
			$error = $this->validateColumn($field, $data[$field], $column, $object, $process, $isStrict);
			if ($error !== false) {
				$object->setError($field, $error, false); // 不要覆盖已经存在的错误
			}
		}
		return $this;
	}

	public function validateColumn($field, $value, array $column, Model $obj, $process = null, $isStrict = false)
	{
		$require = isset($column['require']) && (bool)$column['require'];
		$allowEmpty = isset($column['empty']) ? (bool)$column['empty'] : !$require;
		$isEmail = isset($column['email']) && (bool)$column['email'] ? true : false;
		$error = false;

		if (!empty($column['numeric'])) {
			if (!is_numeric($value))
				$error = [Model::ERR_NOT_NUMERIC];
			elseif ($column['numeric'] >= 3 && !is_float($value))
				$error = [Model::ERR_NOT_FLOAT];
			// 同时判断
			elseif ((!empty($column['min']) && is_numeric($column['min'])) &&
			        (!empty($column['max']) && is_numeric($column['max'])) &&
			        ($value < $column['min'] || $value > $column['max'])
			) {
				$error = [Model::ERR_NUMERIC_LESS_GREAT_THAN, 'min' => $column['min'], 'max' => $column['max']];
			}
			elseif (!empty($column['min']) && is_numeric($column['min']) && $value < $column['min'])
				$error = [Model::ERR_NUMERIC_LESS_THAN, 'min' => $column['min']];
			elseif (!empty($column['max']) && is_numeric($column['max']) && $value > $column['max'])
				$error = [Model::ERR_NUMERIC_GREET_THAN, 'max' => $column['max']];
		}
		else {
			$length = mb_strlen($value);
			if (!$allowEmpty && $length <= 0)
				$error = [Model::ERR_NOT_ALLOW_EMPTY];
//			elseif ($length > 0 || !$allowEmpty) {
			else {
				// 字符最小长度
				if ((!$allowEmpty || $length > 0) &&
				    (!empty($column['min']) && is_numeric($column['min'])) &&
				    (!empty($column['max']) && is_numeric($column['max'])) &&
				    ($length < $column['min'] || $length > $column['max'])
				) {
					$error = [Model::ERR_STR_LEN_LESS_GREAT_THAN, 'min' => $column['min'], 'max' => $column['max']];
				}
				elseif ((!$allowEmpty || $length > 0) &&
				        (!empty($column['min']) && is_numeric($column['min'])) &&
				        $length < $column['min']
				) {
					$error = [Model::ERR_STR_LEN_LESS_THAN, 'min' => $column['min']];
				}
				// 字符最大长度
				elseif ((!$allowEmpty || $length > 0) &&
				        (!empty($column['max']) && is_numeric($column['max'])) &&
				        $length > $column['max']
				) {
					$error = [Model::ERR_STR_LEN_GREET_THAN, 'max' => $column['max']];
				}
				// 邮箱
				elseif ($isEmail && !$this->isEmail($value, $obj, $process))
					$error = [Model::ERR_NOT_EMAIL];
				elseif (!empty($column['pattern']) && !$this->isMatch($value, $column['pattern'], $obj, $process)) {
					if (!empty($column['sample']))
						$error = [Model::ERR_NOT_MATCH_SAMPLE, 'sample' => $column['sample']];
					else
						$error = [Model::ERR_NOT_MATCH];
				}
				elseif (!empty($column['equal']) &&
				        (!isset($data[$column['equal']]) || !equals($data[$column['equal']], $value))
				) {
					$error = [Model::ERR_NOT_EQUAL, 'equalLabel' => $obj->getLabel($column['equal'])];
				}
				elseif (!empty($column['options']) &&
				        is_array($column['options']) &&
				        !empty($column['inRange']) &&
				        !isset($column['options'][$value])
				) {
					$error = [Model::ERR_NOT_IN_RANGE];
				}
				elseif ($isStrict &&
				        !empty($column['unique']) &&
				        !$this->isUnique($value, $field, $obj, $process)
				) {
					$error = [Model::ERR_DUPLICATE, 'value' => $value];
				}
			}
		}
		return $error;
	}

	public function isEmail($value, Model $obj = null, $process = null)
	{
		return preg_match('/^[0-9a-z][a-z0-9\._-]{1,}@[a-z0-9-]{1,}[a-z0-9]\.[a-z\.]{1,}[a-z]$/i', $value);
	}

	public function isMatch($value, $pattern, Model $obj = null, $process = null)
	{
		if (!empty($pattern) && is_string($pattern)) {
			$pattern = '#' . $pattern . '#i';
			return preg_match($pattern, $value);
		}
		return true;
	}

	public function isUnique($value, $field, Model $obj, $process = null)
	{
		$query = $obj->query(false)->in($field, $value);
		if ($obj->isExists())
			$query->notIn($obj->getReferenceData());
		return $query->count() > 0 ? false : true;
	}
}