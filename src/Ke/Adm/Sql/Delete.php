<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/20 0020
 * Time: 2:20
 */

namespace Ke\Adm\Sql;


class Delete extends Query
{

	protected $command = self::DELETE;

	public function __construct($table = null)
	{
		if (isset($table))
			$this->setTable($table);
	}

	public function buildSql()
	{
		$this->clearClosure();
		$sql = $this->command . ' ' . $this->table;
		$sql .= $this->buildWhereSql();
		if (!empty($this->group))
			$sql .= ' GROUP BY ' . $this->group;
		if (!empty($this->order))
			$sql .= ' ORDER BY ' . $this->order;
		$sql .= call_user_func([$this->builderClass, 'mkLimitOffset'], $this->limit, $this->offset);
		return $sql;
	}
}