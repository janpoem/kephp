<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/20 0020
 * Time: 3:54
 */

namespace Ke\Adm\Sql;


use Ke\Adm\Sql\Builder\BuilderImpl;
use Ke\Adm\Sql\Builder\MySQL;

abstract class Query
{

	const SELECT = 'SELECT';

	const INSERT = 'INSERT';

	const DELETE = 'DELETE';

	const UPDATE = 'UPDATE';

	const MARRY_AND = 'AND';

	const MARRY_OR = 'OR';

	const LEFT_JOIN = 'LEFT JOIN';

	const RIGHT_JOIN = 'RIGHT JOIN';

	const INNER_JOIN = 'INNER JOIN';

	const OUTER_JOIN = 'OUTER JOIN';

	const ASC = 'ASC';

	const DESC = 'DESC';

	protected $command = '';

	protected $table = '';

	protected $tableAs = null;

	protected $where = [];

	protected $whereParams = [];

	protected $order = '';

	protected $group = '';

	protected $limit = 0;

	protected $offset = 0;

	protected $marry = self::MARRY_AND;

	protected $not = false;

	protected $closure = false;

	protected $closureDeep = [];

	protected $closureAutoId = 0;

	protected $builderClass = MySQL::class;

	public static function newSelect($table = null, $fields = null)
	{
		return new Select($table, $fields);
	}

	public static function newInsert($table = null, array $insert = null)
	{
		return new Insert($table, $insert);
	}

	public static function newUpdate($table = null, array $update = null)
	{
		return new Update($table, $update);
	}

	public static function newDelete($table = null)
	{
		return new Delete($table);
	}

	public static function matchAs($str, array &$matches = null)
	{
		return preg_match('#([^\s]+)(?:[\s\t]+(?:as[\s\t]+)?([^\s]+))?#i', $str, $matches);
	}

	public function setBuilderClass($class)
	{
		if (is_subclass_of($class, BuilderImpl::class))
			$this->builderClass = $class;
		return $this;
	}

	public function setTable($table, $as = null)
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
//		$index = $as;
//		if (empty($index))
//			$index = $this->tableAutoId++;
//		$this->tablesIndex[$table] = $index;
//		$this->tables[$index] = $table;
		return $this;
	}


	public function turnOr()
	{
		$this->marry = self::MARRY_OR;
		return $this;
	}

	public function turnAnd()
	{
		$this->marry = self::MARRY_AND;
		return $this;
	}

	public function where()
	{
		$args = func_get_args();
		if (empty($args))
			return $this;
		$sql = trim((string)array_shift($args));
		if (empty($sql))
			return $this;
		$this->pushWhere($sql, $args);
		return $this;
	}

	public function in($field, $values = null)
	{
		if (empty($field))
			return $this;
		if (is_array($field)) {
			$this->startClosure();
			foreach ($field as $f => $values) {
				$this->in($f, $values);
			}
			$this->rollClosure(1);
			return $this;
		}
		if (!is_string($field))
			return $this;
		$count = func_num_args();
		if ($count <= 1) {
			return $this;
		}
		if ($count === 2) {
			// $this->in('id', [1, 2, 3]);
			if (!is_array($values))
				$values = (array)$values;
		} elseif ($count > 2) {
			$values = func_get_args();
			array_shift($values);
		}
		if (empty($values))
			return $this;
		$holder = trim(str_repeat('?,', count($values)), ',');
		$in = $this->not ? 'NOT IN' : 'IN';
		$sql = $field . ' ' . $in . ' (' . $holder . ')';
		$this->pushWhere($sql, $values);
		return $this;
	}

	public function notin()
	{
		$this->not = true;
		call_user_func_array([$this, 'in'], func_get_args());
		$this->not = false;
		return $this;
	}

	public function startClosure()
	{
		$this->closure = true;
		$this->closureAutoId += 1;
		$this->closureDeep[] = $this->closureAutoId;
		return $this;
	}

	public function rollClosure($deep = 1)
	{
//		if (!isset($marry))
//			$marry = self::MARRY_AND;
//		else {
//			$marry = strtoupper($marry);
//			if ($marry !== self::MARRY_AND && $marry !== self::MARRY_OR)
//				$marry = self::MARRY_AND;
//		}
//

//		$currentMarry = $this->marry;
//		if ($marry !== $currentMarry)
//			$this->marry = $marry;
		$total = count($this->closureDeep);
		if ($total <= 0 || $deep < 1)
			return $this;
		if ($deep >= $total)
			$deep = $total - 1;
		$this->closure = false;
		for ($i = $deep; $i >= 0; $i--) {
			$index = $this->closureDeep[$i];
			$this->combineWhere($index);
			unset($this->closureDeep[$i]);
//			$id = $this->closureDeep[$i];
//			if (empty($this->closureBuffer[$id]))
//				continue;
//			call_user_func_array([$this, 'pushWhere'], $this->closureBuffer[$id]);
//			$this->closureBuffer[$id] = null;
//			unset($this->closureDeep[$i]);
		}
//		if ($currentMarry !== $this->marry)
//			$this->marry = $currentMarry;
		return $this;
	}

	public function clearClosure()
	{
		return $this->rollClosure(count($this->closureDeep));
	}


	public function order($value)
	{
		if (empty($value)) {
			$this->order = '';
		} else {
			if (is_array($value))
				$value = implode(',', $value);
			$this->order = trim($value);
		}
		return $this;
	}

	public function group($value)
	{
		if (empty($value)) {
			$this->group = '';
		} else {
			if (is_array($value))
				$value = implode(',', $value);
			$this->group = trim($value);
		}
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

	abstract public function buildSql();

	public function buildWhereSql()
	{
		if (!empty($this->where[0])) {
			$where = $this->where[0];
			$where[0] = 'WHERE';
			return ' ' . implode(' ', $where);
		}
		return '';
	}

	public function buildLimitSql()
	{
		if ($this->limit > 0) {
			if ($this->offset >= 0)
				return ' LIMIT ' . $this->offset . ',' . $this->limit;
			else
				return ' LIMIT ' . $this->limit;
		}
		return '';
	}

	public function __toString()
	{
		return $this->buildSql();
	}

	public function getParams()
	{
		return empty($this->whereParams[0]) ? [] : $this->whereParams[0];
	}

	protected function pushWhere($sql, array $params = [])
	{
		$index = 0;
		if ($this->closure)
			$index = $this->closureDeep[count($this->closureDeep) - 1];

		if (stripos($sql, ' and ') !== false || stripos($sql, ' or ') !== false)
			$sql = "({$sql})";
		$this->where[$index][] = $this->marry;
		$this->where[$index][] = $sql;
		if (!empty($params)) {
			foreach ($params as $item) {
				if ($item === null)
					$item = '';
				$this->whereParams[$index][] = $item;
			}
		}
		return $this;
	}

	protected function combineWhere($index)
	{
		if ($index <= 0 || !isset($this->where[$index]) || empty($this->where[$index]))
			return false;
		$where = $this->where[$index];
		$marry = array_shift($where);
		$sql = implode(' ', $where);
		if (count($where) > 1)
			$sql = "({$sql})";
		$this->where[0][] = $marry;
		$this->where[0][] = $sql;
		if (!empty($this->whereParams[$index])) {
			foreach ($this->whereParams[$index] as $item) {
				$this->whereParams[0][] = $item;
			}
		}
		unset($this->where[$index], $this->whereParams[$index]);
		return true;
	}
}