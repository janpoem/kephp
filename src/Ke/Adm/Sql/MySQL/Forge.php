<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/20 0020
 * Time: 13:47
 */

namespace Ke\Adm\Sql\MySQL;

use Exception;
use Ke\Adm\Adapter\DbAdapter;
use Ke\Adm\Query;
use Ke\Adm\Sql\ForgeImpl;

class Forge implements ForgeImpl
{

	/** @var DbAdapter */
	private $adapter = null;

	private $intTypes = [
		'int'      => 1,
		'smallint' => 1,
		'tinyint'  => 1,
	];

	private $floatTypes = [
		'float'  => 1,
		'double' => 1,
	];

	private $queryTable = [
		'select' => [
			'TABLE_NAME as name',
			'TABLE_TYPE as type',
			'ENGINE as engine',
			'TABLE_COMMENT as comment',
		],
		'from'   => 'information_schema.tables',
	];

	private $queryColumns = [
		'select' => [
			'COLUMN_NAME as column_name',
			'COLUMN_DEFAULT as column_default',
			'IS_NULLABLE as is_null',
			'DATA_TYPE as data_type',
			'CHARACTER_MAXIMUM_LENGTH as char_length',
			'COLUMN_KEY as column_key',
			'EXTRA as extra',
			'COLUMN_COMMENT as comment',
			'COLUMN_TYPE as column_type',
			'NUMERIC_PRECISION as numeric_precision',
			'NUMERIC_SCALE as numeric_scale',
		],
		'from'   => 'information_schema.columns',
		'order'  => 'ordinal_position ASC',
	];

	public function __construct(DbAdapter $adapter)
	{
		$this->adapter = $adapter;
	}

	public function buildTableProps(string $table): array
	{
		$vars = [
			'columns' => '',
			'props'   => '',
		];
		$tableInfo = $this->getTableInfo($table);

		if ($tableInfo === false)
			throw new Exception("Table \"{$table}\" did not exist");

		$pkField = $this->getPkField($table);
		$pkAutoInc = false;

		$dbCols = $this->getTableColumns($table);
		$props = [];
		$cols = [];
		$typeMaxLength = 0;
		$fieldMaxLength = 0;

		foreach ($dbCols as $column) {
			$field = $column['column_name'];
			$type = $column['data_type'];
			$varType = null;
			$col = [];
			if ($field === $pkField && stripos($column['extra'], 'auto_increment') !== false) {
				$pkAutoInc = true;
			}
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
				}
				elseif ($field === 'updated_at') {
					$col[] = sprintf('self::ON_UPDATE => \'%s\'', 'now');
				}
				$varType = 'int';
				$props[] = [$varType, "{$field}", $label];
			}
			else {
				if (isset($this->intTypes[$type])) {
					$col[] = sprintf('\'%s\' => %s', 'int', '1');
					if (isset($column['column_default']))
						$col[] = sprintf('\'%s\' => %s', 'default', intval($column['column_default']));
					$varType = 'int';
					$props[] = [$varType, "{$field}", $label];
				}
				elseif (isset($this->floatTypes[$type])) {
					$col[] = sprintf('\'%s\' => %s', 'float',
						$column['numeric_scale'] > 0 ? intval($column['numeric_scale']) : 1);
					if (isset($column['column_default']))
						$col[] = sprintf('\'%s\' => %s', 'default', floatval($column['column_default']));
					$varType = 'double';
					$props[] = [$varType, "{$field}", $label];
				}
				elseif ($type === 'bigint') {
					$col[] = sprintf('\'%s\' => %s', 'bigint', '1');
					if (isset($column['column_default']) && is_numeric($column['column_default']))
						$col[] = sprintf('\'%s\' => \'%s\'', 'default', $column['column_default']);
					$varType = 'string';
					$props[] = [$varType, "{$field}", $label];
				}
				elseif ($type === 'enum') {
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
					$options = empty($options) ? '[]' : '[' . implode(', ', $options) . ']';
					$col[] = sprintf('\'%s\' => %s', 'options', $options);
					if (isset($column['column_default']))
						$col[] = sprintf('\'%s\' => \'%s\'', 'default', $column['column_default']);
					$varType = 'string';
					$props[] = [$varType, "{$field}", $label];
				}
				elseif ($type === 'varchar' || $type === 'char') {
					$col[] = sprintf('\'max\' => %s', $column['char_length']);
					if (isset($column['column_default']))
						$col[] = sprintf('\'%s\' => \'%s\'', 'default', $column['column_default']);
					$varType = 'string';
					$props[] = [$varType, "{$field}", $label];
				}
				else {
					$varType = 'mixed';
					$props[] = [$varType, "{$field}", $label];
				}
			}
			if ($field === $pkField) {
				$col[] = '\'pk\' => 1';
				if ($pkAutoInc)
					$col[] = '\'autoInc\' => 1';
			}
			$cols[] = [$field, implode(', ', $col)];
			$typeLength = strlen($varType);
			$fieldLength = strlen($field);
			if ($fieldLength > $fieldMaxLength)
				$fieldMaxLength = $fieldLength;
			if ($typeLength > $typeMaxLength)
				$typeMaxLength = $typeLength;
		}

		$vars['pk'] = empty($pkField) ? "null" : "'{$pkField}'";

		$vars['pkAutoInc'] = empty($pkAutoInc) ? "false" : "true";

		$temp = [
			"\t\t// database columns",
			"\t\t// generated as " . date('Y-m-d H:i:s'),
			"\t\treturn [",
		];
		foreach ($cols as $index => &$col) {
			$prefix = "\t\t\t";
			$temp[] = sprintf('%s%s => [%s],',
				$prefix,
				str_pad("'{$col[0]}'", $fieldMaxLength + 2, ' ', STR_PAD_RIGHT),
				$col[1]);
		}
		$temp[] = "\t\t];";
		$temp[] = "\t\t// database columns";
		$vars['columns'] = implode(PHP_EOL, $temp);

		$temp = [
			" * // class properties",
		];
		$vars['props'] = '';
		foreach ($props as $index => $prop) {
			$prefix = " * ";
			$temp[] = sprintf('%s@property %s $%s %s',
				$prefix,
				str_pad("{$prop[0]}", $typeMaxLength, ' ', STR_PAD_RIGHT),
				str_pad("{$prop[1]}", $fieldMaxLength, ' ', STR_PAD_RIGHT),
				$prop[2]);
		}
		$temp[] = " * // class properties";
		$vars['props'] = implode(PHP_EOL, $temp);

		return $vars;
	}

	public function getDbTables(string $db = null): array
	{
		if (empty($db))
			$db = $this->adapter->getDatabase();
		if (empty($db))
			throw new Exception('Undefined database!');

		return (new Query())->load($this->queryTable)->in([
			'TABLE_SCHEMA' => $db,
		])->find();
	}

	public function getTableInfo(string $table)
	{
		return (new Query())->load($this->queryTable)->in([
			'TABLE_NAME'   => $table,
			'TABLE_SCHEMA' => $this->adapter->getDatabase(),
		])->findOne();
	}

	public function getPkField(string $table)
	{
		return (new Query())->select('COLUMN_NAME as column_name')->from('information_schema.key_column_usage')->in([
			'TABLE_NAME'   => $table,
			'TABLE_SCHEMA' => $this->adapter->getDatabase(),
		    'constraint_name' => 'PRIMARY',
		])->columnOne(0);
	}

	public function getTableColumns(string $table)
	{
		return (new Query())->load($this->queryColumns)->in([
			'TABLE_NAME'   => $table,
			'TABLE_SCHEMA' => $this->adapter->getDatabase(),
		])->find();
	}

	public function mkLabel($field, $label)
	{
		return sprintf('\'%s\' => \'%s\'', 'label', $label);
	}
}