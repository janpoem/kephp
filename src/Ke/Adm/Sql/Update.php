<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/20 0020
 * Time: 1:24
 */

namespace Ke\Adm\Sql;


class Update extends Query
{

	protected $command = self::UPDATE;

	protected $update = [];

	protected $updateParams = [];

	public function __construct($table = null, array $update = null)
	{
		if (isset($table))
			$this->setTable($table);
		if (isset($update))
			$this->set($update);
	}

	public function set($field, $value = null)
	{
		if (empty($field))
			return $this;
		if (is_array($field)) {
			foreach ($field as $key => $value) {
				$this->set($key, $value);
			}
			return $this;
		}
		if (empty($field) || !is_string($field) || empty($field = trim($field)))
			return $this;
		if (!isset($value))
			return $this;
		if (!is_array($value))
			$value = [null, $value];
		if (is_string($value[0]) && !empty($value[0] = trim($value[0])))
			$operator = "{$field} = {$value[0]}";
		else
			$operator = "{$field} = ?";
		$this->update[] = $operator;
		if (isset($value[1]))
			$this->updateParams[] = $value[1];
		return $this;
	}

	public function clearUpdate()
	{
		$this->update = [];
		$this->updateParams = [];
		return $this;
	}

	public function buildSql()
	{
		$this->clearClosure();
		$sql = $this->command . ' ' . $this->table;
		if (!empty($this->update)) {
			$sql .= ' SET ' . implode(',', $this->update);
		}
		$sql .= $this->buildWhereSql();
		if (!empty($this->group))
			$sql .= ' GROUP BY ' . $this->group;
		if (!empty($this->order))
			$sql .= ' ORDER BY ' . $this->order;
		$sql .= call_user_func([$this->builderClass, 'mkLimitOffset'], $this->limit, $this->offset);
		return $sql;
	}

	public function getParams()
	{
		$params = $this->updateParams;
		if (!empty($this->whereParams[0])) {
			foreach ($this->whereParams[0] as $item) {
				$params[] = $item;
			}
		}
		return $params;
	}
}