<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm\Adapter\Database;

use Ke\Adm\Exception;
use Ke\Adm\Utils\SqlQuery;

class PdoMySQL extends PdoAbs
{

	const MARRY_OR = ' OR ';

	const MARRY_AND = ' AND ';

	const WHERE_IN = 1;

	const WHERE_NOT_IN = 0;

	public function getDSN(array $config)
	{
		$dsn = "mysql:dbname={$config['db']};host={$config['host']}";
		if (!empty($config['port']))
			$dsn .= ";port={$config['port']}";
		return $dsn;
	}

	protected function onConnect()
	{
		$this->getConnector()->query("set names '{$this->config['charset']}'");
	}

	public function find($cd, array $params = null)
	{
		if (empty($cd))
			throw new Exception(Exception::INVALID_CONDITIONS, [$this->remote, static::class]);
		if ($cd instanceof SqlQuery) {
			$query = $cd->mkSelectQuery();
		} else {
			$type = gettype($cd);
			if ($type === KE_OBJ) {
				$type = KE_ARY;
				$cd = get_object_vars($cd);
			}
			$sql = null;
			if ($type === KE_STR) {
				$sql = trim($cd);
			} elseif ($type === KE_ARY) {
				$this->mkSelect($cd, $sql, $params);
			}
			if (empty($sql))
				throw new Exception(Exception::INVALID_CONDITIONS, [$this->remote, static::class]);
			$query = [
				// sql
				self::IDX_SQL          => $sql,
				// params
				self::IDX_PARAMS       => $params,
				// fetch type, one or all
				self::IDX_FETCH_TYPE   => (empty($cd['fetch']) || ($cd['fetch'] !== self::FETCH_ONE && $cd['fetch'] !== self::FETCH_ALL)) ? self::FETCH_ONE : $cd['fetch'],
				// fetch style, assoc or num
				self::IDX_FETCH_STYLE  => !empty($cd['array']) ? self::FETCH_NUM : self::FETCH_ASSOC,
				// fetch column
				self::IDX_FETCH_COLUMN => isset($cd['fetchColumn']) ? $cd['fetchColumn'] : null,
			];
		}
		$this->operation = self::OPERATION_READ;
		return call_user_func_array([$this, 'query'], $query);
	}

	public function count($conditions)
	{
		if ($conditions instanceof SqlQuery) {
			$conditions->select('COUNT(*) as rs_count');
			$conditions->fetch(self::FETCH_ONE, null, 0);
			$conditions->order('');
			$query = [];
			if (!empty($conditions->group)) {
				$sql = $conditions->mkSelectSql();
				$conditions->select('COUNT(*) as rs_count');
				$conditions->table("({$sql}) t_count");
				$conditions->group('');
				$query = $conditions->mkSelectQuery();
			}
			else {
				$query = $conditions->mkSelectQuery();
			}
			$this->operation = self::OPERATION_READ;
			$count = call_user_func_array([$this, 'query'], $query);
			return (int)$count;
		}
		elseif (is_array($conditions)) {
			$conditions['fetch'] = self::FETCH_ONE;
			$conditions['select'] = 'COUNT(*) as rs_count';
			unset($conditions['order']);
			$sql = null;
			$params = [];
			if (!empty($conditions['group'])) {
				$from = null;
				$this->mkSelect($conditions, $from, $params);
				$newConditions = [
					'select' => 'COUNT(*) as rs_count',
					'from'   => "({$from}) t_count",
				];
				$this->mkSelect($newConditions, $sql, $params);
			} else {
				$this->mkSelect($conditions, $sql, $params);
			}
			$this->operation = self::OPERATION_READ;
			$result = $this->query($sql, $params, self::FETCH_ONE, self::FETCH_NUM, 0);
			return (int)$result;
		}
	}

	public function insert($table, array $data)
	{
		$keys = [];
		$placeholder = [];
		$params = [];
		foreach ($data as $key => $val) {
			$keys[] = $key;
			$placeholder[] = '?';
			$params[] = $val;
		}
		$sql = 'INSERT INTO ' . (string)$table .
			' (' . implode(', ', $keys) . ')' .
			' VALUES (' . implode(', ', $placeholder) . ')';
		$this->operation = self::OPERATION_WRITE;
		return $this->execute($sql, $params);
	}

	public function lastInsertId($name = null)
	{
		// 取得最后插入id，肯定是写操作，所以要取得写的
		$this->operation = self::OPERATION_WRITE;
		return $this->getConnector()->lastInsertId();
	}

	public function update($table, $conditions = null, array $data = null)
	{
//		if (empty($target))
//			throw new Exception(array('adm.update_unset_target', $this->remote));
		if (empty($data))
			return 0;
		$type = gettype($conditions);
		$params = [];
		$fields = [];
		foreach ($data as $key => $val) {
			// 这个改法还要再测试一段时间才能知道有没有副作用
			// @field + value
			// @children_count + 1
			if (preg_match('#(?:\@([\w]+))[\s\t]*([\+\-\*\/])[\s\t]*(.*)#i', $val, $matches)) {
				list(, $field, $symbol, $val) = $matches;
				$fields[] = "{$key} = {$field} {$symbol} ?";
				$params[] = $val;
			} else {
				$fields[] = "{$key} = ?";
				$params[] = $val;
			}
		}
		$sql = 'UPDATE ' . (string)$table . ' SET ' . implode(', ', $fields);
		$whereSql = '';
		if ($type === KE_ARY) {
			// 既没有设置in查询，也没有设置where查询
//            if (!isset($target['in']) && !isset($target['where'])) {
//                $target['in'] = $target;
//            }
			$this->mkQuery($conditions, $whereSql, $params);
		}
		$whereSql = trim($whereSql);
		if (!empty($whereSql))
			$sql .= " {$whereSql}";
		$this->operation = self::OPERATION_WRITE;
		return $this->execute($sql, $params);
	}

	public function delete($table, $conditions = null)
	{
		$type = gettype($conditions);
		$sql = 'DELETE FROM ' . (string)$table;
		$params = [];
		$whereSql = '';
		if ($type === KE_ARY) {
			// 既没有设置in查询，也没有设置where查询
//            if (!isset($target['in']) && !isset($target['where'])) {
//                $target['in'] = $target;
//            }
			$this->mkQuery($conditions, $whereSql, $params);
		}
		$whereSql = trim($whereSql);
		if (!empty($whereSql))
			$sql .= " {$whereSql}";
		$this->operation = self::OPERATION_WRITE;
		return $this->execute($sql, $params);
	}

	public function truncate($table)
	{
		$this->operation = self::OPERATION_WRITE;
		return $this->execute('TRUNCATE TABLE ' . (string)$table);
	}

//	public function mkSelectQuery(&$cd, &$sql, &$params = null)
//	{
//		if ($cd instanceof SqlQuery)
//			return $cd->mkSelectQuery();
//		if (empty($cd) || !is_array($cd))
//			throw new Exception(Exception::INVALID_CONDITIONS, [$this->remote, static::class]);
//		$this->mkSelect($cd, $sql, $params);
//		$query = [
//			// sql
//			self::IDX_SQL          => $sql,
//			// params
//			self::IDX_PARAMS       => $params,
//			// fetch type, one or all
//			self::IDX_FETCH_TYPE   => (empty($cd['fetch']) || ($cd['fetch'] !== self::FETCH_ONE && $cd['fetch'] !== self::FETCH_ALL)) ? self::FETCH_ONE : $cd['fetch'],
//			// fetch style, assoc or num
//			self::IDX_FETCH_STYLE  => !empty($cd['array']) ? self::FETCH_NUM : self::FETCH_ASSOC,
//			// fetch column
//			self::IDX_FETCH_COLUMN => isset($cd['fetchColumn']) ? $cd['fetchColumn'] : null,
//		];
//		return $query;
//	}

	public function mkSelect(&$cd, &$sql, &$params = null)
	{
		if (empty($cd['select']))
			$cd['select'] = '*';
		if (empty($cd['from']))
			$cd['from'] = '';

		$sql = "SELECT {$cd['select']} FROM {$cd['from']}";
		if (!isset($params))
			$params = [];

		$limit = !empty($cd['limit']) && is_numeric($cd['limit']) ? intval($cd['limit']) : 0;
		$offset = !empty($cd['offset']) && is_numeric($cd['offset']) ? intval($cd['offset']) : 0;

		if (!empty($cd['join']) && is_string($cd['join']))
			$sql .= ' ' . $cd['join'];

		$whereSql = null;
		$this->mkQuery($cd, $whereSql, $params);
		if (!empty($whereSql))
			$sql .= $whereSql;

		$havingSql = null;
		if (!empty($cd['having'])) {
			$this->mkBySql($cd['having'], $havingSql, $params);
		}
		if ($havingSql != null)
			$sql .= ' HAVING ' . $havingSql;

		if (!empty($cd['group']) && is_string($cd['group'])) {
			$sql .= ' GROUP BY ' . $cd['group'];
		}

		if (!empty($cd['order']) && is_string($cd['order']))
			$sql .= ' ORDER BY ' . $cd['order'];

		$sql .= $this->mkLimit($limit, $offset);
	}

	public function mkLimit($limit = 0, $offset = 0)
	{
		if ($limit > 0) {
			if ($offset >= 0)
				return ' LIMIT ' . $offset . ',' . $limit;
			else
				return ' LIMIT ' . $limit;
		}
		return '';
	}

	protected function mkQuery(array & $cd, & $sql, array & $params)
	{
		if (!empty($cd['where']))
			$this->mkWhere($cd['where'], $sql, $params);
		if (!empty($cd['in']))
			$this->mkIn($cd['in'], $sql, $params, self::MARRY_AND, self::WHERE_IN);
		if (!empty($cd['notin']))
			$this->mkIn($cd['notin'], $sql, $params, self::MARRY_AND, self::WHERE_NOT_IN);
		if (!empty($cd['orin']))
			$this->mkIn($cd['orin'], $sql, $params, self::MARRY_OR, self::WHERE_IN);
		if (!empty($cd['ornotin']))
			$this->mkIn($cd['ornotin'], $sql, $params, self::MARRY_OR, self::WHERE_NOT_IN);
//		if (!empty($cd['between']))
//			$this->mkBetween($cd['between'], $sql, $params);
		return $this;
	}

	protected function mkWhere($where, & $sql, array & $params)
	{
		$tempSql = '';
		if (empty($where))
			return $this;
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
		return $this;
	}

	protected function mkIn(
		$in,
		&$sql = null,
		&$params = [],
		$marryMode = self::MARRY_AND,
		$type = self::WHERE_IN
	) {
		if (empty($in)) return $this;
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
		return $this;
	}

	protected function mkBetween($between, & $sql = null, & $params = [])
	{
		if (empty($between))
			return $this;
		$betweenType = gettype($between);
		if ($betweenType === KE_STR) {
			$between = [$between];
			$betweenType = KE_ARY;
		}
		if ($betweenType !== KE_ARY)
			return $this;
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