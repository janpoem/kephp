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


class DocLoader
{

	protected $dir;

	protected $mainDataFile = '';

	protected $source;

	protected $export;

	protected $files = [];

	protected $namespaces = [];

	protected $classes = [];

	protected $functions = [];

	protected $data = [];

	public function __construct($dir)
	{
		$this->dir = realpath($dir);
		if (empty($this->dir) || !is_dir($this->dir))
			throw new \Error("Please input a valid directory!");
		$this->mainDataFile = $this->getMainDataFile();
		if (!is_file($this->mainDataFile))
			throw new \Error("The main data file does not exist!");
		$this->loadMainData();
	}

	public function getMainDataFile()
	{
		return $this->dir . DS . 'data' . DS . 'main.php';
	}

	public function getHashDataFile(string $hash)
	{
		return $this->dir . DS . 'data' . DS . $hash . '.php';
	}

	public function loadMainData()
	{
		$data = import($this->getMainDataFile(), null, KE_IMPORT_ARRAY);
		foreach ($data as $key => $value)
			$this->{$key} = $value;
		return $this;
	}

	public function loadHashData(string $hash)
	{
		if (!isset($this->data[$hash])) {
			$this->data[$hash] = import($this->getHashDataFile($hash), null, KE_IMPORT_ARRAY);
		}
		return $this->data[$hash];
	}

	public function getFiles()
	{
		return $this->files;
	}

	public function getNamespaces()
	{
		return $this->namespaces;
	}

	public function getClasses()
	{
		return $this->classes;
	}

	public function getFunctions()
	{
		return $this->functions;
	}

	public function seek(string $scope, string $name)
	{
//		$data = [];
//		switch ($scope) {
//			case 'fn' :
//				$data = $this->functions;
//				if (isset($data[$name])) {
//					$hashData = $this->loadHashData($data[$name]);
//					var_dump($hashData['fn'][$name]);
//				}
//				break;
//			case 'ns' :
//				$data = $this->namespaces;
//				break;
//			case 'cls' :
//				$data = $this->classes;
//
//				break;
//			default :
//				$data = $this->files;
//		}

	}
}