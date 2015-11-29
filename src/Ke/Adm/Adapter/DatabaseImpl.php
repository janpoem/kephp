<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm\Adapter;


use Ke\Adm\Adapter\Forge\MySQLForge;

interface DatabaseImpl
{

	const READ_WRITE_IN_SAME = -1;

	const OPERATION_READ = 0;

	const OPERATION_WRITE = 1;

	const FETCH_ONE = 'one';

	const FETCH_ALL = 'all';

	const FETCH_NUM = 'num';

	const FETCH_ASSOC = 'assoc';

	const IDX_SQL = 0;

	const IDX_PARAMS = 1;

	const IDX_FETCH_TYPE = 2;

	const IDX_FETCH_STYLE = 3;

	const IDX_FETCH_COLUMN = 4;

//	/**
//	 * 构造函数，一定要传入remote名称，输出调试的时候不会输出调试内容的明细，而以$remote标识
//	 *
//	 * @param string $remote
//	 * @param array  $config
//	 */

	public function setName($name);

	public function getName();

	public function configure(array $config);

	public function getConfig();

	/**
	 * 数据库连接
	 *
	 * $index 默认为-1，表示取得默认配置的连接，如果指定数值，则表示连接读的数据库，用于控制读写分离。
	 *
	 * @param int $index
	 * @return DatabaseImpl
	 */
	public function connect($index = self::READ_WRITE_IN_SAME);

	/**
	 * 是否已经连接
	 *
	 * @param int $index
	 * @return bool
	 */
	public function isConnect($index = self::READ_WRITE_IN_SAME);

	public function getDB();

	/**
	 * 启动事务
	 *
	 * @return bool
	 */
	public function startTransaction();

	/**
	 * 判断是否在事务中
	 *
	 * @return bool
	 */
	public function inTransaction();

	/**
	 * 提交事务
	 *
	 * @return bool
	 */
	public function commit();

	/**
	 * 回滚事务
	 *
	 * @return bool
	 */
	public function rollBack();

	/**
	 * 调用数据库驱动的引用字符串方法
	 *
	 * @param string $string
	 * @return string
	 */
	public function quote($string);

	/**
	 * 执行一条 SQL 语句，并返回受影响的行数
	 *
	 * @param string     $sql
	 * @param array|null $args
	 * @return mixed
	 */
	public function execute($sql, array $args = null);

	/**
	 * 执行SQL，并返回结果集，默认返回数组格式的结果集
	 *
	 * query('select * from any_table where id > ? and name = ?', [100, 'Jack'], 'one');
	 *
	 * @param string     $sql
	 * @param array|null $args
	 * @param string     $type
	 * @param string     $style
	 * @param null       $column
	 * @return mixed
	 */
	public function query(
		$sql,
		array $args = null,
		$type = self::FETCH_ONE,
		$style = self::FETCH_ASSOC,
		$column = null
	);

	// 暂时保留3.x的做法
	/**
	 * 执行select查询
	 *
	 * conditions目前只处理为数组的情况，请不要传入其他格式。以后会陆续加入新的特性
	 *
	 * @param mixed $cd
	 * @param array $params
	 * @return mixed
	 */
	public function find($cd, array $params = null);

	/**
	 * 指定查询条件的结果数，不同的数据库，有不同的查询方法，所以也列举在这里
	 *
	 * $conditions同select方法
	 *
	 * @param mixed $conditions
	 * @return mixed
	 */
	public function count($conditions);

	/**
	 * 指定table，插入数据
	 *
	 * 若指定primaryKey，则返回lastInsertId
	 * 1. 若指定了pk，且为isAutoInc(自增整型)，则返回lastInsertId，注意，
	 * 目前版本暂时不确定lastInsertId是否作为Adapter\Impl的标准接口，因为不同数据库对lastInsertId的实现都有不同。
	 * 2. 若指定了pk，但不是自增类型，则无需返回这个lastInsertId，因为实际上主键已经在insert的$data中包含了。
	 * 3. 如果不指定pk，则默认会返回插入的结果长度，实际上默认为1。
	 *
	 * @param string $table
	 * @param array             $data
	 * @return int
	 */
	public function insert($table, array $data);

	public function lastInsertId($name = null);

	/**
	 * 指定table，根据目标target，更新数据
	 *
	 * 注意，update方法不允许目标为空的更新，如果没指定目标，会抛出异常。
	 *
	 * 这个目标并不会实际去检查数据库是不是存在这些目标，只是检查目标是否设定了。
	 *
	 * @param string $table
	 * @param array  $conditions
	 * @param array  $data
	 * @return int
	 */
	public function update($table, $conditions = null, array $data = null);

	/**
	 * 指定table，删除指定目标的数据
	 *
	 * 该方法同update，必须设定目标才会执行，如果未设定目标会抛出异常。
	 *
	 * 注意，这个目标并不会实际去检查数据库是不是存在这些目标，只是检查目标是否设定了。
	 *
	 * @param string $table
	 * @param array  $conditions
	 * @return int
	 */
	public function delete($table, $conditions = null);

	/**
	 * 指定table，清空表，注意，这个接口表达的应该是清空表，并且重置表主键
	 *
	 * @param string $table
	 * @return int
	 */
	public function truncate($table);

	/**
	 * @return MySQLForge
	 */
	public function getForge();
}