<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/20 0020
 * Time: 7:08
 */

namespace Ke\Adm;


class SqlQuery
{

	const SELECT = 0;

	const INSERT = 1;

	const UPDATE = 2;

	const DELETE = 3;

	const MARRY_AND = 'AND';

	const MARRY_OR = 'OR';

	const NONE_VALUE = 0;

	const SINGLE_VALUE = 1;

	const DOUBLE_VALUE = 2;

	const MULTI_VALUES = 9;

	protected static $operators = [
		'null'  => [null, self::NONE_VALUE, 'IS NULL'],
		'!null' => [null, self::NONE_VALUE, 'IS NOT NULL'],
		'='     => [null, self::SINGLE_VALUE,],
		'!='    => [null, self::SINGLE_VALUE,],
		'<>'    => [null, self::SINGLE_VALUE, '!='],
		'>'     => [null, self::SINGLE_VALUE,],
		'<'     => [null, self::SINGLE_VALUE,],
		'>='    => [null, self::SINGLE_VALUE,],
		'<='    => [null, self::SINGLE_VALUE,],
		'in'    => [null, self::MULTI_VALUES, 'IN'],
		'!in'   => [null, self::MULTI_VALUES, 'NOT IN'],
	];

	/** @var int 当前查询的命令 */
	public $command = -1;

	/** @var array 当前查询关联的数据表 */
	public $table = ['', null];

	/** @var array 当前查询的查询条件 */
	public $where = [];

	protected $wherePt = null;

	public $whereMarry = [];

	public $params = [];

	public $joins = [];

	public $order = '';

	public $group = '';

	public $limit = 0;

	public $offset = 0;

	protected $conditionGroup = false;

	protected $conditionGroupDeep = [];

	protected $conditionGroupAutoId = 0;

	protected $marry = self::MARRY_AND;

	protected $notIn = false;

	/**
	 * SqlQuery constructor.
	 *
	 * <code>
	 * $query = new SqlQuery(SqlQuery::SELECT, 'users')
	 * </code>
	 *
	 * @param $command
	 * @param null $table
	 */
	public function __construct($command, $table = null)
	{
		$this->setCommand($command);
		if (isset($table))
			$this->table($table);
	}

	public function setCommand($command)
	{
		$this->command = $command;
		return $this;
	}

	public function table($table, $as = null)
	{

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

	protected $deep = 0;

	public function where($field)
	{
		$args = func_get_args();
		$marry = $this->marry;
		if ($args[0] === self::MARRY_AND || $args[0] === self::MARRY_OR) {
			$marry = array_shift($args);
		}
		var_dump($args);
		if (is_array($args[0])) {
			foreach ($args as $item) {
				call_user_func_array([$this, 'where'], $item);
			}
		} else {
			var_dump('------------------------');
			$this->pushWhere($marry, $args, $this->deep);
		}
	}

	public function pushWhere($marry, $args, $deep = 0)
	{
		var_dump($deep, $args);
	}
}