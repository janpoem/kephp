<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/22
 * Time: 5:13
 */

namespace Ke\Adm;

use ArrayObject;
use Ke\Adm\Query\SqlQuery;

/**
 * 数据库对象模型
 *
 * @package Ke\Adm
 */
class Model extends ArrayObject
{

	/** 用户手动创建的数据源（不可信） */
	const SRC_USER_CREATE = 0;

	/** 从数据库查询中得到的数据源 */
	const SRC_DB_QUERY = 1;

	/** 从缓存容器中加载的数据源 */
	const SRC_CACHE_LOAD = 2;

	const PROCESS_CREATE = 'Create';

	const PROCESS_UPDATE = 'Update';

	const SAVE_FAILURE = 0;

	const SAVE_SUCCESS = 1;

	const SAVE_NOTHING = 2;

	/**
	 * 已经被初始化的Model的记录
	 *
	 * Class => true | false
	 *
	 * @var array
	 */
	private static $initModels = [];

	private static $sourcePrefix = [];

	/**
	 * 自增主键的必须的字段属性
	 *
	 * @var array
	 */
	private static $pkAutoIncColumn = ['pk' => true, 'autoInc' => true, 'unique' => true];

	/**
	 * 非自增主键必须的字段属性
	 *
	 * @var array
	 */
	private static $pkNotAutoIncColumn = ['pk' => true, 'require' => true, 'empty' => false, 'unique' => true];

	/**
	 * 识别当前的Model是否被初始化，这个值不应该被在继承的Class中手动变更。
	 *
	 * @var bool 是否被初始化
	 */
	protected static $isInit = false;

	/**
	 * @var null|string 数据库的数据源名称
	 */
	protected static $dbSource = null;

	/**
	 * @var false|null|string 缓存的数据源名称，false表示为不使用缓冲容器
	 */
	protected static $cacheSource = false;

	/**
	 * 最终Model的数据表名称的存放字段
	 *
	 * 在初始化一个Model的时候，可以同时定义tableName和tablePrefix
	 *
	 * <code>
	 * class User extends Model {
	 *
	 *     protected static $tableName = 'user'; // 经过初始化，会变为：'ke_user'
	 *
	 *     protected static $tablePrefix = 'ke_';
	 * }
	 * </code>
	 *
	 * @var string 数据表名称
	 */
	protected static $tableName = '';

	/**
	 * @var string 数据表的前缀
	 */
	protected static $tablePrefix = null;

	/**
	 * 数据表字段定义
	 *
	 * fieldName => options
	 *
	 * @var array 定义的数据表字段
	 */
	protected static $columns = [];

	/**
	 * 字段按性质分组存放，经过初始化的Model会将不同性质的字段分组存放，用于
	 *
	 * @var array 字段按性质分组存放
	 */
	protected static $groupColumns = [];

	/**
	 * @var string 主键字段
	 */
	protected static $pkField = 'id';

	/**
	 * @var bool 主键是否为自增
	 */
	protected static $pkAutoInc = true;

	/**
	 * @var array 当前数据模型的默认值
	 */
	protected static $defaultData = [];

	protected static $regexEmail = '/^[0-9a-z][a-z0-9\._-]{1,}@[a-z0-9-]{1,}[a-z0-9]\.[a-z\.]{1,}[a-z]$/i';

	protected static $stdErrors = [
		'empty'           => '{label}不得为空！',
		'strLt'           => '{label}不得少于{min}个字符长度！',
		'strMt'           => '{label}不得多于{max}个字符长度！',
		'email'           => '{label}不是有效的邮箱格式，正确格式为：account@host.com。',
		'match'           => '{label}不符合指定格式！',
		'matchSample'     => '{label}不符合指定格式，正确格式为：{sample}',
		'equal'           => '{label}与{equalLabel}不符！',
		'inRange'         => '{label}值不在有效范围内！',
		'number'          => '{label}不是有效的数字！',
		'numberLt'        => '{label}数值不得小于{numMin}！',
		'numberMt'        => '{label}数值不得大于{numMax}！',
		'duplicate'       => '{label}已经存在{duplicateMsg}的记录！',
		'duplicate_error' => '多字段重复验证失败，缺少字段{duplicateMsg}！',
	];

	/**
	 * 初始化一个Model
	 */
	final public static function initModel()
	{
		$class = static::class;
		if (isset(self::$initModels[$class]))
			return true;
		static::initTable($class);
		static::initPkField();
		foreach (static::$columns as $field => $column) {
			static::setStaticColumn($field, $column, true);
		}
		$override = (array)static::overrideColumns($class);
		if (!empty($override)) {
			foreach ($override as $field => $column) {
				static::setStaticColumn($field, $column, false);
			}
		}
		static::onInitModel(static::class);
		self::$initModels[$class] = true;
		static::$isInit = &self::$initModels[$class];
		return true;
	}

	/**
	 *
	 *
	 * @param string $class
	 */
	protected static function onInitModel($class)
	{
	}

	protected static function initTable($class)
	{
		$prefix = static::$tablePrefix;
		if (empty($prefix)) {
			$source = Db::getSource(static::$dbSource);
			$prefix = self::$sourcePrefix[static::$dbSource] = empty($source['prefix']) ? '' : trim($source['prefix'], '_');
		}
		$table = static::$tableName;
		if (empty($table)) {
			list(, $cls) = parseClass($class);
			$table = strtolower($cls);
		}
		if (!empty($prefix)) {
			$table = $prefix . '_' . $table;
		}
		if ($table !== static::$tableName)
			static::$tableName = &$table;
	}

	/**
	 * 初始化主键字段，确保主键的字段被填充进static::$columns
	 */
	protected static function initPkField()
	{
		$isAutoInc = !empty(static::$pkAutoInc);
		if (!empty(static::$pkField)) {
			$column = $isAutoInc ? self::$pkAutoIncColumn : self::$pkNotAutoIncColumn;
			if (empty(static::$columns[static::$pkField])) {
				static::$columns = [static::$pkField => $column] + static::$columns;
//				static::$columns[static::$pkField] = $column;
			} else {
				static::$columns[static::$pkField] = array_merge(static::$columns[static::$pkField], $column);
			}
		}
		$column = &static::$columns[static::$pkField];
		if ($isAutoInc) {
			if (!isset($column['int']) && !isset($column['bigint']))
				$column['int'] = true;
		} else {
			if (!isset($column['max']))
				$column['max'] = 32;
		}
	}

	public static function getTableName($as = null)
	{
		if (!static::$isInit)
			static::initModel();
		if (!empty($as))
			return static::$tableName . ' AS ' . $as;
		return static::$tableName;
	}

	/**
	 * 重载数据模型字段的接口
	 *
	 * 以返回数组格式，来重载当前数据模型的字段定义
	 *
	 * @param $class
	 * @return null
	 */
	public static function overrideColumns($class)
	{
		return null;
	}

	public static function setStaticColumn($field, array $column, $isReplace = true)
	{
		$isPk = false;
		$isAutoInc = !empty($column['autoInc']);
		if (empty(static::$pkField) && !empty($column['pk'])) {
			$isPk = true;
			static::$pkField = &$field;
			static::$pkAutoInc = &$isAutoInc;
		}
		// 分组
		if (!empty($column['dummy']))
			static::$groupColumns['dummy'][$field] = true;
		if (!empty($column['require']))
			static::$groupColumns['require'][$field] = true;
		if (!empty($column['hidden']))
			static::$groupColumns['hidden'][$field] = true;
		if (!empty($column['unique']))
			static::$groupColumns['unique'][$field] = true;
		// 过滤
		if ($isAutoInc) {
			unset($column['default'], static::$defaultData[$field]);
		} else {
			$isDefineDefault = isset($column['default']);
			$default = isset($column['default']) ? $column['default'] : null;
			$numeric = 0;
			if (!empty($column['concat'])) {
				// concat序列化，必须为一个有效的数组
				$column['serialize'] = true;
				static::$groupColumns['serialize'][$field] = true;
				// 如果指定的default为 'a,b,c'，则先将他拆开
				if (is_string($default) && !empty($default))
					$default = explode($column['concat'], $default);
				else
					$default = (array)$default;
			} elseif (!empty($column['php']) || !empty($column['json'])) {
				// php和json序列化，就不对默认值进行任何处理了。
				$column['serialize'] = true;
				static::$groupColumns['serialize'][$field] = true;
			} elseif (isset($column['options'])) {
				if (empty($column['options']) || !is_array($column['options'])) {
					$column['options'] = null;
					$default = null;
				} elseif (!isset($default) || !isset($column['options'][$default])) {
					$default = array_keys($column['options'])[0];
				}
			} elseif (!empty($column['bool'])) {
				$default = (bool)$default; // 布尔类型，其实也是变向的options，强制转换
			} else {
				if (isset($column['numeric']) && is_numeric($column['numeric']))
					$numeric = (int)$column['numeric'];
				elseif (!empty($column['float']))
					$numeric = 3 + intval($column['float']);
				elseif (!empty($column['bigint']))
					$numeric = 2;
				elseif (!empty($column['int']))
					$numeric = 1;
				elseif ((isset($column['numMin']) && $column['numMin'] > 0) ||
					(isset($column['numMax']) && $column['numMax'] > 0)
				)
					$numeric = 1;
				else
					$numeric = 0;

				if ($numeric > 0) {
					if (empty($default) || !is_numeric($default))
						$default = 0;
					else {
						if ($numeric === 1)
							$default = (int)$default;
						elseif ($numeric >= 2)
							$default = (float)$default;
						if ($default === false)
							$default = 0;
						elseif ($default > 0 && $numeric >= 3) {
							$default = round($default, $numeric - 3);
						}
					}
				} else {
					// 字符串的有效默认值，应该是''，而不是null
					$default = trim((string)$default);
				}
			}

			if ($numeric > 0) {
				$column['numeric'] = $numeric;
			} elseif (isset($column['numeric'])) {
				$column['numeric'] = null;
			}

			if (empty($column['dummy']) || $isDefineDefault)
				static::$defaultData[$field] = $column['default'] = $default;
		}
		if ($isReplace) {
			static::$columns[$field] = $column;
		} else {
			if (!empty(static::$columns[$field]))
				static::$columns[$field] = $column + static::$columns[$field];
			else
				static::$columns[$field] = $column;
		}
	}

	public static function getStaticColumns()
	{
		if (!static::$isInit)
			static::initModel();
		return static::$columns;
	}

	public static function getStaticColumn($field, $process = null)
	{
		if (!static::$isInit)
			static::initModel();
		return isset(static::$columns[$field]) ? static::$columns[$field] : [];
	}

	public static function getDefaultData()
	{
		if (!static::$isInit)
			static::initModel();
		return static::$defaultData;
	}


	public static function getLabel($field)
	{
		if (isset(static::$columns[$field]['label']))
			return static::$columns[$field]['label'];
		if (isset(static::$columns[$field]['title']))
			return static::$columns[$field]['title'];
		return $field;
	}

	public static function staticFilterColumn(array $column, $value, $object = null, $process = null)
	{
		if (!empty($column['filter'])) {
			if (isset($object) && is_callable([$object, $column['filter']])) {
				$value = call_user_func([$object, $column['filter']], $value, $process);
			} elseif (is_callable([static::class, $column['filter']])) {
				$value = call_user_func([static::class, $column['filter']], $value, $process, $object);
			} elseif (is_callable($column['filter'])) {
				$value = call_user_func($column['filter'], $value, $process, $object);
			}
//			// 指定了返回的结果
//			if ($return !== null)
//				$value = $return;
		}
		// 序列化和自增字段，不做任何处理了
		if (!empty($column['autoInc'])) {
			return $value;
		}
		if (!empty($column['concat'])) {
			if (!is_array($value))
				$value = (array)$value;
			return $value;
		}
		if (!empty($column['serialize'])) {
			return $value;
		}
		// 指定了options选项，优先匹配options
//		if (!empty($column['options']) && is_array($column['options'])) {
//			if (!isset($column['options'][$value])) {
//				if (isset($column['default']))
//					return $column['default'];
//				$options = array_keys($column['options']);
//				return $options[0];
//			}
//			return $value;
//		}
		if (isset($column['numeric']) && $column['numeric'] > 0) {
			if (empty($value) || !is_numeric($value))
				$value = 0;
			else {
				if ($column['numeric'] === 1)
					$value = (int)$value;
				elseif ($column['numeric'] >= 2)
					$value = (float)$value;
				if ($value === false)
					$value = 0;
				elseif ($value > 0 && $column['numeric'] >= 3) {
					$value = round($value, $column['numeric'] - 3);
				}
			}
			return $value;
		}
		// 剩余就是字符串的处理
		$type = gettype($value);
		if ($type === KE_ARY || $type === KE_RES) {
			$value = '';
		} elseif ($type === KE_OBJ) {
			$value = method_exists($value, '__toString') ? strval($value) : '';
		} else {
			$value = trim($value);
		}
		if (!empty($column['trim']))
			$value = trim($value, $column['trim']);
		if (!empty($column['ltrim']))
			$value = ltrim($value, $column['ltrim']);
		if (!empty($column['rtrim']))
			$value = rtrim($value, $column['rtrim']);
		// 小写、大写只能是其中一种
		if (!empty($column['lower']))
			$value = mb_strtolower($value);
		elseif (!empty($column['upper']))
			$value = mb_strtoupper($value);

		// 默认为-1，去掉标签
		if (empty($column['html']))
			$value = strip_tags($value);
		elseif ($column['html'] === 'entity')
			$value = htmlentities($value, ENT_COMPAT);

		return $value;
	}

	public static function validateField2($field, $value, Model $object, $process)
	{
		$column = static::getStaticColumn($field, $process);
		// 过滤值
		$value = $object->$field = static::filterByColumn($column, $value, $object, $process);
		$object->processData[$field] = $value;
		// 必须的字段
		$isRequire = !empty($column['require']);
		$allowEmpty = isset($column['empty']) ? (bool)$column['empty'] : !$isRequire;
		$isEmail = isset($column['email']) && (bool)$column['email'] ? true : false;
		$isNumeric = !empty($column['numeric']);
		$isDummy = !empty($column['dummy']);

		if (!$object->hasError($field)) {
			$error = [null];

			// 数字格式检查
			if ($isNumeric) {
				if (!is_numeric($value)) {
					$error[0] = 'numeric';
				} elseif (!empty($column['numMin']) && is_numeric($column['numMin']) && $value < $column['numMin']) {
					$error[0] = 'numericLt';
					$error['numMin'] = $column['numMin'];
				} elseif (!empty($column['numMax']) && is_numeric($column['numMax']) && $value > $column['numMax']) {
					$error[0] = 'numericMt';
					$error['numMax'] = $column['numMax'];
				}
			} else {
				$length = mb_strlen($value);
				if (!$allowEmpty && $length <= 0) {
					$error[0] = 'empty';
				} elseif (!empty($column['min']) && is_numeric($column['min']) && $length < $column['min']) {
					$error[0] = 'strLt';
					$error['min'] = $column['min'];
				} elseif (!empty($column['max']) && is_numeric($column['max']) && $length > $column['max']) {
					$error[0] = 'strMt';
					$error['max'] = $column['max'];
				} elseif ($isEmail && preg_match(static::$regexEmail, $value) == 0) {
					$error[0] = 'email';
				} elseif (!empty($column['pattern'])) {
					$pattern = '';
					if (is_string($column['pattern']))
						$pattern = "#{$column['pattern']}#i";
					if (!empty($pattern) && !preg_match($pattern, $value)) {
						if (empty($column['sample'])) {
							$error[0] = 'match';
						} else {
							$error[0] = 'matchSample';
							$error['sample'] = $column['sample'];
						}
					}
				} elseif (!empty($column['equal']) && isset(static::$columns[$column['equal']])) {
					$error['equalLabel'] = static::getLabel($column['equal']);
					if (!isset($object[$column['equal']]) || !equals($value, $object[$column['equal']]))
						$error[0] = 'equal';
				} elseif (!empty($column['options']) && is_array($column['options']) && !empty($column['inRange'])) {
					if (!isset($column['options'][$value]))
						$error[0] = 'inRange';
				}
			}

			if (!empty($error[0])) {
				$object->setError($field, $error);
			}
		}

		if ($isDummy) {
			unset($object[$field], $object->processData[$field]);
		} else {
			if (equals($object->shadow[$field], $object->processData[$field]))
				unset($object->processData[$field]);
		}
	}

	protected static function getStdError($str)
	{
		return isset(static::$stdErrors[$str]) ? static::$stdErrors[$str] : $str;
	}

	public static function find($conditions = null)
	{
		if (!static::$isInit)
			static::initModel();
		if ($conditions instanceof SqlQuery)
			$conditions->table(static::$tableName)->setFetchType('all');
		elseif (is_array($conditions) && !isset($conditions['from'])) {
			$conditions['from'] = static::$tableName;
			$conditions['fetch'] = 'all';
		} elseif (empty($conditions)) {
			$conditions['from'] = static::$tableName;
			$conditions['fetch'] = 'all';
		}
		$data = Db::getAdapter(static::$dbSource)->find($conditions);
		foreach ($data as $index => &$row) {
			$obj = new static();
			$obj->initData($row, static::SRC_DB_QUERY);
			$data[$index] = $obj;
		}
		return $data;
	}

	private $isReady = false;

	private $source = null;

	private $reference = null;

	private $shadow = null;

	private $hidden = null;

	private $errors = null;

	private $processData = [];

	public function __construct(array $data = null)
	{
		$this->initData($data);
	}

	final public function initData(array $data = null, $source = self::SRC_USER_CREATE)
	{
		if (!static::$isInit)
			static::initModel();
//		if ($this->isReady)
//			return $this;
		$this->source = $source;
		if (empty($data))
			$data = static::$defaultData;
		elseif (!empty(static::$defaultData))
			$data = array_merge(static::$defaultData, $data);
		parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
		$this->prepareData();
//		$this->isReady = true;
		$this->onInitData($this->source);
		return $this;
	}

	final protected function prepareData()
	{
		$this->hidden = null;
		$this->shadow = null;
		$this->errors = null;
		$this->processData = null;
		if ($this->source !== self::SRC_USER_CREATE) {
			if (!empty(static::$pkField) && !empty($this[static::$pkField])) {
				$this->reference = [static::$pkField => $this[static::$pkField]];
			} else {
				$this->reference = (array)$this; // 无主键，只能拿整个数据来作为参考
			}
			// hidden fields
			if (isset(static::$groupColumns['hidden'])) {
				foreach (static::$groupColumns['hidden'] as $field => $isHidden) {
					$this->hidden[$field] = $this[$field];
					unset($this[$field]);
				}
			}
//			$this->process = self::PROCESS_UPDATE;
		} else {
			// 用户创建的，为创建
//			$this->process = self::PROCESS_CREATE;
		}
		$this->isReady = true;
		// 处理隐藏的字段
		return $this;
	}

	protected function onInitData($source)
	{
	}

	public function getPk()
	{
		if (!static::$isInit)
			static::initModel();
		if (!empty(static::$pkField) && !empty($this->reference[static::$pkField]))
			return $this->reference[static::$pkField];
		return false;
	}

	public function isNew()
	{
		return empty($this->reference);
	}

	public function isExists()
	{
		return !empty($this->reference);
	}

	public function offsetSet($field, $value)
	{
		// 用户创建的数据，不需要保存数据源
		if ($this->source !== self::SRC_USER_CREATE) {
			$shadow = null;
			if (isset($this->hidden[$field]))
				$shadow = $this->hidden[$field];
			elseif (isset($this[$field]))
				$shadow = $this[$field];
			if (!isset($this->shadow[$field]) && !equals($shadow, $value))
				$this->shadow[$field] = $shadow;
		}
		parent::offsetSet($field, $value);
	}

	public function merge(array $data)
	{
		foreach ($data as $field => $value) {
			$this[$field] = $value;
		}
		return $this;
	}

	protected function validateCreate(array &$data)
	{
	}

	protected function validateUpdate(array &$data)
	{
	}

	protected function validateSave(array &$data)
	{
	}

	protected function beforeCreate(array &$data)
	{
	}

	protected function beforeUpdate(array &$data)
	{
	}

	protected function beforeSave(array &$data)
	{
	}



	protected function afterSave(array &$data)
	{
	}



	protected function afterUpdate(array &$data)
	{
	}



	protected function afterCreate(array &$data)
	{
	}

	public function save(array $data = null)
	{
		if (isset($data))
			$this->merge($data);

		$return = self::SAVE_FAILURE;
		$process = empty($this->reference) ? self::PROCESS_CREATE : self::PROCESS_UPDATE;
		$validate = 'validate' . $process;
		$before = 'before' . $process;
		$after = 'after' . $process;
		$errors = &$this->errors;
		$this->processData = [];

		$data = (array)$this;
		if ($process === self::PROCESS_UPDATE) {
			$data = array_intersect_key($this->processData, $this->shadow);
		}

		if ($this->$validate($data) === false || $this->validateSave($data) === false || !empty($errors))
			return $return;
		$this->validate($data, $process);
		if (!empty($errors))
			return $return;
		if ($this->$before($data) === false || $this->beforeSave($data) === false || !empty($errors))
			return $return;
		$this->validate($data, $process);


		return $return;
	}

	public function getColumn($field, $process = null)
	{
		return isset(static::$columns[$field]) ? static::$columns[$field] : [];
	}

	public function validate($data, $process)
	{
		if (!is_array($data))
			$data = (array)$data;
		foreach ($data as $field => $value) {
			$this->validateField($field, $value, $process);
		}
//		var_dump($data);
//		$this->processData = (array)$this;
//		if ($this->process === self::PROCESS_UPDATE) {
//			// Update，只取出有差异部分的数据
//			$this->processData = array_intersect_key($this->processData, $this->shadow);
//		}
//		$this->processData = static::filterArray($this->processData, $this, $this->process);
//		var_dump($this->processData);

		return $this;
	}

	public function validateField($field, $value, $process)
	{
		$value = $this->filterField($field, $value, $process);
		$isRequire = !empty($column['require']);
		$allowEmpty = isset($column['empty']) ? (bool)$column['empty'] : !$isRequire;
		$isEmail = isset($column['email']) && (bool)$column['email'] ? true : false;
		$isNumeric = !empty($column['numeric']);


		if (!$this->hasError($field)) {
			$error = [null];
			// 数字格式检查
			if ($isNumeric) {
				if (!is_numeric($value)) {
					$error[0] = 'numeric';
				} elseif (!empty($column['numMin']) && is_numeric($column['numMin']) && $value < $column['numMin']) {
					$error[0] = 'numericLt';
					$error['numMin'] = $column['numMin'];
				} elseif (!empty($column['numMax']) && is_numeric($column['numMax']) && $value > $column['numMax']) {
					$error[0] = 'numericMt';
					$error['numMax'] = $column['numMax'];
				}
			} else {
				$length = mb_strlen($value);
				if (!$allowEmpty && $length <= 0) {
					$error[0] = 'empty';
				} elseif (!empty($column['min']) && is_numeric($column['min']) && $length < $column['min']) {
					$error[0] = 'strLt';
					$error['min'] = $column['min'];
				} elseif (!empty($column['max']) && is_numeric($column['max']) && $length > $column['max']) {
					$error[0] = 'strMt';
					$error['max'] = $column['max'];
				} elseif ($isEmail && preg_match(static::$regexEmail, $value) == 0) {
					$error[0] = 'email';
				} elseif (!empty($column['pattern'])) {
					$pattern = '';
					if (is_string($column['pattern']))
						$pattern = "#{$column['pattern']}#i";
					if (!empty($pattern) && !preg_match($pattern, $value)) {
						if (empty($column['sample'])) {
							$error[0] = 'match';
						} else {
							$error[0] = 'matchSample';
							$error['sample'] = $column['sample'];
						}
					}
				} elseif (!empty($column['equal']) && isset(static::$columns[$column['equal']])) {
					$error['equalLabel'] = static::getLabel($column['equal']);
					if (!isset($object[$column['equal']]) || !equals($value, $object[$column['equal']]))
						$error[0] = 'equal';
				} elseif (!empty($column['options']) && is_array($column['options']) && !empty($column['inRange'])) {
					if (!isset($column['options'][$value]))
						$error[0] = 'inRange';
				}
			}
		}
	}

	protected function filterField($field, $value, $process)
	{
		return static::staticFilterColumn($this->getColumn($field, $process), $value, $this, $process);
	}

	public function setErrors(array $errors)
	{
		if (empty($this->errors))
			$this->errors = $errors;
		else
			$this->errors = array_merge($this->errors, $errors);
		return $this;
	}

	public function getErrors()
	{
		return empty($this->errors) ? false : $this->errors;
	}

	public function hasErrors()
	{
		return !empty($this->errors);
	}

	public function setError($field, $message)
	{
		if (is_array($message)) {
			$str = array_shift($message);
			$str = static::getStdError($str);
			$message['label'] = static::getLabel($field);
			$message = substitute($str, $message);
		}
		$this->errors[$field] = $message;
		return $this;
	}

	public function hasError($field)
	{
		return !empty($this->errors[$field]);
	}

	public function getError($field)
	{
		return isset($this->errors[$field]) ? $this->errors[$field] : false;
	}

	public function removeError($field)
	{
		if (isset($this->errors[$field]))
			unset($this->errors[$field]);
		return $this;
	}
}