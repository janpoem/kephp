<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/30
 * Time: 5:19
 */

namespace Ke\Adm\Adapter\Forge;


use Ke\Adm\Adapter\Database\PdoMySQL;
use Ke\Adm\Adapter\DatabaseImpl;
use Ke\Adm\Exception;
use Ke\Adm\Model;

class MySQLForge
{

	/** @var DatabaseImpl|PdoMySQL */
	private $db = null;

	private $intTypes = [
		'int'      => 1,
		'smallint' => 1,
		'tinyint'  => 1,
	];

	private $floatTypes = [
		'float'  => 1,
		'double' => 1,
	];

	public function __construct(DatabaseImpl $db)
	{
		$this->db = $db;
	}

	public function mkModelVars($model, $tableName)
	{
		$vars = [
			'pkField'   => null,
			'pkAutoInc' => false,
		];
		$tableInfo = $this->getTableInfo($tableName);
		if ($tableInfo === false)
			throw new Exception('DB "{0}": Table "{1}" did not exist', [$this->db->getName(), $tableName]);
		$pkField = $this->getPrimaryKeyColumn($tableName);
		$pkAutoInc = false;

		$dbCols = $this->getTableColumns($tableName);
		$props = [];
		$cols = [];

		foreach ($dbCols as $column) {
			$field = $column['column_name'];
			$type = $column['data_type'];
			$col = [];
			if ($field === $pkField && stripos($column['extra'], 'auto_increment') !== false)
				$pkAutoInc = true;
			$comment = str_replace(['，', '：'], [',', ':'], $column['comment']);
			$comment = explode('|', $comment);
			if (!empty(($label = trim($comment[0]))))
				$col['label'] = $this->mkLabel($field, $label);
			else
				$label = $field;
			if (preg_match('#_(at|time|date|datetime)#i', $field)) {
				$col[] = sprintf('\'%s\' => %s', 'timestamp', '1');
				if ($field === 'created_at') {
					$col[] = sprintf('self::ON_CREATE => \'%s\'', 'now');
				} elseif ($field === 'updated_at') {
					$col[] = sprintf('self::ON_UPDATE => \'%s\'', 'now');
				}
				$props[] = ['int', "{$field}", $label];
			} else {
				if (isset($this->intTypes[$type])) {
					$col[] = sprintf('\'%s\' => %s', 'int', '1');
					if (isset($column['column_default']))
						$col[] = sprintf('\'%s\' => %s', 'default', intval($column['column_default']));
					$props[] = ['int', "{$field}", $label];
				} elseif (isset($this->floatTypes[$type])) {
					$col[] = sprintf('\'%s\' => %s', 'float', $column['numeric_scale'] > 0 ? intval($column['numeric_scale']) : 1);
					if (isset($column['column_default']))
						$col[] = sprintf('\'%s\' => %s', 'default', floatval($column['column_default']));
					$props[] = ['double', "{$field}", $label];
				} elseif ($type === 'bigint') {
					$col[] = sprintf('\'%s\' => %s', 'bigint', '1');
					if (isset($column['column_default']) && is_numeric($column['column_default']))
						$col[] = sprintf('\'%s\' => \'%s\'', 'default', $column['column_default']);
					$props[] = ['string', "{$field}", $label];
				} elseif ($type === 'enum') {
					// 枚举类型，肯定是要限制选项的
					$options = [];
					$commentOptions = [];
					if (!empty($comment[1])) {
						$exp = explode(',', $comment[1]);
						foreach ($exp as $item) {
							$itemExp = explode(':', $item);
							if (isset($itemExp[1]))
								$commentOptions[trim($itemExp[0])] = trim($itemExp[1]);
						}
					}
					if (preg_match_all('#\'([^\']+)\'#i', $column['column_type'], $matches, PREG_SET_ORDER)) {
						foreach ($matches as $match) {
							$val = $match[1];
							$txt = isset($commentOptions[$match[1]]) ? $commentOptions[$match[1]] : $match[1];
							$options[] = sprintf('\'%s\' => \'%s\'', $val, $txt);
						}
					}
					$options = empty($options) ? '[]' : '['.implode(', ', $options).']';

//					$col['options'] = $options;
//					if (isset($dbCol['column_default']))
//						$col['default'] = $dbCol['column_default'];
				}
			}
			$cols[] = [$field, implode(', ', $col)];
		}

//		var_dump($props);
	}

	public function getTableInfo($tableName)
	{
		return $this->db->find([
			'fetch'  => 'one',
			'select' => 'TABLE_NAME as table_name, TABLE_TYPE as table_type, ENGINE as engine, TABLE_COMMENT as table_comment',
			'from'   => 'information_schema.tables',
			'where'  => [
				'table_name = ? and table_schema = ?',
				$tableName,
				$this->db->getDB(),
			],
		]);
	}

	public function getPrimaryKeyColumn($tableName)
	{
		return $this->db->find([
			'fetch'       => 'one',
			'fetchColumn' => 0,
			'select'      => 'COLUMN_NAME as column_name',
			'from'        => 'information_schema.key_column_usage',
			'where'       => [
				'table_name = ? and table_schema = ? and constraint_name = ?',
				$tableName,
				$this->db->getDB(),
				'PRIMARY',
			],
		]);
	}

	public function getTableColumns($tableName)
	{
		// 得到所有字段
		$columns = $this->db->find([
			'fetch'  => 'all',
			'select' => 'COLUMN_NAME as column_name, COLUMN_DEFAULT as column_default, IS_NULLABLE as is_null, DATA_TYPE as data_type,
            CHARACTER_MAXIMUM_LENGTH as char_length, COLUMN_KEY as column_key, EXTRA as extra, COLUMN_COMMENT as comment,
            COLUMN_TYPE as column_type, NUMERIC_PRECISION as numeric_precision, NUMERIC_SCALE as numeric_scale',
			'from'   => 'information_schema.columns',
			'where'  => ['table_name = ? and table_schema = ?', $tableName, $this->db->getDB()],
			'order'  => 'ordinal_position asc',
		]);
		return $columns;
	}

	public function filterTableColumnAsModelColumn(array $dbCol)
	{
		$field = $dbCol['column_name'];
		$col = [];
		$dataType = $dbCol['data_type'];
		$comment = str_replace(['，', '：'], [',', ':'], $dbCol['comment']);
		$comment = explode('|', $comment);
		if (!empty(($label = trim($comment[0]))))
			$col['label'] = $label;
		if (preg_match('#_(at|time|date|datetime)#i', $field)) {
			$col['timestamp'] = true;
			if ($field === 'created_at')
				$col['{PROCESS_CREATE}'] = '{now}';
			elseif ($field === 'updated_at')
				$col['{PROCESS_UPDATE}'] = '{now}';
		} else {
			if (isset($this->intTypes[$dataType])) {
				$col['int'] = true;
				if ($dbCol['column_default'] === null)
					$col['default'] = 0;
				else
					$col['default'] = intval($dbCol['column_default']);
			} elseif (isset($this->floatTypes[$dataType])) {
				$col['float'] = $dbCol['numeric_scale'];
				if ($dbCol['column_default'] === null)
					$col['default'] = (double)0;
				else
					$col['default'] = floatval($dbCol['column_default']);
			} elseif ($dataType === 'bigint') {
				$col['bigint'] = true;
				if ($dbCol['column_default'] === null || !is_numeric($dbCol['column_default']))
					$col['default'] = '0';
				else
					$col['default'] = $dbCol['column_default'];
			} elseif ($dataType === 'enum') {
				// 枚举类型，肯定是要限制选项的
				$options = [];
				$commentOptions = [];
				if (!empty($comment[1])) {
					$exp = explode(',', $comment[1]);
					foreach ($exp as $item) {
						$itemExp = explode(':', $item);
						if (isset($itemExp[1]))
							$commentOptions[trim($itemExp[0])] = trim($itemExp[1]);
					}
				}
				if (preg_match_all('#\'([^\']+)\'#i', $dbCol['column_type'], $matches, PREG_SET_ORDER)) {
					foreach ($matches as $match) {
						$options[$match[1]] = isset($commentOptions[$match[1]]) ? $commentOptions[$match[1]] : $match[1];
					}
				}
				$col['options'] = $options;
				if (isset($dbCol['column_default']))
					$col['default'] = $dbCol['column_default'];

//				$properties[] = "@property string \${$name} {$title}{$comment}";
			}
		}
//		var_dump($col);
		var_dump($col);
		return [$field, $col];
	}

	public function mkLabel($field, $label)
	{
		return sprintf('\'%s\' => \'%s\'', 'label', $label);
	}
}