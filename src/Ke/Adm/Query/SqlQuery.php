<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/22
 * Time: 2:24
 */

namespace Ke\Adm\Query;

use Ke\Adm\Adapter\DatabaseImpl as Database;

/**
 * Sql查询辅助器
 *
 * todo: insert, update, delete
 *
 * @package Ke\Adm\Query
 */
class SqlQuery
{

	const MARRY_AND = 'AND';

	const MARRY_OR = 'OR';

	const NONE_VALUE = 0;

	const SINGLE_VALUE = 1;

	const DOUBLE_VALUE = 2;

	const MULTI_VALUES = 100;

	const LEFT_JOIN = 'LEFT JOIN';

	const RIGHT_JOIN = 'RIGHT JOIN';

	const INNER_JOIN = 'INNER JOIN';

	const OUTER_JOIN = 'OUTER JOIN';

	protected static $operators = [
		'null'  => [null, self::NONE_VALUE, 'IS NULL'],
		'!null' => [null, self::NONE_VALUE, 'IS NOT NULL'],
		'='     => ['in',],
		'!='    => ['!in',],
		'<>'    => ['!in',],
		'>'     => [null, self::SINGLE_VALUE,],
		'<'     => [null, self::SINGLE_VALUE,],
		'>='    => [null, self::SINGLE_VALUE,],
		'<='    => [null, self::SINGLE_VALUE,],
		'in'    => [null, self::MULTI_VALUES, 'IN', true],
		'!in'   => [null, self::MULTI_VALUES, 'NOT IN', true],
	];

	/** @var array 当前查询关联的数据表 */
	public $table = '';

	public $tableAs = '';

	public $select = '*';

	/** @var array 当前查询的查询条件 */
	public $where = [];

	protected $params = [];

	public $joins = [];

	public $order = '';

	public $group = '';

	public $limit = 0;

	public $offset = 0;

	public $insert = [];

	public $update = [];

	protected $fetchType = Database::FETCH_ONE;

	protected $fetchStyle = Database::FETCH_ASSOC;

	protected $fetchColumn = null;

	public static function matchAs($str, array &$matches = null)
	{
		return preg_match('#([^\s]+)(?:[\s\t]+(?:as[\s\t]+)?([^\s]+))?#i', $str, $matches);
	}

	/**
	 * @param null $table
	 * @param null $select
	 * @return SqlQuery
	 */
	public static function newSelect($table = null, $select = null)
	{
		return (new static($table))->select($select);
	}

	/**
	 * SqlQuery constructor.
	 *
	 * <code>
	 * $query = new SqlQuery(SqlQuery::SELECT, 'users')
	 * </code>
	 *
	 * @param null $table
	 */
	public function __construct($table = null)
	{
		if (isset($table))
			$this->table($table);
	}

	public function table($table, $as = null)
	{
		if (empty($table) || !is_string($table))
			return $this;
		$table = trim($table);
		if (empty($table))
			return $this;
		if (static::matchAs($table, $matches)) {
			$table = $matches[1];
			if (isset($matches[2]))
				$as = $matches[2];
		}
		$this->table = $table;
		if (empty($as) || !is_string($as))
			$as = null;
		$as = trim($as);
		if (empty($as))
			$as = null;
		$this->tableAs = $as;
		return $this;
	}

	public function select($fields, $table = null)
	{
		$fields = $this->filterSelect($fields, $table);
		if ($fields !== false)
			$this->select = $fields;
		return $this;
	}

	protected function filterSelect($fields, $table = null)
	{
		// filter table name
		if (empty($table) || !is_string($table)) {
			$table = null;
		} else {
			$table = trim($table);
			if (empty($table))
				$table = null;
		}
		// generate fields
		if (empty($fields)) {
			$fields = '*';
			if (!empty($table))
				$fields = "`{$table}`.*";
			return $fields;
		} else {
			if (is_string($fields)) {
				$fields = explode(',', $fields);
				foreach ($fields as &$field) {
					$field = trim($field);
					if (!empty($table))
						$field = "`{$table}`.`{$field}`";
					else
						$field = "`{$field}`";
				}
				return implode(',', $fields);
			} elseif (is_array($fields)) {
				foreach ($fields as $key => &$field) {
					if (!empty($key) && is_string($key)) {
						$field = $this->filterSelect($field, $key);
					} else {
						$field = $this->filterSelect($field, $table);
					}
				}
				return implode(',', $fields);
			}
		}
		return false;
	}

	public function where()
	{
		$this->pushWhere(func_get_args(), null, 0, $this->where);
		return $this;
	}

	public function clearWhere()
	{
		$this->where = [];
		$this->params = [];
		return $this;
	}

	protected function pushWhere(array $args, $marry = null, $deep = 0, array &$group = [])
	{
		if ($args[0] === self::MARRY_AND || $args[0] === self::MARRY_OR) {
			$marry = array_shift($args);
		}
		if (empty($marry))
			$marry = self::MARRY_AND;
		if (is_array($args[0])) {
			$passMarry = $marry;
			$newGroup = [];
			foreach ($args as $index => $item) {
				if ($passMarry === null && ($item === self::MARRY_AND || $item === self::MARRY_OR)) {
					$passMarry = $item;
				} else {
					$this->pushWhere($item, $passMarry, $deep + 1, $newGroup);
				}
				if ($index === 0)
					$passMarry = null;
			}
			if (count($newGroup) > 2) {
				$group[] = array_shift($newGroup);
				$group[] = '(' . implode(' ', $newGroup) . ')';
			} else {
				foreach ($newGroup as $item)
					$group[] = $item;
			}
		} else {
			$count = count($args);
			if ($count < 2)
				return $this; // break
			list($field, $operator) = $args;
			if (is_object($operator))
				return $this;

			$sql = $field . ' ';
			$params = &$this->params;
			$values = [];

			if (is_array($operator)) {
				$holder = '';
				array_walk_recursive($operator, function ($value) use (&$holder, &$params) {
					$holder .= '?,';
					$params[] = $value === null ? '' : $value;
				});
				$sql .= 'IN (' . substr($holder, 0, -1) . ')';
			} elseif (isset(static::$operators[$operator])) {
				// 已知操作符
				$options = static::$operators[$operator];
				if (isset($options[0]) && isset(static::$operators[$options[0]]))
					$options = static::$operators[$options[0]];
				if (!isset($options[1]))
					return $this; // break
				$sql .= (empty($options[2]) ? $operator : $options[2]) . ' ';
				if ($options[1] !== self::NONE_VALUE) {
					$limit = $options[1];
					if ($limit === self::MULTI_VALUES)
						$values = array_slice($args, 2);
					else
						$values = array_slice($args, 2, $options[1]); // 尽可能减少后面flatten的数量
					if (empty($values))
						return $this;
					$holder = '';
					$counter = 0;
					array_walk_recursive($values, function ($value) use (&$holder, &$params, &$counter, $limit) {
						if ($limit === self::MULTI_VALUES || $counter < $limit) {
							$holder .= '?,';
							$params[] = $value === null ? '' : $value;
							$counter++;
						}
					});
					$holder = substr($holder, 0, -1);
					if (!empty($options[3])) // true
						$holder = '(' . $holder . ')';
					$sql .= $holder;
				}
			} else {
				// todo: 未知操作符，暂时没做处理
			}
			$group[] = $marry;
			$group[] = $sql;
		}
		return $group;
	}

	public function group($value)
	{
		if (empty($value)) {
			$value = '';
		} else {
			if (is_array($value))
				$value = implode(',', $value);
			$value = trim($value);
		}
		$this->group = $value;
		return $this;
	}

	public function order($value)
	{
		if (empty($value)) {
			$value = '';
		} else {
			if (is_array($value))
				$value = implode(',', $value);
			$value = trim($value);
		}
		$this->order = $value;
		return $this;
	}

	public function limit($value)
	{
		if (!is_numeric($value))
			return $this;
		if ($value < 0)
			$value = 0;
		$value = (int)$value;
		if ($value === false)
			$value = 0;
		$this->limit = $value;
		return $this;
	}

	public function offset($value)
	{
		if (!is_numeric($value))
			return $this;
		if ($value < 0)
			$value = 0;
		$value = (int)$value;
		if ($value === false)
			$value = 0;
		$this->offset = $value;
		return $this;
	}

	public function join($table, $on, $type = self::LEFT_JOIN)
	{
		if (empty($table) || !is_string($table))
			return $this;
		$table = trim($table);
		if (empty($table))
			return $this;
		if (!empty($on) && !is_string($on))
			return $this;
		$as = null;
		if (static::matchAs($table, $matches)) {
			$table = $matches[1];
			if (isset($matches[2]))
				$as = $matches[2];
		}
		$this->joins[$table] = "$type $table $as ON $on";
		return $this;
	}

	public function fetch($type = null, $style = null, $column = null)
	{
		if (isset($type))
			$this->setFetchType($type);
		if (isset($style))
			$this->setFetchStyle($style);
		if (isset($column))
			$this->setFetchColumn($column);
		return $this;
	}

	public function setFetchType($type)
	{
		if ($type !== Database::FETCH_ONE && $type !== Database::FETCH_ALL)
			$type = Database::FETCH_ONE;
		$this->fetchType = $type;
		return $this;
	}

	public function setFetchStyle($style)
	{
		if ($style !== Database::FETCH_ASSOC && $style !== Database::FETCH_NUM)
			$style = Database::FETCH_ASSOC;
		$this->fetchStyle = $style;
		return $this;
	}

	public function setFetchColumn($column)
	{
		if (is_numeric($column)) {
			$column = (int)$column;
			if ($column === false || $column < 0)
				$column = null;
		} else {
			$type = gettype($column);
			if ($type === KE_ARY || $type === KE_OBJ || $type === KE_RES) {
				$column = null;
			} else {
				$column = trim($column);
				if (empty($column))
					$column = null;
			}
		}
		$this->fetchColumn = $column;
		return $this;
	}

	public function getFetch()
	{
		return [$this->fetchType, $this->fetchStyle, $this->fetchColumn];
	}

	public function mkLimitOffsetSql($limit = 0, $offset = 0)
	{
		if ($limit > 0) {
			if ($offset >= 0)
				return ' LIMIT ' . $offset . ',' . $limit;
			else
				return ' LIMIT ' . $limit;
		}
		return '';
	}

	public function mkWhereSql()
	{
		if (!empty($this->where)) {
			$where = $this->where;
			$where[0] = 'WHERE';
			return ' ' . implode(' ', $where);
		}
		return '';
	}

	public function mkSelectSql()
	{
		$sql = 'SELECT ' . $this->select . ' FROM ' . $this->table;
		if (!empty($this->tableAs))
			$sql .= ' ' . $this->tableAs;
		if (!empty($this->joins)) {
			$sql .= ' ' . implode(' ', $this->joins);
		}
		$sql .= $this->mkWhereSql();
		if (!empty($this->group))
			$sql .= ' GROUP BY ' . $this->group;
		if (!empty($this->order))
			$sql .= ' ORDER BY ' . $this->order;
		$sql .= $this->mkLimitOffsetSql($this->limit, $this->offset);
		return $sql;
	}

	public function mkSelectQuery()
	{
		return [
			Database::IDX_SQL          => $this->mkSelectSql(),
			Database::IDX_PARAMS       => $this->params,
			Database::IDX_FETCH_TYPE   => $this->fetchType,
			Database::IDX_FETCH_STYLE  => $this->fetchStyle,
			Database::IDX_FETCH_COLUMN => $this->fetchColumn,
		];
	}
}