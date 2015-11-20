<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/19 0019
 * Time: 20:14
 */

namespace Ke\Adm\Sql;


class Select extends Query
{

	protected $command = self::SELECT;

	protected $fields = '*';

	protected $joins = [];

	public function __construct($table = null, $fields = null)
	{
		if (isset($table))
			$this->from($table);
		if (isset($fields))
			$this->select($fields);
	}

	public function select($fields, $table = null)
	{
		$fields = $this->filterFields($fields, $table);
		if ($fields !== false)
			$this->fields = $fields;
		return $this;
	}

	protected function filterFields($fields, $table = null)
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
						$field = $this->filterFields($field, $key);
					} else {
						$field = $this->filterFields($field, $table);
					}
				}
				return implode(',', $fields);
			}
		}
		return false;
	}

	public function from($table, $as = null)
	{
		return $this->setTable($table, $as);
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


	public function buildSql()
	{
		$this->clearClosure();
		$sql = $this->command . ' ' . $this->fields . ' FROM ' . $this->table;
		if (!empty($this->tableAs))
			$sql .= ' ' . $this->tableAs;
		if (!empty($this->joins)) {
			$sql .= ' ' . implode(' ', $this->joins);
		}
		$sql .= $this->buildWhereSql();
		if (!empty($this->group))
			$sql .= ' GROUP BY ' . $this->group;
		if (!empty($this->order))
			$sql .= ' ORDER BY ' . $this->order;
		$sql .= call_user_func([$this->builderClass, 'mkLimitOffset'], $this->limit, $this->offset);
		return $sql;
	}
}