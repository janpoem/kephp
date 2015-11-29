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

class Filter
{

	const SERIALIZE_CONCAT = 'concat';

	const SERIALIZE_JSON = 'json';

	const SERIALIZE_PHP = 'php';

	const SERIALIZE_IGBINARY = 'igbinary';

	const CONCAT_DELIMITER = ',';

	public function filterColumn(array $column, &$value = null, Model $obj = null, $process = null)
	{
		if (!empty($column['serialize'])) {
			if ($process === Model::ON_INIT)
				return $this->unSerialize($value);
			return $this->serialize($value, $column['serialize'][0], $column['serialize'][1]);
		}
		// options的值不再filter进行处理，而在validate进行验证处理
		// 剩下可能的值类型过滤
		if (!empty($column['bool'])) {
			$value = (bool)$value;
		}
		elseif (!empty($column['timestamp'])) {
			$value = $this->filterTimestamp($column, $value, $obj, $process);
		}
		elseif (!empty($column['numeric'])) {
			$value = $this->filterNumeric($column, $value, $obj, $process);
		}
		else {
			$value = $this->filterString($column, $value, $obj, $process);
		}
		return $value;
	}

	public function filterTimestamp(array $column, &$value, Model $obj = null, $process = null)
	{
		if (empty($value))
			$value = 0;
		elseif (is_numeric($value))
			$value = (int)$value;
		elseif (is_string($value)) {
			if ($process === Model::ON_INIT)
				return $value;
			$value = strtotime($value);
		}
		// 其他的可能性的值得，还是需要默认转为0
		else
			$value = 0;
		return $value;
	}

	public function filterNumeric(array $column, &$value, Model $object = null, $process = null)
	{
		// todo: 整形的处理，需要增加unsigned类型的处理
		if ($column['numeric'] === 1) {
			// int类型
			if (($value = (int)$value) === false) // 转型失败
				$value = 0;
		}
		elseif ($column['numeric'] === 2) {
			// bigint，注意，因为从数据库中取出的bigint，php自动处理为字符串，所以这里也作为字符串处理
			if (!is_numeric($value))
				$value = '0';
			else
				$value = (string)$value;
		}
		elseif ($column['numeric'] >= 3) {
			if (($value = (float)$value) === false) // 转型失败
				$value = 0;
			elseif ($column['numeric'] > 3 && $value !== 0)
				$value = round($value, $column['numeric'] - 3);
		}
		return $value;
	}

	public function filterString(array $column, &$value, Model $object = null, $process = null)
	{
		if ($value === null) $value = ''; // null的可能性是比较高的
		elseif ($value === false) $value = '0';
		elseif ($value === true) $value = '1';
		else {
			$type = gettype($value);
			// 数组和资源类型，就不做字符转换的处理了。
			if ($type === KE_ARY || $type === KE_RES)
				$value = '';
			elseif ($type === KE_OBJ) {
				if (is_callable($value, '__toString'))
					$value = (string)$value;
				else
					$value = '';
			}
			else
				$value = trim($value);
		}
		if (!empty($column['trim']))
			$value = trim($value, $column['trim']);
		if (!empty($column['ltrim']))
			$value = ltrim($value, $column['ltrim']);
		if (!empty($column['rtrim']))
			$value = rtrim($value, $column['rtrim']);
		// 小写、大写只能是其中一种
		if (!empty($column['lower']))
			$value = mb_strtolower($value);
		elseif (!empty($column['upper']))
			$value = mb_strtoupper($value);
		// 移除html标签
		// 没定义的时候，默认强制删除html标签
		// 只有当html不为空，且不为entity的时候，才会保留html标签
		if (empty($column['html']))
			$value = strip_tags($value);
		elseif ($column['html'] === 'entity')
			$value = htmlentities($value, ENT_COMPAT);
		return $value;
	}

	public function isSerializeValue($value, array &$matches = null)
	{
		return preg_match('#^(json|php|concat|igbinary)(?:\[([^\[\]]))?:([\s\S]*)$#m', $value, $matches);
	}

	public function serialize($value, $scheme, $param = null)
	{
		$type = gettype($value);
		// 要先检查，如果data不是以下类型，则表示可以安全执行字符串检查
		if ($type !== KE_ARY && $type !== KE_OBJ && $type !== KE_RES) {
			// 如果检查本身已经带有序列化的标记，则不管，直接返回值
			if ($this->isSerializeValue($value)) {
				return $value;
			}
		}
		if ($type === KE_RES)
			$value = ''; // 资源类型不做序列化
		if ($scheme === self::SERIALIZE_JSON) {
			return 'json:' . json_encode($value);
		}
		elseif ($scheme === self::SERIALIZE_CONCAT) {
			if (empty($param) || !is_string($param))
				$param = self::CONCAT_DELIMITER;
			if (!is_array($value))
				$value = (array)$value;
			return 'concat[' . $param . ']:' . implode($param, $value);
		}
		elseif ($scheme === self::SERIALIZE_PHP) {
			return 'php:' . serialize($value);
		}
		elseif ($scheme === self::SERIALIZE_IGBINARY) {
			return 'igbinary:' . igbinary_serialize($value);
		}
		return $value;
	}

	public function unSerialize($value)
	{
		if (empty($value))
			return $value;
		$type = gettype($value);
		if ($type === KE_ARY || $type === KE_OBJ || $type === KE_RES)
			return $value;
		if ($this->isSerializeValue($value, $matches)) {
			list(, $scheme, $param, $str) = $matches;
			if ($scheme === self::SERIALIZE_JSON) {
				return json_decode($value, true);
			}
			elseif ($scheme === self::SERIALIZE_CONCAT) {
				if (empty($param) || !is_string($param))
					$param = self::CONCAT_DELIMITER;
				return explode($param, $value);
			}
			elseif ($scheme === self::SERIALIZE_PHP) {
				return unserialize($value);
			}
			elseif ($scheme === self::SERIALIZE_IGBINARY) {
				return igbinary_unserialize($value);
			}
		}
		return $value;
	}
}