<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm;

use Exception;
use ArrayObject;

class Model extends ArrayObject implements CacheModelImpl
{

	use CacheModelTrait;

	const SOURCE_USER = 0;
	const SOURCE_DB   = 1;

	const ON_CREATE = 'create';
	const ON_UPDATE = 'update';
	const ON_DELETE = 'delete';
	const ON_SAVE   = 'save';

	const SAVE_FAILURE = 0;
	const SAVE_SUCCESS = 1;
	const SAVE_NOTHING = 2;

	const ERR_UNKNOWN                 = 1;
	const ERR_NOT_NUMERIC             = 10;
	const ERR_NOT_FLOAT               = 11;
	const ERR_NUMERIC_LESS_THAN       = 12;
	const ERR_NUMERIC_GREET_THAN      = 13;
	const ERR_NUMERIC_LESS_GREAT_THAN = 14;
	const ERR_NOT_ALLOW_EMPTY         = 20;
	const ERR_NOT_EMAIL               = 21;
	const ERR_NOT_MATCH               = 22;
	const ERR_NOT_MATCH_SAMPLE        = 23;
	const ERR_STR_LEN_LESS_THAN       = 24;
	const ERR_STR_LEN_GREET_THAN      = 25;
	const ERR_STR_LEN_LESS_GREAT_THAN = 26;
	const ERR_NOT_EQUAL               = 27;
	const ERR_NOT_IN_RANGE            = 28;
	const ERR_DUPLICATE               = 29;

	private static $stdErrorMessages = [
		self::ERR_UNKNOWN                 => '{label}存在未知的错误！',
		self::ERR_NOT_NUMERIC             => '{label}不是有效的数值类型！',
		self::ERR_NOT_FLOAT               => '{label}不是有效的浮点数！',
		self::ERR_NUMERIC_LESS_THAN       => '{label}的数值不得小于{min}！',
		self::ERR_NUMERIC_GREET_THAN      => '{label}的数值不得大于{max}！',
		self::ERR_NUMERIC_LESS_GREAT_THAN => '{label}的数值应在{min} ~ {max}之间！',
		self::ERR_NOT_ALLOW_EMPTY         => '{label}为必填字段，不得为空！',
		self::ERR_NOT_EMAIL               => '{label}不是有效的邮箱地址！',
		self::ERR_NOT_MATCH               => '{label}不符合指定格式！',
		self::ERR_NOT_MATCH_SAMPLE        => '{label}不符合指定格式，正确格式为：{sample}！',
		self::ERR_STR_LEN_LESS_THAN       => '{label}的字符长度不能少于{min}位！',
		self::ERR_STR_LEN_GREET_THAN      => '{label}的字符长度不能多于{max}位！',
		self::ERR_STR_LEN_LESS_GREAT_THAN => '{label}的字符长度应在{min} ~ {max}位之间！',
		self::ERR_NOT_EQUAL               => '{label}与{equalLabel}的值不相同！',
		self::ERR_NOT_IN_RANGE            => '{label}不在指定的取值范围内！',
		self::ERR_DUPLICATE               => '{label}已经存在值为"{value}"的记录！',
	];

	protected static $_init = false;

	protected static $dbSource = null;

	protected static $cacheSource = false;

	protected static $cacheTTL = 60 * 60 * 12; /* default value: 12 hours */

	protected static $tableName = '';

	protected static $tablePrefix = '';

	protected static $pk = 'id';

	protected static $pkAutoInc = true;

	protected static $columns = [];

	protected static $groupColumns = [];

	private static $globalValidator = null;

	private static $globalFilter = null;

	protected static $validatorClass = null;

	protected static $validator = null;

	protected static $filterClass = null;

	protected static $filter = null;

	protected static $queries = [];

	protected static $errorMessages = [];

	///////////////////////////////////////////////////////////////

	// 下面的几个属性是很重要的属性，但是php目前版本，如果一个类继承自ArrayObject，并且混入了这个Trait
	// 当从缓存中读取的时候，private会失效，这是个很严重的bug。所以下来属性暂时保留用protected
	// 参考[issues#array_object.php]
	// 所以，在访问下列的属性的时候，请使用
	protected $_initData = false;

	protected $sourceType = self::SOURCE_USER;

	protected $referenceData = false;

	protected $shadowData = null;

	protected $hiddenData = null;

	protected $errors = null;

	public static function initModel()
	{
		if (!static::$_init) {
			$isInit = true;
			$class = static::class;
			// todo: 这里要增加从缓存中读取
			// 初始化表名
			$table = Db::mkTableName(static::$dbSource, $class, static::$tableName, static::$tablePrefix);
			static::$tableName = &$table;

			$filter = static::getFilter();

			$groupColumns = static::$groupColumns;
			$pk = null;
			$pkAutoInc = false;
			$dbColumns = static::dbColumns();
			$columns = static::$columns;
			// =============================================================== //
			// user : [a, b] + db : [b, c]
			// [a(user), b(user), c(db)], b(db)
			// when
			// b(db) => [ a => 1, b => 2 ]
			// b(user) => [ a => 2, c => 3 ]
			// b(user) + b(db) => [ a => 2, c => 3, b => 2 ] (drop a => 1)
			// =============================================================== //
			// 数据库字段（dbColumns），全部由脚本读取数据库生成，他不包含任何用户手动定义的内容。
			// 但用户定义的字段（static $columns），则由用户手工输入，包含了编辑类型、特定的属性声明，默认值等。
			// 所以我们以用户定义的字段作为默认基础
			// =============================================================== //
			foreach ($columns + $dbColumns as $field => $column) {
				if (is_string($column))
					$column = ['label' => $column];
				elseif (!is_array($column))
					$column = [];
				if (isset($columns[$field]) && isset($dbColumns[$field]))
					$column += $dbColumns[$field];
				if (!empty($column['pk']) && empty($pk)) {
					$pk = $field;
					$pkAutoInc = $column['autoInc'] ?? false;
				}
				$columns[$field] = $filter->initColumn($field, $column, $groupColumns, false);
			}
			// 全部字段过滤完以后，才来检查主键的有效性
			if (empty($pk)) {
				$pk = trim(static::$pk);
				$pkAutoInc = static::$pkAutoInc;
				if (!isset($columns[$pk]))
					$columns[$pk] = [];
			}
			$columns[$pk] = $filter->initColumn($pk, $columns[$pk], $groupColumns, true, $pkAutoInc);

			static::$pk = &$pk;
			static::$pkAutoInc = &$pkAutoInc;
			static::$columns = &$columns;
			static::$groupColumns = &$groupColumns;
			static::$_init = &$isInit;
			static::onInitModel();
		}
	}

	public static function dbColumns()
	{
		return [];
	}

	protected static function onInitModel()
	{
	}

	public static function getDbSource()
	{
		return static::$dbSource;
	}

	/**
	 * @return Adapter\Db\PdoMySQL|Adapter\DbAdapter
	 * @throws \Exception
	 */
	public static function getDbAdapter()
	{
		return Db::getAdapter(static::$dbSource);
	}

	public static function isEnableCache()
	{
		return static::$cacheSource !== false && !empty(static::$pk);
	}

	public static function getCacheSource()
	{
		return static::$cacheSource;
	}

	public static function getCacheAdapter()
	{
		if (!static::isEnableCache())
			throw new Exception(sprintf('Model "%s" undefined cache source or the model without primary key!',
				static::class));
		return Cache::getAdapter(static::$cacheSource);
	}

	public static function getCacheDefaultTTL(): int
	{
		return static::$cacheTTL;
	}

	/**
	 * @return Validator
	 */
	public static function getValidator()
	{
		if (isset(static::$validator))
			return static::$validator;
		if (!empty(static::$validatorClass)) {
			$class = static::$validatorClass;
			if ($class === Validator::class || !is_subclass_of($class, Validator::class)) {
				$class = null;
				static::$validatorClass = &$class;
			}
			else {
				$validator = new $class();
				static::$validator = &$validator;
				return static::$validator;
			}
		}
		if (!isset(self::$globalValidator))
			self::$globalValidator = new Validator();
		return self::$globalValidator;
	}

	/**
	 * @return Filter
	 */
	public static function getFilter()
	{
		if (isset(static::$filter) && static::$filter instanceof Filter)
			return static::$filter;
		if (!empty(static::$filterClass)) {
			$class = static::$filterClass;
			if ($class === Filter::class || !is_subclass_of($class, Filter::class)) {
				$class = null;
				static::$filterClass = &$class;
			}
			else {
				$filter = new $class();
				static::$filter = &$filter;
				return static::$filter;
			}
		}
		if (!isset(self::$globalFilter))
			self::$globalFilter = new Filter();
		return self::$globalFilter;
	}

	public static function getStaticColumns($process = Model::ON_CREATE)
	{
		if (static::$_init === false)
			static::initModel();
		$columns = static::$columns;
		if (!empty(static::$groupColumns[$process])) {
//			return array_merge($columns, static::$groupColumns[$process]); // 维持默认的columns的顺序
			return array_merge($columns, static::$groupColumns[$process]); // 此方法会打乱columns的顺序
		}
		return $columns;
	}

	public static function getStaticColumn($field, $process = Model::ON_CREATE)
	{
		if (static::$_init === false)
			static::initModel();
		$columns = static::getStaticColumns($process);
		return isset($columns[$field]) ? $columns[$field] : [];
	}

	public static function getGroupColumns($group = null)
	{
		if (static::$_init === false)
			static::initModel();
		if (!isset($group))
			return static::$groupColumns;
		return empty(static::$groupColumns[$group]) ? [] : static::$groupColumns[$group];
	}

	public static function getDefaultData($field = null)
	{
		if (static::$_init === false)
			static::initModel();
		if (!isset($field))
			return static::$groupColumns['default'];
		return isset(static::$groupColumns['default'][$field]) ? static::$groupColumns['default'][$field] : false;
	}

	public static function getLabel($field)
	{
		if (static::$_init === false)
			static::initModel();
		if (isset(static::$columns[$field]['label']))
			return static::$columns[$field]['label'];
		if (isset(static::$columns[$field]['title']))
			return static::$columns[$field]['title'];
		return $field;
	}

	public static function getErrorMessage($message): string
	{
		if (isset(static::$errorMessages[$message]))
			return static::$errorMessages[$message];
		if (isset(self::$stdErrorMessages[$message]))
			return self::$stdErrorMessages[$message];
		return (string)$message;
	}

	public static function buildErrorMessage($field, $error): string
	{
		$message = null;
		$data = ['label' => static::getLabel($field)];
		if (empty($error)) {
			$message = static::getErrorMessage(static::ERR_UNKNOWN);
		}
		elseif (is_string($error)) {
			$message = static::getErrorMessage($error);
		}
		elseif (is_array($error)) {
			$message = static::getErrorMessage(array_shift($error));
			if (!empty($error))
				$data += $error;
		}
		return substitute($message, $data);
	}

	public static function getTable($as = null)
	{
		if (static::$_init === false)
			static::initModel();
		if (!empty($as))
			return static::$tableName . ' ' . $as;
		return static::$tableName;
	}

	public static function getPkField()
	{
		if (static::$_init === false)
			static::initModel();
		return empty(static::$pk) ? static::$pk : false;
	}

	public static function isPkAutoInc()
	{
		if (static::$_init === false)
			static::initModel();
		if (!empty(static::$pk) && !empty(static::$pkAutoInc))
			return true;
		return false;
	}

	/**
	 * @param bool|null $name
	 * @return Query
	 */
	public static function query($name = false)
	{
		if ($name !== false && isset(static::$queries[$name])) {
			if (!(static::$queries[$name] instanceof Query)) {
				$query = (new Query())->setModel(static::class);
				if (is_array(static::$queries[$name]))
					$query->load(static::$queries[$name]);
				static::$queries[$name] = &$query;
			}
			return clone static::$queries[$name];
		}
		return (new Query())->setModel(static::class);
	}

	public static function getQueries()
	{
		return static::$queries;
	}

	/**
	 *
	 * <code>
	 * // 指定查询条件
	 * User::find([ 'where' => ['id', '=', 100] ]);
	 * User::find(User::query()->where('id', '=', 100));
	 * // 查询主键，必须Model为有主键的模式
	 * User::find(1, 2, 3);
	 * // 主键查询，并追加查询条件
	 * User::find(1, 2, 3, ['select' => 'id', 'order' => 'id DESC']);
	 * </code>
	 *
	 * @param array ...$args
	 * @return array|DataList
	 */
	public static function find(...$args)
	{
		$count = count($args);
		$query = null;
		if ($count > 0) {
			$last = $args[$count - 1];
			if (is_array($last) || is_object($last)) {
				$query = array_pop($args);
				if (!($query instanceof Query))
					$query = static::query(false)->load($query);
				else
					$query->setModel(static::class);
			}
		}
		if (!isset($query))
			$query = static::query(false);
		if (!empty($args)) {
			if (!empty(static::$pk))
				$query->in(static::$pk, $args);
		}
		return $query->find();
	}

	/**
	 * 这个方法类似find，但是不管满足查询条件的结果为多少，只返回第一条结果。
	 *
	 * @param array ...$args
	 * @return array|Model|static
	 */
	public static function findOne(...$args)
	{
		$count = count($args);
		$query = null;
		if ($count > 0) {
			$last = $args[$count - 1];
			if (is_array($last) || is_object($last)) {
				$query = array_pop($args);
				if (!($query instanceof Query))
					$query = static::query(false)->load($query);
				else
					$query->setModel(static::class);
			}
		}
		if (!isset($query))
			$query = static::query(false);
		if (!empty($args)) {
			if (!empty(static::$pk))
				$query->in(static::$pk, $args);
		}
		return $query->findOne();
	}

	/**
	 * @param array $keyValues
	 * @param null  $query
	 * @return array|DataList
	 */
	public static function findIn(array $keyValues, $query = null)
	{
		if (!($query instanceof Query))
			$query = static::query(false)->load($query);
		return $query->in($keyValues)->find();
	}

	/**
	 * @param array $keyValues
	 * @param null  $query
	 * @return array|Model|static
	 */
	public static function findOneIn(array $keyValues, $query = null)
	{
		if (!($query instanceof Query))
			$query = static::query(false)->load($query);
		return $query->in($keyValues)->findOne();
	}

	public static function newList($data, Query $query, $source = null)
	{
		$list = new DataList();
		$list->setModel($query->getModel());
		$list->setPagination($query->getPagination());
		foreach ($data as $row) {
			$list[] = self::newInstance($row, $query, $source);
		}
		return $list;
	}

	public static function newInstance($data, Query $query, $source = null)
	{
		$obj = new static(false);
		$obj->prepareData($data, $source);
		return $obj;
	}

	/////////////////////////////////////////////////////////////////////////////////////
	// cache 静态方法 部分
	/////////////////////////////////////////////////////////////////////////////////////

	public static function makeCacheArgs($pk = null): array
	{
		return [static::$pk => $pk];
	}

	public static function makeCacheInstance(string $key, array $args)
	{
		return static::findOne($args[static::$pk] ?? null);
	}

//
//	/**
//	 * @param $pk
//	 * @return Model|static
//	 * @throws Exception
//	 */
//	public static function loadCache($pk)
//	{
//		if (!static::$_init)
//			static::initModel();
//		if (!static::isEnableCache())
//			throw new Exception(sprintf('Model "{%s}" undefined cache source or the model without primary key!',
//				static::class));
//		if (strlen($pk) === 0)
//			throw new Exception('Undefined primary key!');
//		global $KE_CACHES;
//		$key = static::makeCacheKey($pk);
//		$adapter = static::getCacheAdapter();
//		$cache = $KE_CACHES[$key] ?? $adapter->get($key);
//		if ($cache === false) {
//			$cache = static::findOneIn([static::$pk => $pk]);
//			if ($cache->isExists()) {
//				$cache->saveCache();
//			}
//		}
////		if ($cache !== false) {
////			return $cache;
////		}
////		$obj = static::findOneIn([static::$pk => $pk]);
////		if ($obj->isExists()) {
////			$obj->saveCache();
////		}
//		if ($cache->isExists() && !isset($KE_CACHES[$key]))
//			$KE_CACHES[$key] = $cache;
//		return $cache;
//	}

	public function __construct($data = null)
	{
		if ($data !== false) {
			if (empty($data) || !is_array($data))
				$data = [];
			$this->prepareData($data);
		}
	}

	final protected function prepareData($data, $source = Model::SOURCE_USER)
	{
		if (static::$_init === false)
			static::initModel();
		if (empty($data))
			$source = self::SOURCE_USER;
		// 优先绑定了数据源，和数据的参考依据，防止这个数据被污染
		$this->sourceType = $source;
		// 重置数据
		$this->referenceData = null;
		$this->shadowData = null;
		if (!empty($data) && $source !== self::SOURCE_USER) {
			if ($this->_initData) {
				$data = array_merge((array)$this, $data);
				if (!empty(static::$groupColumns['dummy'])) {
					$data = array_diff_key($data, static::$groupColumns['dummy']);
				}
			}

			if (!empty(static::$pk) && !empty($data[static::$pk]))
				$this->referenceData = [static::$pk => $data[static::$pk]];
			else
				$this->referenceData = $data;

			// 只有从数据源读取的数据，才处理隐藏的字段
			if (!empty(static::$groupColumns['hidden'])) {
				$hidden = array_intersect_key($data, static::$groupColumns['hidden']);
				if (!empty($this->hiddenData))
					$hidden += $this->hiddenData;
				$this->hiddenData = $hidden;
				$data = array_diff_key($data, static::$groupColumns['hidden']);
			}
		}
		else {

		}
		// 反序列化，必须处理
		if (!empty(static::$groupColumns['serialize'])) {
			$filter = static::getFilter();
			foreach (static::$groupColumns['serialize'] as $field => $_) {
				if (isset($data[$field]))
					$data[$field] = $filter->unSerialize($data[$field]);
			}
		}
		if ($this->_initData === false) {
			// 如果是直接从数据源加载的，不用defaultData来填充默认的字段（如果只是数据片段，则只作为片段处理）
			if ($source === self::SOURCE_USER) {
				if (empty($data))
					$data = static::$groupColumns['default'];
				else
					$data = array_merge(static::$groupColumns['default'], $data);
			}
			parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
			$this->_initData = true;
			$this->onInitData();
		}
		else {
			parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
			$this->onUpgradeData();
		}
		return $this;
	}

	protected function onInitData()
	{
	}

	protected function onUpgradeData()
	{
	}

	public function getReferenceData()
	{
		return $this->referenceData;
	}

	public function getShadowData($field = null)
	{
		if (!isset($field))
			return $this->shadowData;
		return isset($this->shadowData[$field]) ? $this->shadowData[$field] : null;
	}

	public function getHiddenData($field = null)
	{
		if (!isset($field))
			return $this->hiddenData;
		return isset($this->hiddenData[$field]) ? $this->hiddenData[$field] : null;
	}

	/**
	 * 验证一个Model的数据时，会调用这个接口
	 *
	 * 这个接口，用于给不同的Model实例提供一个重载Columns的机会
	 *
	 * @param string $process
	 * @return array
	 */
	public function getColumns($process = self::ON_CREATE): array
	{
		return static::getStaticColumns($process);
	}

	public function getColumn($field, $process = self::ON_CREATE): array
	{
		$columns = $this->getColumns($process);
		return isset($columns[$field]) ? $columns[$field] : [];
	}

	public function getPk()
	{
		if (!empty(static::$pk) && isset($this->referenceData[static::$pk]))
			return $this->referenceData[static::$pk];
		return false;
	}

	public function isNew()
	{
		return empty($this->referenceData);
	}

	public function isExists()
	{
		return !empty($this->referenceData);
	}

	public function offsetSet($field, $value)
	{
		$filter = static::getFilter();
		$columns = $this->getColumns($this->isNew() ? self::ON_CREATE : self::ON_UPDATE);
		$value = $filter->filterColumn($value, $columns[$field] ?? []);
		////
		$isParent = true;
		if ($this->sourceType !== self::SOURCE_USER) {
			if (!isset($this->shadowData[$field])) {
				$old = null;
				if (isset($this->hiddenData[$field]))
					$old = $this->hiddenData[$field];
				elseif (isset($this[$field]))
					$old = $this[$field];
				if (!equals($old, $value))
					$this->shadowData[$field] = $old;
			}
			elseif ($value === $this->shadowData[$field]) {
				unset($this->shadowData[$field]); // 恢复这个值
				if (isset($this->hiddenData[$field])) {
					unset($this[$field]);
					$isParent = false;
				}
			}
		}
		if ($isParent)
			parent::offsetSet($field, $value);
	}

	public function merge($data)
	{

		foreach ($data as $field => $value)
			$this[$field] = $value;
		return $this;
	}

	public function restore()
	{
		if ($this->sourceType !== self::SOURCE_USER && !empty($this->shadowData)) {
			foreach ($this->shadowData as $field => $value) {
				if (isset($this->hiddenData[$field]) || $value === null)
					unset($this[$field]);
				else
					$this[$field] = $value;
			}
			$this->shadowData = null;
		}
		return $this;
	}

	protected function validateCreate(array &$data)
	{
	}

	protected function validateUpdate(array &$data)
	{
	}

	protected function validateSave($process, array &$data)
	{
	}

	protected function beforeCreate(array &$data)
	{
	}

	protected function beforeUpdate(array &$data)
	{
	}

	protected function beforeSave($process, array &$data)
	{
	}

	protected function afterCreate(array &$data)
	{
	}

	protected function afterUpdate(array &$data)
	{
	}

	protected function afterSave($process, array &$data)
	{
	}

	public function save(array $data = null)
	{
		if (!empty($data))
			$this->merge($data);
		$this->errors = [];
		$process = empty($this->referenceData) ? self::ON_CREATE : self::ON_UPDATE;
		$validate = 'validate' . $process;
		$before = 'before' . $process;
		$after = 'after' . $process;

		$data = (array)$this;
		$updateData = [];
		if ($process === self::ON_UPDATE) {
			if (empty($this->shadowData))
				$data = [];
			else
				$data = array_intersect_key($data, $this->shadowData);
			if (!empty(static::$groupColumns['update_data'])) {
				$updateData = static::$groupColumns['update_data'];
				$data = array_merge($updateData, $data);
			}
		}

		$validator = static::getValidator();
		//////////////////////////////////////////////////////////////////////
		// 一段验证，validate*
		// 触发validateUpdate|validateCreate, validateSave事件
		if ($this->$validate($data) === false || $this->validateSave($process, $data) === false)
			return self::SAVE_FAILURE;
		// 执行数据检验
		$validator->validateModelObject($this, $data, $process, false);
		// 如果在此过程中，发现错误，则不往后执行
		if (!empty($this->errors))
			return self::SAVE_FAILURE;

		//////////////////////////////////////////////////////////////////////
		// 二段验证，before*
		// 触发beforeUpdate|beforeCreate, beforeSave事件
		if ($this->$before($data) === false || $this->beforeSave($process, $data) === false)
			return self::SAVE_FAILURE;
		$validator->validateModelObject($this, $data, $process, true);
		if (!empty($this->errors))
			return self::SAVE_FAILURE;

		$this->errors = null;

		$adapter = static::getDbAdapter();
		$builder = $adapter->getQueryBuilder();
		$table = static::getTable();
		$sql = null;
		$args = [];

		// 生成SQL和执行参数
		if ($process === self::ON_CREATE) {
			$builder->buildInsert($table, $data, $sql, $args);
		}
		else {
			// 比较一下，除了默认要更新的数据外，实际更新的数据时什么
			$diff = array_diff_key($data, $updateData);
			if (empty($diff)) {
				$this->restore();
				return self::SAVE_NOTHING;
			}
			$builder->buildUpdate($table, $data, $builder->buildIn((array)$this->referenceData), $sql, $args);
		}
		if ($adapter->execute($sql, $args) > 0) {
			if ($process === self::ON_CREATE) {
				if (!empty(static::$pk) && !empty(static::$pkAutoInc)) {
					$data[static::$pk] = (int)$adapter->lastInsertId($table);
				}
			}
			$this->prepareData($data, self::SOURCE_DB);
			$this->$after($data);
			$this->afterSave($process, $data);
			if ($this->isCached() || static::isEnableCache())
				$this->saveCache();
			return self::SAVE_SUCCESS;
		}
		return self::SAVE_FAILURE;
	}

	public function getErrors()
	{
		return $this->errors ?? [];
	}

	public function hasErrors()
	{
		return !empty($this->errors);
	}

	public function setErrors(array $errors)
	{
		foreach ($errors as $field => $error) {
			$this->setError($field, $error);
		}
		return $this;
	}

	public function getError($field)
	{
		return isset($this->errors[$field]) ? $this->_errors[$field] : null;
	}

	public function hasError($field)
	{
		return isset($this->errors[$field]);
	}

	public function setError($field, $error = null, bool $isCover = true)
	{
		if ($error === null || $error === false)
			unset($this->errors[$field]);
		else {
			if (!isset($this->errors[$field]) || $isCover) {
				$this->errors[$field] = static::buildErrorMessage($field, $error);
			}
		}
		return $this;
	}

	public function removeError($field)
	{
		unset($this->errors[$field]);
	}

	protected function beforeDestroy()
	{
	}

	protected function afterDestroy()
	{
	}

	public function destroy()
	{
		if ($this->isNew() || $this->sourceType === self::SOURCE_USER)
			return self::SAVE_FAILURE;
		if ($this->beforeDestroy() === false)
			return self::SAVE_FAILURE;
		$adapter = static::getDbAdapter();
		$builder = $adapter->getQueryBuilder();
		$table = static::getTable();
		$sql = null;
		$args = [];
		$builder->buildDelete($table, $builder->buildIn((array)$this->referenceData), $sql, $args);
		if ($adapter->execute($sql, $args) > 0) {
			if ($this->isCached() || static::isEnableCache())
				$this->destroyCache();
			$this->referenceData = null;
			$this->afterDestroy();
			return self::SAVE_SUCCESS;
		}
		return self::SAVE_FAILURE;
	}

	/////////////////////////////////////////////////////////////////////////////////////
	// cache 实例方法 部分
	/////////////////////////////////////////////////////////////////////////////////////

	public function isValidCache(): bool
	{
		return $this->isExists();
	}

//	protected function onCreateCache()
//	{
//	}
//
//	protected function onUpdateCache()
//	{
//	}
//
//	protected function onSaveCache()
//	{
//	}
//
//	protected function onDestroyCache()
//	{
//	}
//
//	public function saveCache()
//	{
//		if (!static::isEnableCache() || $this->isNew())
//			return self::SAVE_FAILURE;
//		$process = 'Update';
//		$lastStatus = $this->cacheStatus;
//		if (empty($this->cacheStatus)) {
//			$this->cacheStatus = self::ON_CREATE;
//			$process = 'Create';
//		}
//		elseif ($this->cacheStatus === self::ON_CREATE) {
//			$this->cacheStatus = self::ON_UPDATE;
//		}
//		$lastUpdatedAt = $this->cacheUpdatedAt;
//		$this->cacheUpdatedAt = time();
//		$this->cacheKey = static::makeCacheKey($this->getPk());
//		$event = "on{$process}Cache";
//		if ($this->$event() !== false &&
//		    $this->onSaveCache() !== false &&
//		    static::getCacheAdapter()->set($this->cacheKey, $this, $this->getCacheTTL())
//		) {
//			return self::SAVE_SUCCESS;
//		}
//		// 保存失败，要还原最后更新的时间，和状态
//		$this->cacheStatus = $lastStatus;
//		$this->cacheUpdatedAt = $lastUpdatedAt;
//		return self::SAVE_FAILURE;
//	}
//
//	public function destroyCache()
//	{
//		if (!static::isEnableCache() || $this->isNew() || !$this->isCache())
//			return self::SAVE_FAILURE;
//		$status = $this->cacheStatus;
//		$this->cacheStatus = self::ON_DELETE;
//		$key = static::makeCacheKey($this->getPk());
//		$event = 'onDestroyCache';
//		if ($this->$event() === false)
//			return self::SAVE_FAILURE;
//		if (static::getCacheAdapter()->delete($key)) {
//			$this->cacheStatus = false;
//			return self::SAVE_SUCCESS;
//		}
//		$this->cacheStatus = $status;
//		return self::SAVE_FAILURE;
//	}
//
//	public function isCache()
//	{
//		return $this->cacheStatus !== 0;
//	}
//
//	public function getCacheStatus()
//	{
//		return $this->cacheStatus;
//	}
//
//	public function getCacheTTL()
//	{
//		return static::$cacheTTL;
//	}
//
//	public function getCacheExpireDate()
//	{
//		if ($this->isCache())
//			return $this->cacheUpdatedAt + $this->getCacheTTL();
//		return -1;
//	}

}