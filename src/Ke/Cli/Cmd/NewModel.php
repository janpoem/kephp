<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Cli\Cmd;

use Ke\Adm\Adapter\DbAdapter;
use Ke\Adm\Db;
use Ke\Cli\ReflectionCommand;

/**
 * Class NewModel
 * @package Cmd
 */
class NewModel extends ReflectionCommand
{

	protected static $commandName = 'newModel';

	protected static $commandDescription = '';

	// define fields: type|require|default|field|shortcut
	//         types: string|integer|double|bool|dir|file|realpath|json|concat|dirs|files|any...
	// enjoy coding everyday~~
	/**
	 * @var string
	 * @type string
	 * @require true
	 * @field   1
	 */
	protected $className = '';

	protected $namespace = '';

	/**
	 * @var string
	 * @type string
	 * @field t
	 */
	protected $tableName = '';

	/**
	 * @var string
	 * @type string
	 * @field s
	 */
	protected $source = null;

	/** @var DbAdapter */
	protected $adapter = null;

	protected $src = KE_APP_NS_PATH;

	protected $tip = 'Creating model';

	protected function onConstruct($argv = null)
	{
		$this->adapter = Db::getAdapter($this->source);
		$this->className = str_replace('/', '\\', $this->className);
		list($this->namespace, $this->className) = parse_class($this->className);

		if (empty($this->tableName))
			$this->tableName = strtolower($this->className);
		$this->tableName = Db::mkTableName($this->source, $this->className, $this->tableName);

		if (empty($this->tableName))
			trigger_error('Invalid table name, please specify table -t=<tableName>', E_USER_ERROR);
	}

	protected function onExecute($argv = null)
	{
		$className = $this->getFullClassName();

		$this->console->printf('%s "%s" ...', $this->tip, $className);

		$message = $this->buildModel($this->tableName, $className, $this->getPath());

		$this->console->println(...$message);
	}

	public function getPath()
	{
		return $this->src . DS . str_replace('\\', DS, $this->getFullClassName()) . '.php';
	}

	public function buildModel(string $table, string $class, string $path)
	{
		list($namespace, $pureClass) = parse_class($class);
		$dir = dirname($path);
		$tpl = $this->getTplContent();
		$forge = $this->adapter->getForge();
		$vars = $forge->buildTableProps($table);
		$vars['namespace'] = $namespace;
		$vars['class'] = $pureClass;
		$vars['tableName'] = $table;
		$vars['datetime'] = date('Y-m-d H:i:s');

		if (is_file($path))
			return ['Fail', PHP_EOL, "File {$path} is existing!"];

		if (!is_dir($dir))
			mkdir($dir, 0755, true);

		if (file_put_contents($path, substitute($tpl, $vars))) {
			return ['Success'];
		}
		else {
			return ['Fail', PHP_EOL, 'I/O error, please try again!'];
		}
	}

	public function getTplContent()
	{
		return file_get_contents(__DIR__ . '/Templates/Model.tp');
	}

	public function getFullClassName()
	{
		$name = [];
		if (!empty(KE_APP_NS))
			$name[] = KE_APP_NS;
		$name[] = 'Model';
		if (!empty($this->namespace))
			$name[] = $this->namespace;
		$name[] = $this->className;
		return implode('\\', $name);
	}
}

