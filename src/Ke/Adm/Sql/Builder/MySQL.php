<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/20 0020
 * Time: 3:53
 */

namespace Ke\Adm\Sql\Builder;

use Ke\Adm\Adapter\DatabaseImpl as Database;

class MySQL implements BuilderImpl
{

	protected static $fetchTypes = [
		Database::FETCH_ALL => true,
		Database::FETCH_ONE => true,
	];

	protected static $fetchStyles = [
		Database::FETCH_ASSOC => true,
		Database::FETCH_NUM   => true,
	];

	public static function mkLimitOffset($limit = 0, $offset = 0)
	{
		if ($limit > 0) {
			if ($offset >= 0)
				return ' LIMIT ' . $offset . ',' . $limit;
			else
				return ' LIMIT ' . $limit;
		}
		return '';
	}

	public static function mkOrder($value)
	{
		if (empty($value) || !is_string($value))
			return '';
		return " ORDER BY {$value}";
	}

	public static function mkGroup($value)
	{
		if (empty($value) || !is_string($value))
			return '';
		return " GROUP BY {$value}";
	}

	public static function mkSelectByArray(array & $cd, & $sql, array & $params = null)
	{
		$query = [
			Database::IDX_SQL          => '', // sql
			Database::IDX_PARAMS       => [], // params
			// fetch type, one or all
			Database::IDX_FETCH_TYPE   => isset($cd['fetchType']) && isset(self::$fetchTypes[$cd['fetchType']]) ?
				$cd['fetchType'] : Database::FETCH_ONE,
			// fetch style, assoc or num
			Database::IDX_FETCH_STYLE  => isset($cd['fetchStyle']) && isset(self::$fetchTypes[$cd['fetchStyle']]) ?
				$cd['fetchStyle'] : Database::FETCH_ASSOC,
			// fetch column
			Database::IDX_FETCH_COLUMN => null,
		];
		if (isset($cd['fetchColumn'])) {
			$query[Database::IDX_FETCH_COLUMN] = $cd['fetchColumn'];
		}

		if (empty($cd['select']))
			$cd['select'] = '*';
		if (empty($cd['from']))
			$cd['from'] = '';

		// 初始化sql和params
		$sql = "SELECT {$cd['select']} FROM {$cd['from']}";

		if (!isset($params))
			$params = [];
		$limit = !empty($cd['limit']) && is_numeric($cd['limit']) ? intval($cd['limit']) : 0;
		$offset = !empty($cd['offset']) && is_numeric($cd['offset']) ? intval($cd['offset']) : 0;

		if (!empty($cd['join']) && is_string($cd['join']))
			$sql .= ' ' . $cd['join'];

		$whereSql = null;
		static::mkQueryByArray($cd, $whereSql, $params);
		if (!empty($whereSql))
			$sql .= $whereSql;

		$havingSql = null;
		if (!empty($cd['having'])) {
			static::mkBySql($cd['having'], $havingSql, $params);
		}
		if ($havingSql != null)
			$sql .= ' HAVING ' . $havingSql;

		if (isset($cd['group']))
			$sql .= static::mkGroup($cd['group']);
		if (isset($cd['order']))
			$sql .= static::mkOrder($cd['order']);

		$sql .= static::mkLimitOffset($limit, $offset);

		$query[Database::IDX_SQL] = $sql;
		$query[Database::IDX_PARAMS] = $params;

		return $query;
	}

	protected static function mkQueryByArray(array & $cd, & $sql, array & $params)
	{
		if (!empty($cd['where']))
			static::mkWhere($cd['where'], $sql, $params);
		if (!empty($cd['in']))
			static::mkIn($cd['in'], $sql, $params, self::MARRY_AND, self::WHERE_IN);
		if (!empty($cd['notin']))
			static::mkIn($cd['notin'], $sql, $params, self::MARRY_AND, self::WHERE_NOT_IN);
		if (!empty($cd['orin']))
			static::mkIn($cd['orin'], $sql, $params, self::MARRY_OR, self::WHERE_IN);
		if (!empty($cd['ornotin']))
			static::mkIn($cd['ornotin'], $sql, $params, self::MARRY_OR, self::WHERE_NOT_IN);
		if (!empty($cd['between']))
			static::mkBetween($cd['between'], $sql, $params);
//		return $this;
	}

	protected static function mkWhere($where, & $sql, array & $params)
	{
		$tempSql = '';
		if (empty($where))
			return;
		$type = gettype($where);
		if ($type === KE_STR)
			$tempSql = $where;
		elseif ($type === KE_ARY) {
			if (!empty($where[0]) && is_string($where[0])) {
				$tempSql = array_shift($where);
				if (!empty($where) && is_array($where)) {
					foreach ($where as $val)
						$params[] = $val;
				}
			}
			// 去掉了bySql的查询
		}
		if (!empty($tempSql))
			$sql .= (stripos($sql, 'where') === false ? ' WHERE ' : null) .
				$tempSql;
		return;
	}

	protected static function mkIn(
		$in,
		& $sql = null,
		& $params = [],
		$marryMode = self::MARRY_AND,
		$type = self::WHERE_IN
	)
	{
		if (empty($in)) return;
		if (is_array($in)) {
			$genSql = null;
			foreach ($in as $key => $val) {
				if ($val === 0 || $val === '0' || $val === false)
					$val = '0';
				elseif ($val === null)
					$val = '';
				if (is_string($val) && strpos($val, ',') !== false)
					$val = explode(',', $val);
				if (is_string($val) || is_numeric($val)) {
					$params[] = $val;
					$genSql .= (empty($genSql) ? null : ' AND ') . $key . ($type === self::WHERE_NOT_IN ? ' != ?' : ' = ?');
				} elseif (is_array($val)) {
					$tempSql = null;
					foreach ($val as &$v) {
						$v = trim($v);
//                        if (empty($v) && $v !== 0 && $v !== '0') continue;
						$tempSql .= !$tempSql ? '?' : ',?';
						$params[] = $v;
					}
					$genSql .= (empty($genSql) ? null : ' AND ') . $key . ($type === self::WHERE_NOT_IN ? ' NOT IN ' : ' IN ') . ' (' . $tempSql . ')';
				}
			}
			$sql .= (stripos($sql, 'where') === false ? ' WHERE ' : $marryMode) . $genSql;
		}
		return;
	}

	protected static function mkBetween($between, & $sql = null, & $params = [])
	{
		if (empty($between))
			return;
		$betweenType = gettype($between);
		if ($betweenType === KE_STR) {
			$between = [$between];
			$betweenType = KE_ARY;
		}
		if ($betweenType !== KE_ARY)
			return;
		$marryMapping = ['and' => self::MARRY_AND, 'or' => self::MARRY_OR];
		foreach ($between as $row) {
			$type = gettype($row);
			if ($type === KE_STR) {
				// 字符串类型，要过滤一下空格
				$row = explode(',', $row);
				foreach ($row as & $item) {
					$item = trim($item);
				}
				$type = KE_ARY;
			}
			if ($type !== KE_ARY)
				continue;
			// 最小长度为3,
			$count = count($row);
			if ($count < 3)
				continue;
			// 找出最基本的字段
			// 0 => field
			// 1 => $value1
			// 2 => $value2
			list($field, $value1, $value2) = $row;
			if (empty($field) || !is_string($field))
				continue;
			$hasNot = false;
			$marry = self::MARRY_AND;

			if (isset($row[3])) {
				$row3 = strtolower(trim($row[3]));
				// array('id', 1, 3, 'and'), array('id', 1, 3, 'or')
				// 改变marry模式
				if (isset($marryMapping[$row3])) {
					$marry = $marryMapping[$row3];
				}
				// array('id', 1, 3, 'not')
				// 确定是不是否定查询
				elseif ($row3 === 'not') {
					$hasNot = true;
				}
			}
			// array('id', 1, 3, 'and', 'not'), array('id', 1, 3, 'or', 'not')
			if (isset($row[4]) && strtolower(trim($row[4])) === 'not') {
				$hasNot = true;
			}
			$invalid = false;
			$not = $hasNot ? ' NOT ' : ' ';
			// 数值查询，还是需要从小到大排列的
			if (is_numeric($value1) && is_numeric($value2)) {
				$invalid = true;
				if ($value1 < $value2) {
					$params[] = $value1;
					$params[] = $value2;
				} else {
					$params[] = $value2;
					$params[] = $value1;
				}
			} elseif (is_string($value1) && is_string($value2)) {
				$invalid = true;
				if ($value1 < $value2) {
					$params[] = $value1;
					$params[] = $value2;
				} else {
					$params[] = $value2;
					$params[] = $value1;
				}
			}
			if ($invalid)
				$sql .= (empty($sql) ? ' WHERE ' : $marry) . "{$field}{$not}BETWEEN ? AND ?";
		}
	}

	protected function mkBySql($by, & $sql = null, & $params = [])
	{
		if (empty($by)) return;
		$sql = empty($sql) ? null : ' AND ';
		if (is_array($by)) {
			foreach ($by as $key => $val) {
				$operator = '=';
				if (is_array($val)) {
					if (!empty($val[1])) {
						$operator = $val[0];
						$val = $val[1];
					} else
						$val = $val[0];
				}
				$sql .= (empty($sql) ? null : ' AND ') .
					$key . ' ' . $operator . ' ?';
				$params[] = $val;
			}
		}
	}
}