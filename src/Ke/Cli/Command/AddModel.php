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

		$console->writef('Creating model "{0}" ...', [$model], false);

		if (is_file($this->classPath)) {
			$console->halt("Fail", PHP_EOL, "The file \"{$this->classPath}\" is existing, please use update_model!");
		}

		if (!is_dir($this->dir))
			mkdir($this->dir, 0755, true);

		$forge = $this->adapter->getForge();
		$vars = $forge->mkTableVars($this->tableName);
		$vars['namespace'] = $this->namespace;
		$vars['class'] = $this->class;
		$vars['className'] = $this->className;
		$vars['tableName'] = $this->tableName;
		$vars['datetime'] = date('Y-m-d H:i:s');

		$tplPath = __DIR__ . '/Templates/Model.tp';
		$tpl = file_get_contents($tplPath);
		if (file_put_contents($this->classPath, substitute($tpl, $vars))) {
			$console->halt('Success');
		} else {
			$console->halt("Fail", PHP_EOL, "I/O error, please try again!");
		}
	}
}