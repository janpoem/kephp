<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/30
 * Time: 4:40
 */

namespace Ke\Cli\Command;

use Ke\Adm\Adapter\DatabaseImpl;
use Ke\Adm\DbSource;
use Ke\Adm\Exception;
use Ke\Cli\Command;
use Ke\Cli\ReflectionCommand;

/**
 * 添加一个Model
 *
 *
 * @package Ke\Cli\Command
 */
class AddModel extends ReflectionCommand
{

	protected static $commandName = 'add_model';

	protected static $commandDescription = 'Add a database model!';

	/**
	 * @var string
	 * @type string
	 * @require true
	 * @field   1
	 */
	protected $model = '';

	/**
	 * @var null
	 * @type string
	 * @field    source
	 * @shortcut s
	 */
	protected $dbSource = null;

	/**
	 * @var string
	 * @type string
	 * @field prefix
	 * @shortcut p
	 * @default Model
	 */
	protected $prefix = '';

	/** @var DatabaseImpl */
	private $adapter = null;


	private $className = '';

	private $classPath = '';

	private $dir = '';

	private $namespace = '';

	private $class = '';

	private $tableName = '';

	protected function onExecute(\Ke\Cli\Console $console, $argv = null)
	{
		$this->adapter = DbSource::getAdapter($this->dbSource);
		$model = trim($this->model, KE_PATH_NOISE);
		$this->prefix = trim($this->prefix, KE_PATH_NOISE);
		$this->className = trim(KE_APP_NS . "\\{$this->prefix}\\" . $model, KE_PATH_NOISE);
		$this->classPath = KE_APP_NS_PATH . DS . $this->className . '.php';
		if (!KE_IS_WIN)
			$this->classPath = str_replace('\\', '/', $this->classPath);
		$this->dir = dirname($this->classPath);
		$this->tableName = DbSource::mkTableName($this->dbSource, $model);

		list($this->namespace, $this->class) = parseClass($this->className);

		if (is_file($this->classPath))
			throw new Exception('The file "{file}" is existing!', ['file' => $this->classPath]);

		$forge = $this->adapter->getForge();
		$forge->mkModelVars($model, $this->tableName);
//		$columns = $forge->getTableColumns($this->tableName);

//		if (empty($columns)) {
//			throw new Exception('The table "{table}" did not exist, or there are no columns in it!', ['table' => $this->tableName]);
//		}

//		$modelColumns = [];
//		foreach ($columns as $column) {
//			list($field, $col) = $forge->filterTableColumnAsModelColumn($column);
//			$modelColumns[$field] = $col;
//		}

//		if (!is_dir($this->dir))
//			mkdir($this->dir, 0755, true);

//		$this->path = KE_SRC . DS . trim($this->model, KE_PATH_NOISE) .
//		list($this->namespace, $this->class) = parseClass($this->model);

	}
}