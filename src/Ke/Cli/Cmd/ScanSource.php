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

use DirectoryIterator;
use Ke\App;
use Ke\Cli\ReflectionCommand;
use Ke\Utils\DocMen\SourceScanner;
use Ke\Utils\DocMen\FileParser;

class ScanSource extends ReflectionCommand
{

	protected static $commandName = 'scan_source';

	protected static $commandDescription = '';

	/**
	 * @var string
	 * @type dir
	 * @require true
	 * @field   1
	 */
	protected $dir = '';

	/**
	 * @var string
	 * @type string
	 * @require true
	 * @field   e
	 */
	protected $export = '';

	/** @var SourceScanner */
	protected $scanner;

	protected $includedFiles = [];

	protected $main = [];

	protected $namespaces = [];

	protected function onPrepare($argv = null)
	{
		$this->scanner = new SourceScanner($this->dir, $this->export);
	}

	protected function onExecute($argv = null)
	{
		$startParse = microtime();
		$this->scanner->start();
		$this->console->println(
			"Parse", count($this->scanner->getFiles()), 'files used', round(diff_milli($startParse), 4), 'ms,',
			'total parsed', count($this->scanner->getClasses()), 'classes,',
			count($this->scanner->getNamespaces()), 'namespaces,',
			count($this->scanner->getFunctions()), 'functions'
		);
		$startWrite = microtime();
		$this->scanner->export();
		$this->console->println("Write all data", 'used', round(diff_milli($startWrite), 4), 'ms');
	}
}