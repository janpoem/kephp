<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/19 0019
 * Time: 10:25
 */

namespace Ke\Adm\Adapter;

use Ke\Logging\Log;
use PDO;

use Ke\Adm\Exception;

abstract class PdoAbs implements DatabaseImpl
{

	protected $remote = null;

	protected $config = [
		'host'       => '',
		'port'       => '',
		'db'         => '',
		'user'       => '',
		'password'   => '',
		'prefix'     => '',
		'charset'    => 'utf8',
		'logger'     => null,
		'readSplit'  => [],
		'pdoOptions' => [],
	];

	private $isInit = false;


	protected $readHostCount = 0;

	protected $hostCount = 1; // 默认主机的数量是1

	/** @var PDO */
	protected $connectors = null;

	/** @var array PDO的设置 */
	protected $pdoOptions = [
		// 限制全部字段强制转换为小写
		PDO::ATTR_CASE              => PDO::CASE_LOWER,
		// 空字符串转换为php的null值
		PDO::ATTR_ORACLE_NULLS      => PDO::NULL_EMPTY_STRING,
		// 数字内容转换为(true:强制转字符|false:源类型)类型
		PDO::ATTR_STRINGIFY_FETCHES => false,
		PDO::ATTR_EMULATE_PREPARES  => false,
		//			PDO::ATTR_PERSISTENT		=> true
	];

	/** @var bool 默认自动提交为标准 */
	protected $isAutoCommit = true;

	protected $errorMode = PDO::ERRMODE_EXCEPTION;

	protected $logger = 'adm';

	protected $operation = self::OPERATION_READ;

	public function init($remote, array $config)
	{
		if ($this->isInit)
			return $this;
		$this->remote = $remote;
		$this->config = array_merge($this->config, $config);
		$this->logger = $this->config['logger'];
		if (!empty($this->config['pdoOptions'])) {
			foreach ($this->config['pdoOptions'] as $key => $value)
				$this->pdoOptions[$key] = $value;
		}
		// 配置了读分离服务器配置，则增加读分离服务器的数量上去
		if (!empty($this->config['readSplit'])) {
			$this->readHostCount = count($this->config['readSplit']);
			$this->hostCount += $this->readHostCount;
		}
		return $this;
	}

	/**
	 * 取得PDO连接的字符串，PDO专有接口
	 * @return string
	 */
	abstract public function getDSN(array $config);

	/**
	 * 当连接成功时执行的接口
	 */
	abstract protected function onConnect();


	public function getConfig($index)
	{
		if ($index === -1 || !isset($this->config['readSplit'][$index]))
			return $this->config;
		$config = $this->config['readSplit'][$index];
		if (empty($config['host']) || empty($config['user']))
			throw new Exception(Exception::INVALID_READ_CONFIG, [$this->remote, static::class, $index]);
		if (empty($config['db']))
			$config['db'] = $this->config['db'];
		if (empty($config['password']))
			$config['password'] = '';
		return $config;
	}

	/**
	 * 连接函数
	 *
	 * @param int $index
	 * @return PdoAbs
	 * @throws Exception
	 */
	public function connect($index = self::READ_WRITE_IN_SAME)
	{
//        if ($index > -1 && !isset($this->config['readSplit'][$index]))
//            $index = -1;
		if (isset($this->connectors[$index]))
			return $this;
		try {
			$config = $this->getConfig($index);
			$connector = new PDO(
				$this->getDSN($config),
				$config['user'],
				$config['password'],
				$this->pdoOptions);
//            if (App::isEnv(App::ENV_PRO))
//                $this->errorMode = PDO::ERRMODE_SILENT;
			$connector->setAttribute(PDO::ATTR_ERRMODE, $this->errorMode);
			$connector->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
			$this->connectors[$index] = $connector;
			$this->isAutoCommit = true; // 默认以自动提交为标准
			$this->onConnect();
			if (!empty($this->logger)) {
				Log::getLogger($this->logger)->info("{$this->remote}#{$index} connect success!", ['config' => $config]);
			}
		} catch (\Exception $ex) {
			throw new Exception(Exception::CONNECT_ERROR, [
				$this->remote, static::class, $index, $ex->getMessage(),
			]);
		}
		return $this;
	}

	/**
	 * 获取当前驱动的连接对象，如果为连接，自动连接
	 *
	 * @return PDO
	 */
	protected function getConnector()
	{
		$index = $this->switchReadWriteIndex($this->operation);
		if (!$this->isConnect($index)) {
			$this->connect($index);
		}
		return $this->connectors[$index];
	}

	public function getDB()
	{
		return $this->config['db'];
	}

	/**
	 * 判断当前驱动是否已经连接
	 *
	 * @param int $index
	 * @return bool
	 */
	public function isConnect($index = self::READ_WRITE_IN_SAME)
	{
//        if ($index > -1 && !isset($this->config['readSplit'][$index]))
//            $index = -1;
		return isset($this->connectors[$index]);
//        if ($index === -1)
//            return (isset($this->connector) && $this->connector instanceof PDO);
//        else
//            return isset($this->readConnector[$index]) && $this->readConnector[$index] instanceof PDO;
	}

	/**
	 * 启动事务接口
	 *
	 * @return $this
	 */
	public function startTransaction()
	{
		if ($this->isAutoCommit) {
			$this->getConnector()->beginTransaction();
			$this->isAutoCommit = false;
		}
		return $this;
	}

	/**
	 * 注意特定的数据库如pgsql，需要重载该方法，调用PDO::inTransaction()为准。
	 * @return bool 是否已经启动事务。
	 */
	public function inTransaction()
	{
		return !$this->isAutoCommit;
	}

	/**
	 * 提交事务
	 * @return PdoAbs
	 */
	public function commit()
	{
		if (!$this->isAutoCommit) {
			$this->getConnector()->commit();
			$this->isAutoCommit = true;
		}
		return $this;
	}

	/**
	 * 回滚事务
	 * @return PdoAbs
	 */
	public function rollBack()
	{
		if (!$this->isAutoCommit) {
			$this->getConnector()->rollBack();
			$this->isAutoCommit = true;
		}
		return $this;
	}

	/**
	 * 调用数据库驱动引用字符串
	 * @param string $string
	 * @return string
	 */
	public function quote($string)
	{
		return $this->getConnector()->quote($string);
	}

	protected function switchReadWriteIndex($operation)
	{
		// 没有配置readSplit，肯定还是在同一台机器上操作了
		if ($this->hostCount <= 1 || empty($this->config['readSplit']))
			return self::READ_WRITE_IN_SAME;
		// 写操作，返回默认的连接
		if ($operation === self::OPERATION_WRITE)
			return self::READ_WRITE_IN_SAME;
		else
			return 0; // 先返回默认的$index
	}

	/**
	 * 预备执行SQL函数，如果SQL存在异常，会在这里抛出错误。
	 *
	 * @param string $sql
	 * @param array $args
	 * @return \PDOStatement
	 */
	protected function prepare($sql, array $args = null)
	{
		$statement = $this->getConnector()->prepare($sql);
		if (!isset($args))
			$args = [];
		if (!empty($this->logger)) {
			Log::getLogger($this->logger)->info("{$this->remote} prepare sql!", [
				'sql'    => $sql,
				'params' => $args,
			]);
		}
		$statement->execute($args);
		return $statement;
	}

	public function execute($sql, array $args = null)
	{
		$st = $this->prepare($sql, $args);
		return $st->rowCount();
	}

	public function query($sql, array $args = null,
	                      $type = self::FETCH_ONE,
	                      $style = self::FETCH_ASSOC,
	                      $column = null)
	{
		$st = $this->prepare($sql, $args);
		if (isset($column)) {
			$columnCount = $st->columnCount();
			$columnIndex = -1;
			if (is_numeric($column)) {
				if ($column >= 0 && $column < $columnCount)
					$columnIndex = $column;
			} elseif (is_string($column) && !empty($column)) {
				for ($i = 0; $i < $columnCount; $i++) {
					$columnMeta = $st->getColumnMeta($i);
					if ($columnMeta['name'] === $column) {
						$columnIndex = $i;
						break;
					}
				}
			}
			if ($columnIndex > -1) {
				if ($type === self::FETCH_ONE) {
					return $st->fetchColumn($columnIndex);
				} else {
					return $st->fetchAll(PDO::FETCH_COLUMN, $columnIndex);
				}
			}
			return false;
		} else {
			$style = $style === self::FETCH_ASSOC ? PDO::FETCH_ASSOC : PDO::FETCH_NUM;
			if ($style === self::FETCH_ONE) {
				return $st->fetch($style);
			} else {
				return $st->fetchAll($style);
			}
		}
	}
}