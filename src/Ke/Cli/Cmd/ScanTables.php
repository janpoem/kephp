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

class ScanTables extends ReflectionCommand
{

	protected static $commandName = 'scanTables';

	protected static $commandDescription = '';

	/**
	 * @var string
	 * @type string
	 * @field   1
	 */
	protected $source = '';

	/** @var DbAdapter */
	protected $adapter = null;

	protected $prefix = '';

	protected $tables = [];

	protected function onConstruct($argv = null)
	{
		$this->adapter = Db::getAdapter($this->source);
		$this->prefix = trim($this->adapter->getConfiguration()['prefix'], ' -_.');
		$this->tables = $this->adapter->getForge()->getDbTables();
	}

	protected function onExecute($argv = null)
	{
		$start = microtime();
		$total = 0;
		foreach ($this->tables as $table) {
			$class = $this->parseTableNameInGroup($table['name']);
			$command = new NewModel(['', $class]);
			if (is_file($command->getPath()))
				$command = new UpdateModel(['', $class]);
			$command->execute();
			$total++;
		}
		$usedTime = round(diff_milli($start), 4);
		$this->console->println("There are {$total} model create or update, used {$usedTime} ms!");
	}

	public function removePrefix(string $tableName): string
	{
		if (empty($this->prefix))
			return $tableName;
		if (stripos($tableName, "{$this->prefix}_") === 0) {
			return substr($tableName, strlen("{$this->prefix}_"));
		}
		return $tableName;
	}

	public function parseTableNameInGroup(string $tableName)
	{
		$name = $this->removePrefix($tableName);
		$parse = explode('_', $name);
		$namespace = ucfirst($parse[0]);
		$class = str_replace(' ', '_', ucwords(str_replace('_', ' ', $name)));
		return $namespace . '\\'. $class;
	}
}