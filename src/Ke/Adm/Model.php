<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm;

use ArrayObject;
use Ke\Adm\Adapter\DatabaseImpl;
use Ke\Adm\Utils\SqlQuery;

use Ke\Adm\Utils\Filter;
use Ke\Adm\Utils\Validator;

class Model extends ArrayObject
{

	const SOURCE_USER = 0;
	const SOURCE_DB = 1;

	const ON_INIT = 'init';
	const ON_UPDATE = 'update';
	const ON_CREATE = 'create';

	const SAVE_FAILURE = 0;
	const SAVE_NOTHING = 1;
	const SAVE_SUCCESS = 2;

	private static $initModels = [];

	private static $pkAutoIncColumn = ['pk' => true, 'autoInc' => true, 'unique' => true];

	private static $pkNotAutoIncColumn = ['pk' => true, 'require' => true, 'unique' => true];

	private static $globalValidator = null;

	private static $globalFilter = null;

	protected static $isInitModel = false;

	protected static $dbSource = null;

	protected static $cacheSource = false;

	protected static $cacheTTL = 0;

	protected static $tableName = null;

	protected static $tablePrefix = null;

	protected static $columns = [];

	protected static $groupColumns = null;

	protected static $pkField = 'id';

	protected static $pkAutoInc = true;

	protected static $validatorClass = null;

	protected static $validator = null;

	protected static $filterClass = null;

	protected static $filter = null;

	private $isInitData = false;

	private $isCached = false;

	private $sourceType = self::SOURCE_USER;

	private $referenceData = null;

	private $shadowData = null;

	private $hiddenData = null;

	private $errors = null;

	public static function initModel()
	{
		$class = static::class;
		if (!isset(self::$initModels[$class]) || !static::$isInitModel) {
			// 初始化表名
			$table = DbSource::mkTableName(static::$dbSource, $class, static::$tableName, static::$tablePrefix);
			static::$tableName = &$table;
			// 初始化字段
			static::initColumns();
			static::onInitModel($class);
			self::$initModels[$class] = true;
			static::$isInitModel = &self::$initModels[$class];
		}
	}

	public static function getInitModels()
	{
		return self::$initModels;
	}

	public static function hasInitModel($class)
	{
		return isset(self::$initModels[$class]);
	}

	protected static function onInitModel($class)
	{
	}

	protected static function overrideColumns()
	{
		return false;
	}

	protected static function initColumns()
	{
		$filter = static::getFilter();
		$columns = static::$columns;
		$groupColumns = static::$groupColumns;
		$groupColumns['default'] = [];
		$defaultData = &$groupColumns['default'];
		$pk = null;
		$pkAutoInc = false;
		$pkInit = false;
		// 主键的处理
		if (!empty(static::$pkField) && is_string(static::$pkField)) {
			$pk = trim(static::$pkField);
			if (strlen($pk) <= 0) {
				$pk = null;
			}
		}
		if ($pk !== null) {
			$pkAutoInc = !empty(static::$pkAutoInc);
		}
		if ($pk !== null && !isset($columns[$pk])) {
			$pkInit = true;
			if (!empty($columns))
				$columns = [$pk => $pkAutoInc ? self::$pkAutoIncColumn : self::$pkNotAutoIncColumn] + $columns;
			else
				$columns = [$pk => $pkAutoInc ? self::$pkAutoIncColumn : self::$pkNotAutoIncColumn];
		}
		// load override columns
		// merge the static::$columns with override, and foreach once time
		// but here we don't used merge method, we used +, if the field exists, it will not be cover
		// in the foreach, then we will load the exists field from
		$override = static::overrideColumns();
		if (!empty($override) && is_array($override)) {
			$columns += $override;
		}
		foreach ($columns as $field => &$column) {
			if (!empty($override[$field]) && is_array($override[$field])) {
				$column = array_merge($column, $override[$field]);
			}
			if ($pk === null) {
				if (!empty($column['pk'])) {
					$pk = $field;
					$pkAutoInc = !empty($column['autoInc']);
				}
			}
			// 主键的过滤
			if ($pk === $field) {
				if (!$pkInit) {
					$pkInit = true;
					$column = array_merge($column, $pkAutoInc ? self::$pkAutoIncColumn : self::$pkNotAutoIncColumn);
				}
				if ($pkAutoInc) {
					if (empty($column['numeric']) && empty($column['numeric']))
						$column['numeric'] = 1;
					unset($column['default'], $defaultData[$pk]);
				} else {
					if (empty($column['max']))
						$column['max'] = 32;
					if (!isset($column['default']))
						$column['default'] = '';
					else
						$column['default'] = trim($column['default']);
					$defaultData[$pk] = $column['default'];
				}
				continue;
			}
			if (!empty($column['hidden']))
				$groupColumns['hidden'][$field] = true;
			if (!empty($column['require']))
				$groupColumns['require'][$field] = true;
			if (!empty($column['dummy']))
				$groupColumns['dummy'][$field] = true;
			$default = null;
			$isDefineDefault = false;
			$isDummy = !empty($columns['dummy']);
			$numeric = 0;
			if (isset($column['default'])) {
				$default = $column['default'];
				$isDefineDefault = true;
			} elseif (isset($defaultData[$field])) {
				$default = $defaultData[$field];
				$isDefineDefault = true;
			}
			if (!empty($column['numeric']))
				$numeric = (int)$column['numeric'];
			// filter options
			if (isset($column['options'])) {
				if (empty($column['options']) || !is_array($column['options'])) {
					unset($column['options']);
				} else {
					if (!isset($default) || !isset($column['options'][$default]))
						$default = array_keys($column['options'])[0];
				}
			} // 序列化操作的初始值，和一般的字段的初始值的处理不同，所以这里独立处理
			elseif (isset($column['concat'])) {
				if (empty($column['concat']))
					$column['concat'] = ',';
				if (empty($default))
					$default = [];
				elseif (is_string($default))
					$default = explode(',', $default);
				elseif (!is_array($default))
					$default = (array)$default;
				$column['serialize'] = ['concat', $column['concat']];
				$column['array'] = true;
				$groupColumns['serialize'][$field] = true;
			} elseif (!empty($column['json'])) {
				$column['serialize'] = ['json', null];
				$groupColumns['serialize'][$field] = true;
			} elseif (!empty($column['php'])) {
				$column['serialize'] = ['php', null];
				$groupColumns['serialize'][$field] = true;
			} else {
				// 到这里，首先就排除了序列化的可能性
				unset($column['serialize']);
				// 优先级，bool > timestamp > numeric > string
				if ($numeric > 0 ||
					!empty($column['int']) ||
					!empty($column['float']) ||
					!empty($column['bigint'])
				) {
					if (!empty($column['float'])) {
						$numeric = 3;
						if (is_numeric($column['float']))
							$numeric += $column['float'];
					} elseif (!empty($column['bigint']))
						$numeric = 2;
					elseif (!empty($column['int']))
						$numeric = 1;
					if ($numeric > 12) // 9位小数
						$numeric = 12;
					$column['numeric'] = $numeric;
				} else {
					// 到这里，也排除了是数值类型的可能性
					unset($column['numeric']);
				}
				// 这里就可以统一用过滤的方法，对默认值进行过滤处理了。
				$default = $filter->filterColumn($column, $default, null, self::ON_INIT);
			}
			// 虚构字段
//			if ($isDummy)
//				$column['dummy'] = true;
//			else
//				unset($column['dummy']);
			// 写入default
			// 如果是虚构的字段，而且没有定义默认值的话，就不写入默认值了。

			if (!$isDummy || $isDefineDefault)
				$defaultData[$field] = $column['default'] = $default;

			if (!empty($column[self::ON_CREATE])) {
				$columnClone = $column;
				unset($columnClone[self::ON_CREATE]);
				if (is_array($column[self::ON_CREATE])) {
					$columnClone = array_merge($columnClone, $column[self::ON_CREATE]);
				} else {
					$columnClone['default'] = $column[self::ON_CREATE];
				}
				$columnCloneDefault = isset($columnClone['default']) ? $columnClone['default'] : null;
				$columnClone['default'] = $filter->filterColumn($columnClone, $columnCloneDefault, null, self::ON_INIT);
				$groupColumns[self::ON_CREATE][$field] = $columnClone;

				$defaultData[$field] = $column['default'] = $columnClone['default'];
			}
			if (!empty($column[self::ON_UPDATE])) {
				$columnClone = $column;
				unset($columnClone[self::ON_UPDATE]);
				if (is_array($column[self::ON_UPDATE])) {
					$columnClone = array_merge($columnClone, $column[self::ON_UPDATE]);
				} else {
					$columnClone['default'] = $column[self::ON_UPDATE];
				}
				$columnCloneDefault = isset($columnClone['default']) ? $columnClone['default'] : null;
				$columnClone['default'] = $filter->filterColumn($columnClone, $columnCloneDefault, null, self::ON_INIT);
				$groupColumns[self::ON_UPDATE][$field] = $columnClone;

				$groupColumns['update_data'][$field] = $columnClone['default'];
			}
		}
		static::$columns = &$columns;
		static::$pkField = &$pk;
		static::$pkAutoInc = &$pkAutoInc;
		static::$groupColumns = &$groupColumns;
	}

	public static function getStaticColumns()
	{
		if (static::$isInitModel === false)
			static::initModel();
		return static::$columns;
	}

	public static function getStaticColumn($field, $process = null)
	{
		if (static::$isInitModel === false)
			static::initModel();
		if (isset(static::$groupColumns[$process][$field]))
			return static::$groupColumns[$process][$field];
		return isset(static::$columns[$field]) ? static::$columns[$field] : [];
	}

	public static function getGroupColumns($group = null)
	{
		if (static::$isInitModel === false)
			static::initModel();
		if (!isset($group))
			return static::$groupColumns;
		return empty(static::$groupColumns[$group]) ? [] : static::$groupColumns[$group];
	}

	public static function getDefaultData($field = null)
	{
		if (static::$isInitModel === false)
			static::initModel();
		if (!isset($field))
			return static::$groupColumns['default'];
		return isset(static::$groupColumns['default'][$field]) ? static::$groupColumns['default'][$field] : false;
	}

	public static function getLabel($field)
	{
		if (static::$isInitModel === false)
			static::initModel();
		if (isset(static::$columns[$field]['label']))
			return static::$columns[$field]['label'];
		if (isset(static::$columns[$field]['title']))
			return static::$columns[$field]['title'];
		return $field;
	}

	public static function getDbSource()
	{
		return static::$dbSource;
	}

	/**
	 * @return DatabaseImpl
	 * @throws Exception
	 */
	public static function getDbAdapter()
	{
		return DbSource::getAdapter(static::$dbSource);
	}

	public static function isEnableCache()
	{
		return static::$cacheSource !== false && !empty(static::$pkField);
	}

	public static function getCacheSource()
	{
		return static::$cacheSource;
	}

	public static function getCacheAdapter()
	{
		if (!static::isEnableCache())
			throw new Exception('Model "{0}" undefined cache source or the model without primary key!', [static::class]);
		return CacheSource::getAdapter(static::$cacheSource);
	}

	public static function getTable($as = null)
	{
		if (!static::$isInitModel)
			static::initModel();
		if (!empty($as))
			return static::$tableName . ' ' . $as;
		return static::$tableName;
	}

	public static function getPkField()
	{
		return empty(static::$pkField) ? false : static::$pkField;
	}

	public static function isPkAutoInc()
	{
		if (!empty(static::$pkField) && !empty(static::$pkAutoInc))
			return true;
		return false;
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
			} else {
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
		if (isset(static::$filter))
			return static::$filter;
		if (!empty(static::$filterClass)) {
			$class = static::$filterClass;
			if ($class === Filter::class || !is_subclass_of($class, Filter::class)) {
				$class = null;
				static::$filterClass = &$class;
			} else {
				$filter = new $class();
				static::$filter = &$filter;
				return static::$filter;
			}
		}
		if (!isset(self::$globalFilter))
			self::$globalFilter = new Filter();
		return self::$globalFilter;
	}

	/**
	 * 启动数据库事务
	 * @return bool
	 */
	public static function startTransaction()
	{
		return static::getDbAdapter()->startTransaction();
	}

	/**
	 * 检查数据库是否在事务状态中
	 *
	 * @return bool
	 */
	public static function inTransaction()
	{
		return static::getDbAdapter()->inTransaction();
	}

	/**
	 * 提交事务
	 *
	 * @return bool
	 */
	public static function commit()
	{
		return static::getDbAdapter()->commit();
	}

	/**
	 * 回滚事务
	 *
	 * @return bool
	 */
	public static function rollBack()
	{
		return static::getDbAdapter()->rollBack();
	}

	protected static function traditionFindPrepare(
		array $args,
		$type = DatabaseImpl::FETCH_ALL,
		$arrayMode = null,
		$fetchColumn = null
	) {
		if (!static::$isInitModel)
			static::initModel();
		$conditions = [];
		$pkField = static::$pkField;
		if (isset($args[0]) && is_array($args[0])) {
			$conditions = $args[0];
		} elseif (!empty($args)) {
			// model::find(1,2,3,4) => args = array(1,2,3,4)
			if (!empty($pkField)) {
				if (is_array($args[count($args) - 1])) {
					$conditions = array_pop($args);
				}
				$conditions['in'][$pkField] = $args;
			}
		}

		if (isset($conditions['fetchColumn'])) {
			$conditions['array'] = 1;
		}

		if (!empty($arrayMode))
			$conditions['array'] = $arrayMode;
		if (isset($fetchColumn)) {
			$conditions['array'] = 1;
			$conditions['fetchColumn'] = $fetchColumn;
		}

		if (empty($conditions['from']))
			$conditions['from'] = static::$tableName;

		static::onTraditionFind($conditions);

		if ($type === DatabaseImpl::FETCH_ALL) {
			// find
			if (isset($conditions['pageSize']) && is_numeric($conditions['pageSize']) && $conditions['pageSize'] > 0) {
				$pagination = ['pageSize' => intval($conditions['pageSize'])];
				// 过滤页码
				if (isset($conditions['pageNumber'])) {
					if (is_numeric($conditions['pageNumber']) && $conditions['pageNumber'] > 0)
						$pagination['pageNumber'] = intval($conditions['pageNumber']);
					else
						$pagination['pageNumber'] = 1;
				} else {
					if (empty($conditions['pageParam']))
						$conditions['pageParam'] = 'page';
					$pagination['pageParam'] = $conditions['pageParam'];
					// 如果指定了分页的字段，而且在$_GET中存在该值，且该值为大于0的整型
					if (isset($conditions['pageParam']) && isset($_GET[$conditions['pageParam']]) &&
						is_numeric($_GET[$conditions['pageParam']]) && $_GET[$conditions['pageParam']] > 0
					) {
						$pagination['pageNumber'] = intval($_GET[$conditions['pageParam']]);
					} else {
						$pagination['pageNumber'] = 1;
					}
				}

				// 记录总数
				$pagination['recordCount'] = static::getDbAdapter()->count($conditions);
				// 分页总数
				$pagination['pageCount'] = intval($pagination['recordCount'] / $pagination['pageSize']);
				if ($pagination['recordCount'] % $pagination['pageSize'] > 0)
					$pagination['pageCount']++;
				// 限制页码
				if ($pagination['pageNumber'] < 1)
					$pagination['pageNumber'] = 1;
				elseif ($pagination['pageNumber'] > $pagination['pageCount'] - 1)
					$pagination['pageNumber'] = $pagination['pageCount'];
				$conditions['limit'] = $pagination['pageSize'];
				$conditions['offset'] = ($pagination['pageNumber'] - 1) * $conditions['limit'];
				$conditions['pagination'] = $pagination;
			} else {
				if (isset($conditions['limit']) && is_numeric($conditions['limit']) && $conditions['limit'] > 0) {
					$conditions['limit'] = intval($conditions['limit']);
				} else {
					unset($conditions['limit']);
				}
				if (isset($conditions['offset']) && is_numeric($conditions['offset']) && $conditions['offset'] > -1) {
					$conditions['offset'] = intval($conditions['offset']);
				} else {
					unset($conditions['offset']);
				}
			}
		} else {
			// findOne
			$conditions['limit'] = 1;
		}
		$conditions['fetch'] = $type;
		return $conditions;
	}

	protected static function onTraditionFind(array &$conditions)
	{
	}

	/**
	 * @param null $tableAs
	 * @return SqlQuery
	 * @throws Exception
	 */
	public static function query($tableAs = null)
	{
		return (new SqlQuery())->setModel(static::class)->table(static::getTable($tableAs));
	}

	public static function find($conditions = null)
	{
		if (!($conditions instanceof SqlQuery)) {
			$conditions = static::traditionFindPrepare(func_get_args(), DatabaseImpl::FETCH_ALL);
			$fetchNum = !empty($conditions['array']);
		} else {
			$fetchNum = $conditions->getFetchStyle() === DatabaseImpl::FETCH_NUM;
		}
		$result = static::getDbAdapter()->find($conditions);
		if ($fetchNum)
			return $result;
		return static::newList($result, $conditions, self::SOURCE_DB);
	}

	public static function findOne($conditions = null)
	{
		if (!($conditions instanceof SqlQuery)) {
			$conditions = static::traditionFindPrepare(func_get_args(), DatabaseImpl::FETCH_ONE);
			$fetchNum = !empty($conditions['array']);
		} else {
			$fetchNum = $conditions->getFetchStyle() === DatabaseImpl::FETCH_NUM;
		}
		$result = static::getDbAdapter()->find($conditions);
		if ($fetchNum)
			return $result;
		if ($result === false)
			return new static();
		return static::newInstance($result, $conditions, self::SOURCE_DB);
	}

	public static function findIn(array $in, array $conditions = null)
	{
		if (!isset($conditions))
			$conditions = [];
		$conditions['in'] = $in;
		return static::find($conditions);
	}

	public static function findOneIn(array $in, array $conditions = null)
	{
		if (!isset($conditions))
			$conditions = [];
		$conditions['in'] = $in;
		return static::findOne($conditions);
	}

	public static function findColumn()
	{
		$args = func_get_args();
		$column = array_shift($args);
		$conditions = self::traditionFindPrepare($args, DatabaseImpl::FETCH_ALL, true, $column);
		return static::getDbAdapter()->find($conditions);
	}

	public static function findColumnOne()
	{
		$args = func_get_args();
		$column = array_shift($args);
		$conditions = self::traditionFindPrepare($args, DatabaseImpl::FETCH_ONE, true, $column);
		return static::getDbAdapter()->find($conditions);
	}

	public static function rsCount($conditions = null)
	{
		if ($conditions instanceof SqlQuery) {
			if (empty($conditions->table))
				$conditions->table = static::$tableName;
		} elseif (is_array($conditions)) {
			if (empty($conditions['from']))
				$conditions['from'] = static::$tableName;
		}
		return static::getDbAdapter()->count($conditions);
	}

	public static function rsCountIn(array $in, array $conditions = null)
	{
		if ($conditions instanceof SqlQuery) {
			if (empty($conditions->table))
				$conditions->table = static::$tableName;
			foreach ($in as $field => $values) {
				$conditions->where($field, 'in', $values);
			}
		} elseif (is_array($conditions)) {
			if (empty($conditions['from']))
				$conditions['from'] = static::$tableName;
			$conditions['in'] = $in;
		}
		return static::getDbAdapter()->count($conditions);
	}

	public static function mkCacheKey($pk)
	{
		return static::$tableName . '.' . $pk;
	}

	public static function getCache($pk)
	{
		if (!static::$isInitModel)
			static::initModel();
		if (!static::isEnableCache())
			throw new Exception('Model "{0}" undefined cache source or the model without primary key!', [static::class]);
		if (strlen($pk) === 0)
			throw new Exception('Undefined primary key!');
		$key = static::mkCacheKey($pk);
		$cs = static::getCacheAdapter();
		$load = $cs->get($key);
		if ($load !== false) {
			return $load;
		}
		$obj = static::findOne($pk);
		if ($obj->isNew()) {
			return $obj;
		}
		$obj->saveCache(self::ON_CREATE);
		return $obj;
	}

	public static function update($conditions, array $data)
	{
		return static::getDbAdapter()->update(static::$tableName, $conditions, $data);
	}

	public static function updateIn(array $in, array $data)
	{
		return static::getDbAdapter()->update(static::$tableName, ['in' => $in], $data);
	}

	public static function delete($conditions)
	{
		return static::getDbAdapter()->delete(static::$tableName, $conditions);
	}

	public static function deleteIn(array $in)
	{
		return static::getDbAdapter()->delete(static::$tableName, ['in' => $in]);
	}

	protected static function newList(array $data, $conditions, $source = null)
	{
		$list = new DataList();
		if ($conditions instanceof SqlQuery) {
			if ($conditions->hasPagination())
				$list->setPagination($conditions->getPagination());
		} else {
			if (isset($conditions['pagination']))
				$list->setPagination($conditions['pagination']);
		}
		foreach ($data as $index => $row) {
			$list[] = static::newInstance($row, $conditions, $source);
		}
		return $list;
	}

	protected static function newInstance(array $data, $conditions, $source = null)
	{
		$obj = new static(false);
		$obj->prepareData($data, $source);
		return $obj;
	}

	public function __construct($data = null)
	{
		if ($data !== false) {
			if (empty($data) || !is_array($data))
				$data = [];
			$this->prepareData($data);
		}
	}

	final private function prepareData(array $data, $source = self::SOURCE_USER)
	{
		if (!static::$isInitModel)
			static::initModel();
		// 优先绑定了数据源，和数据的参考依据，防止这个数据被污染
		$this->sourceType = $source;
		// 重置数据
		$this->referenceData = null;
		$this->shadowData = null;
		if (!empty($data) && $source !== self::SOURCE_USER) {
			if ($this->isInitData) {
				$data += (array)$this;
				if (!empty(static::$groupColumns['dummy'])) {
					$data = array_diff_key($data, static::$groupColumns['dummy']);
				}
			}

			if (!empty(static::$pkField) && !empty($data[static::$pkField]))
				$this->referenceData = [static::$pkField => $data[static::$pkField]];
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
		} else {

		}
		// 反序列化，必须处理
		if (!empty(static::$groupColumns['serialize'])) {
			$filter = static::getFilter();
			foreach (static::$groupColumns['serialize'] as $field => $_) {
				if (isset($data[$field]))
					$data[$field] = $filter->unSerialize($data[$field]);
			}
		}
		if ($this->isInitData === false) {
			// 如果是直接从数据源加载的，不用defaultData来填充默认的字段（如果只是数据片段，则只作为片段处理）
			if ($source === self::SOURCE_USER) {
				if (empty($data))
					$data = static::$groupColumns['default'];
				else
					$data = array_merge(static::$groupColumns['default'], $data);
			}
			parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
			$this->isInitData = true;
			$this->onInitData();
		} else {
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

	protected function onCreateCache()
	{
	}

	protected function onUpdateCache()
	{
	}

	protected function onDestroyCache()
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

	public function getPk()
	{
		if (!empty(static::$pkField) && isset($this->referenceData[static::$pkField]))
			return $this->referenceData[static::$pkField];
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

	public function getCacheTTL()
	{
		return static::$cacheTTL;
	}

	public function getColumn($field, $process = null)
	{
		return static::getStaticColumn($field, $process);
	}

	public function offsetSet($field, $value)
	{
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
			} elseif ($value === $this->shadowData[$field]) {
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

	public function merge(array $data)
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
		$this->errors = null;
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
		// 第一次
		if ($this->$validate($data) === false || $this->validateSave($process, $data) === false || !empty($this->errors))
			return self::SAVE_FAILURE;
		if (!empty($errors = $validator->validateModelData($this, $data, $process, false)))
			$this->errors = array_merge((array)$this->errors, $errors);
		if (!empty($this->errors))
			return self::SAVE_FAILURE;

		if ($this->$before($data) === false || $this->beforeSave($process, $data) === false || !empty($this->errors))
			return self::SAVE_FAILURE;
		if (!empty($errors = $validator->validateModelData($this, $data, $process, true)))
			$this->errors = array_merge((array)$this->errors, $errors);
		if (!empty($this->errors))
			return self::SAVE_FAILURE;

		$db = DbSource::getAdapter(static::$dbSource);
		$table = static::$tableName;
		if ($process === self::ON_CREATE) {
			$result = $db->insert($table, $data);
			if ($result > 0) {
				if (!empty(static::$pkField) && !empty(static::$pkAutoInc)) {
					$data[static::$pkField] = $db->lastInsertId($table);
					static::getFilter()->filterColumn(static::$columns[static::$pkField], $data[static::$pkField], $this);
				}
				$this->prepareData($data, self::SOURCE_DB);
				$this->$after($data);
				$this->afterSave($process, $data);
				if ($this->isCached || static::isEnableCache())
					$this->saveCache($process);
				return self::SAVE_SUCCESS;
			}
		} elseif ($process === self::ON_UPDATE) {
			$diff = array_diff_key($data, $updateData);
			if (empty($diff)) {
				$this->restore();
				return self::SAVE_NOTHING;
			}
			$result = $db->update($table, $data, [
				'in' => $this->referenceData,
			]);
			if ($result > 0) {
				$this->prepareData($data, self::SOURCE_DB);
				$this->$after($data);
				$this->afterSave($process, $data);
				if ($this->isCached || static::isEnableCache())
					$this->saveCache($process);
				return self::SAVE_SUCCESS;
			}
		}
		return self::SAVE_FAILURE;
	}

	public function saveCache($process = self::ON_CREATE)
	{
		if (!static::isEnableCache() || $this->isNew())
			return false;
		$this->isCached = true;
		if (static::getCacheAdapter()->set(static::mkCacheKey($this->getPk()), $this, $this->getCacheTTL())) {
			$event = 'on' . $process . 'cache';
			$this->$event();
			return true;
		}
		return false;
	}

	public function getErrors()
	{
		return $this->errors;
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

	public function setError($field, $error = null)
	{
		if ($error === null)
			unset($this->errors[$field]);
		else {
			if (is_array($error)) {
				$validator = static::getValidator();
				$error = $validator->mkErrorMessage($this, $field, $error);
			}
			$this->errors[$field] = $error;
		}
		return $this;
	}

	protected function beforeDestroy()
	{
	}

	protected function afterDestroy()
	{
	}

	public function destroy()
	{
		if (empty($this->referenceData) || $this->sourceType === self::SOURCE_USER)
			return false;
		$query = [
			'in'    => $this->referenceData,
			'limit' => 1,
		];
		if ($this->beforeDestroy() === false)
			return false;
		$count = static::getDbAdapter()->delete(static::$tableName, $query);
		if ($count > 0) {
			if ($this->isCached || static::isEnableCache())
				$this->destroyCache();
			$this->referenceData = null;
			$this->afterDestroy();
			return true;
		}
		return false;
	}

	public function destroyCache()
	{
		if (!static::isEnableCache() || $this->isNew())
			return false;
		$this->isCached = false;
		if (static::getCacheAdapter()->delete(static::mkCacheKey($this->getPk()))) {
			$this->onDestroyCache();
			return true;
		}
		return false;
	}
}