<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/20 0020
 * Time: 2:25
 */

namespace Ke\Adm\Sql;


class Insert extends Query
{

	protected $command = self::INSERT;

	protected $insert = [];

	public function __construct($table = null, array $insert = null)
	{
		if (isset($table))
			$this->setTable($table);
	}

	public function insert(array $insert = null)
	{
		if (empty($insert)) {
			$this->insert = [];
		} else {
			foreach ($insert as $field => $value) {
				if (empty($field) || !is_string($field))
					continue;
				if ($value === null)
					$value = '';
				$this->insert[$field] = $value;
			}
		}
		return $this;
	}

	public function buildSql()
	{
		$sql = $this->command . ' INTO ' . $this->table;
		$keys = array_keys($this->insert);
		$holder = trim(str_repeat('?,', count($this->insert)), ',');
		$sql .= ' (' . implode(',', $keys) . ') VALUES (' . $holder . ')';
		return $sql;
	}

	public function getParams()
	{
		return array_values($this->insert);
	}
}