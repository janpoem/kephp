<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Utils\DocMen;

/**
 * DocMen，主要为文档数据的加载器，并且辅助查询
 *
 * @package Ke\Utils\DocMen
 */
class DocMen
{

	const CLS   = 'class';
	const IMPL  = 'interface';
	const TRAIT = 'trait';
	const NS    = 'namespace';
	const FUNC  = 'function';
	const FILE  = 'file';

	protected $docDir = '';

	private $mainDataFile = false;

	protected $source;

	protected $export;

	protected $files = [];

	protected $namespaces = [];

	protected $classes = [];

	protected $functions = [];

	protected $data = [];

	public function __construct(string $dir)
	{
		$this->docDir = realpath($dir);
		if (empty($this->docDir) || !is_dir($this->docDir))
			throw new \Error("Please input a valid directory!");

	}

	public function getMainDataFile()
	{
		return $this->docDir . DS . 'main.php';
	}

	public function getHashDataFile(string $hash)
	{
		return $this->docDir . DS . $hash . '.php';
	}

	public function loadMainData()
	{
		if ($this->mainDataFile === false) {
			$file = $this->getMainDataFile();
			if (!is_file($file))
				throw new \Error('Main data file does not found!');
			$this->mainDataFile = $file;
			$data = import($this->getMainDataFile(), null, KE_IMPORT_ARRAY);
			foreach ($data as $key => $value)
				$this->{$key} = $value;
		}
		return $this;
	}

	public function loadHashData(string $hash)
	{
		if (!isset($this->data[$hash])) {
			$this->data[$hash] = import($this->getHashDataFile($hash), null, KE_IMPORT_ARRAY);
		}
		return $this->data[$hash];
	}

	public function getSourceDir()
	{
		return $this->source;
	}

	public function getAllFiles()
	{
		foreach ($this->files as $file => $data) {
			yield $file => $data;
		}
	}

	public function getAllNamespaces()
	{
		foreach ($this->namespaces as $ns => $hash) {
			yield $ns => $hash;
		}
	}

	public function getAllClasses()
	{
		foreach ($this->classes as $cls => $hash) {
			yield $cls => $hash;
		}
	}

	public function getAllFunctions()
	{
		foreach ($this->functions as $fn => $hash) {
			yield $fn => $hash;
		}
	}

	public function getTypeName(string $type)
	{
		switch ($type) {
			case 'ns' :
			case 'namespace' :
				return self::NS;
			case 'fn' :
			case 'func' :
			case 'function' :
				return self::FUNC;
			case 'impl' :
			case 'if' :
			case 'interface' :
				return self::IMPL;
			case 'file' :
				return self::FILE;
			case 'trait' :
				return self::TRAIT;
			case 'cls' :
			case 'class' :
			case 'abstract class' :
			case 'final class' :
			default :
				return self::CLS;
		}
	}
}