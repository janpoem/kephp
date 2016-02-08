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
		return $this->dir . DS . 'main.php';
	}

	public function getHashDataFile(string $hash)
	{
		return $this->dir . DS . $hash . '.php';
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

	public function convertFileNameToUri(string $file)
	{
		return str_replace(['\\', '.'], ['/', '_'], $file);
	}

	public function getSource()
	{
		return $this->source;
	}

	public function getFiles()
	{
		foreach ($this->files as $file => $data) {
			yield $file => $this->convertFileNameToUri($file);
		}
	}

	public function convertClassToUri(string $class = null)
	{
		return str_replace(['\\'], ['/'], $class);
	}

	public function revertClass(string $class)
	{
		return str_replace(['/'], ['\\'], $class);
	}

	public function filterNamespace(string $ns = null)
	{
		return empty($ns) ? '&ltGlobal&gt' : $ns;
	}

	public function getNamespaces()
	{
		foreach ($this->namespaces as $ns => $hash) {
			yield $this->filterNamespace($ns) => $this->convertClassToUri($ns);
		}
	}

	public function getNamespacesCount()
	{
		return count($this->namespaces);
	}

	public function getClasses()
	{
		foreach ($this->classes as $cls => $hash) {
			yield $cls => $this->convertClassToUri($cls);
		}
	}

	public function getFunctions()
	{
		foreach ($this->functions as $fn => $hash) {
			yield $fn => $fn;
		}
	}

	public function getNamespace(string $ns)
	{

	}

	public function seek(string $scope, string $name)
	{
		$data = [];
		switch ($scope) {
			case 'fn' :
				$data = $this->functions;
				if (isset($data[$name])) {
					$hashData = $this->loadHashData($data[$name]);
				}
				break;
			case 'ns' :
				$data = $this->namespaces;
				$name = $this->revertClass($name);
				if (isset($data[$name])) {
					$data[$name]['children'] = $this->loadHashData($data[$name]['hash']);
					$data[$name]['name'] = $this->filterNamespace($data[$name]['name']);
					return $data[$name];
				}
				return false;
				break;
			case 'cls' :
				$data = $this->classes;
				$name = $this->revertClass($name);
				if (isset($data[$name])) {
					$hashData = $this->loadHashData($data[$name]);
					return $hashData['cls'][$name] ?? false;
				}
				break;
			default :
				$data = $this->files;
		}
		return false;
	}
}