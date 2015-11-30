<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/30 0030
 * Time: 13:19
 */

namespace Ke\Cli\Command;

use Ke\Adm\Adapter\DatabaseImpl;
use Ke\Adm\DbSource;
use Ke\Cli\Command;
use Ke\Cli\ReflectionCommand;

class UpdateModel extends ReflectionCommand
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

		$console->writef('Updating model "{0}" ...', [$model], false);

		if (!is_file($this->classPath)) {
			$console->halt("Fail", PHP_EOL, "The file \"{$this->classPath}\" did not exist, please use add_model!");
		}

		if (!is_dir($this->dir))
			mkdir($this->dir, 0755, true);

		$forge = $this->adapter->getForge();
		$vars = $forge->mkTableVars($this->tableName);

		$content = file_get_contents($this->classPath);
		$split = preg_split('#\s{1}\*\s{1}\/\/\s{1}class\s{1}properties#i', $content);
		if (count($split) >= 3) {
			$split[1] = $vars['props'];
		}
		$content = implode('', $split);

		$split = preg_split('#[\t\s]+\/\/\s{1}database\s{1}columns#', $content);
		print_r($split);
		if (count($split) >= 3) {
			$split[1] = $vars['columns'];
		}
		$content = implode('', $split);

		$content = preg_replace_callback('#[\t\s]+protected[\t\s]+static[\t\s]+\$pkField[\t\s]+\=[\t\s]+([^\t\s]+)\;#i', function($matches) use ($vars) {
			return str_replace($matches[1], $vars['pkField'], $matches[0]);
		}, $content);

		$content = preg_replace_callback('#[\t\s]+protected[\t\s]+static[\t\s]+\$pkAutoInc[\t\s]+\=[\t\s]+([^\t\s]+)\;#i', function($matches) use ($vars) {
			return str_replace($matches[1], $vars['pkAutoInc'], $matches[0]);
		}, $content);

		if (file_put_contents($this->classPath, $content)) {
			$console->halt('Success');
		}
		else {
			$console->halt('Fail', PHP_EOL, 'I/O error, please try again!');
		}
	}
}