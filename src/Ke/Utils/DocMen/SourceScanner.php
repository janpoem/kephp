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


use DirectoryIterator;

class SourceScanner
{

	protected $source;

	private $reversionSource = '';

	protected $export;

	protected $externalFiles = [];

	private $externalId = 0;

	protected $files = [];

	protected $namespaces = [];

	protected $classes = [];

	protected $functions = [];

	protected $data = [];

	public function __construct(string $dir, string $export)
	{
		$this->source = realpath($dir);
		$this->reversionSource = str_replace('\\', '/', $this->source);
		if (empty($this->source) || !is_dir($this->source))
			throw new \Error("Please input a valid directory!");
		if (empty($export))
			throw new \Error('Please input the export dir!');
		$this->export = $export;
		if (!is_dir($this->export))
			mkdir($this->export, 0755, true);
		$this->export = realpath($this->export);
	}

	public function start()
	{
		$this->entry(new DirectoryIterator($this->source));
		return $this;
	}

	public function export()
	{
		file_put_contents(predir($this->getMainDataFile()),
			"<?php\r\nreturn " . var_export($this->getMainData(), true) . ";\r\n");
		$this->writeData();
		return $this;
	}

	private function sort()
	{
		ksort($this->files, SORT_STRING);
		ksort($this->namespaces, SORT_STRING);
		ksort($this->classes, SORT_STRING);
		ksort($this->functions, SORT_STRING);
	}

	public function getMainData()
	{
		$this->sort();
		return [
			'source'     => $this->source,
			'export'     => $this->export,
			'files'      => $this->files,
			'namespaces' => $this->namespaces,
			'classes'    => $this->classes,
			'functions'  => $this->functions,
		];
	}

	public function getHashDataFile(string $hash)
	{
		return $this->export . DS . $hash . '.php';
	}

	private function writeData()
	{
		foreach ($this->data as $hash => $data) {
			$path = $this->getHashDataFile($hash);
			file_put_contents(predir($path),
				"<?php\r\nreturn " . var_export($data, true) . ";\r\n");
		}
	}

	public function getMainDataFile()
	{
		return $this->export . DS . 'main.php';
	}

	public function entry(DirectoryIterator $dir)
	{
		foreach ($dir as $item) {
			if ($item->isDot())
				continue;
			$path = $item->getRealPath();
			if ($item->isDir())
				$this->entry(new DirectoryIterator($path));
			elseif ($item->isFile())
				$this->parseFile($path);
		}
		return $this;
	}

	public function isParseFile(string $path)
	{
		return (preg_match('/\.php$/', $path) &&
		        !preg_match('/[\\\\\/]classes\.php$/', $path) &&
		        !preg_match('/[\\\\\/]refs\.php$/', $path));
	}

	public function parseFile(string $path)
	{
		$this->addFile($path);

		if ($this->isParseFile($path)) {
			$fp = new FileParser($path);
			$fp->parse($this);

			$fns = $fp->getFunctions();
			if (!empty($fns)) {
				foreach ($fns as $name => $fn) {
					$this->addFunction($fn['namespace'], $name, $fn);
				}
			}
		}
		return $this;
	}


	public function addFile(string $fullPath, array $data = null)
	{
		$savePath = $this->filterPath($fullPath);
		$isExternal = false;
		$dir = $this->reversionSource;
		$path = $savePath;
		if (isset($this->externalFiles[$savePath])) {
			$isExternal = true;
			$dir = $this->externalFiles[$savePath]['dir'];
			$path = $this->externalFiles[$savePath]['path'];
//			$savePath = ;
		}
		if (!isset($this->files[$savePath])) {
			$this->files[$savePath] = [
				'name'       => $savePath,
				//				'atime'    => fileatime($fullPath),
				//				'ctime'    => filectime($fullPath),
				//				'mtime'    => filemtime($fullPath),
				'clsCount'   => 0,
				'fnCount'    => 0,
				'fn'         => [],
				'cls'        => [],
				'dir'        => $dir,
				'path'       => $path,
				'isExternal' => $isExternal,
			];
			if (!empty($data))
				$this->files[$savePath] = array_merge($this->files[$savePath], $data);
		}
		return $this;
	}

	public function addExternalFile(string $path)
	{
		if (!isset($this->externalFiles[$path])) {
			$same = compare_path($this->reversionSource, $path);
			if (!empty($same)) {
				$check = $same . '/';
				$cutPath = substr($path, strlen($check));
				$rename = 'External/' . $cutPath;
				$this->externalFiles[$path] = $this->externalFiles[$rename] = [
					'dir'  => $same,
					'path' => $cutPath,
				    'name' => $rename,
				];
			}

		}
		return $this;
	}

	public function filterPath(string $path)
	{
		if (strpos($path, '\\') !== false)
			$path = str_replace('\\', '/', $path);
		if (isset($this->files[$path]))
			return $path;
		elseif (isset($this->externalFiles[$path]))
			return $this->externalFiles[$path]['name'];
		$check = $this->reversionSource . '/';
		if (strpos($path, $check) === 0) {
			$path = substr($path, strlen($check));
		}
		elseif (is_file($path)) {
			$this->addExternalFile($path);
			$path = $this->externalFiles[$path]['name'];
		}
		return $path;
	}

	public function getFileData(string $path)
	{
		$savePath = $this->filterPath($path);
		return $this->files[$savePath] ?? false;
	}

	public function setFileData(string $path, array $data = null)
	{
		$savePath = $this->filterPath($path);
		if (!isset($this->files[$savePath])) {
			$this->addFile($path, $data);
		}
		elseif (!empty($data)) {
			$this->files[$savePath] = array_merge($this->files[$savePath], $data);
		}
		return $this;
	}

	public function addNamespace(string $namespace, array $data = null)
	{
		if (!isset($this->namespaces[$namespace])) {
			$hash = md5($namespace);
			$this->namespaces[$namespace] = [
				'name'     => $namespace,
				'hash'     => $hash,
				'clsCount' => 0,
				'fnCount'  => 0,
			];
			if (!empty($data))
				$this->namespaces[$namespace] = array_merge($this->namespaces[$namespace], $data);
		}
		return $this;
	}

	public function setNamespaceData(string $namespace, array $data = null)
	{
		if (!isset($this->namespaces[$namespace]))
			$this->addNamespace($namespace, $data);
		elseif (!empty($data))
			$this->namespaces[$namespace] = array_merge($this->namespaces[$namespace], $data);
		return $this;
	}

	public function addClass(string $class, ClassParser $parser)
	{
		$namespace = $parser->namespace;
		$hash = md5($namespace);
		$className = $parser->className;
		$export = get_object_vars($parser);
		$export['hash'] = $hash;
		if (!isset($this->data[$hash])) {
			$this->data[$hash] = [
				'cls' => [],
				'fn'  => [],
			];
		}
		if (!isset($this->data[$hash]['cls'][$className])) {
			// 添加namespace
			$this->addNamespace($namespace);
			$this->namespaces[$parser->namespace]['clsCount'] += 1;
			// 添加file
			$this->addFile($parser->file);
			$file = $this->filterPath($parser->file);
			$this->files[$file]['clsCount'] += 1;

			$this->data[$hash]['cls'][$className] = $export;
			$this->files[$file]['cls'][$className] = $hash;

		}

		if (!isset($this->classes[$class])) {
			$this->classes[$class] = $hash;
		}

		// 解析parent class
		if (!empty($parser->parent)) {
			$cp = new ClassParser($parser->parent, 'class');
			$cp->parse($this);
		}
		// 解析traits
		if (!empty($parser->traits)) {
			foreach ($parser->traits as $trait) {
				$cp = new ClassParser($trait, 'trait');
				$cp->parse($this);
			}
		}
		// 解析interfaces
		if (!empty($parser->impls)) {
			foreach ($parser->impls as $impl) {
				$cp = new ClassParser($impl, 'interface');
				$cp->parse($this);
			}
		}
		return $this;
	}

	public function addFunction(string $namespace = null, string $func, array $data)
	{
		$hash = md5($namespace);

		if (!isset($this->data[$hash])) {
			$this->data[$hash] = [
				'cls' => [],
				'fn'  => [],
			];
		}
		if (!isset($this->data[$hash]['fn'][$func])) {
			$this->addNamespace($namespace);
			$file = $this->filterPath($data['file']);
			// 添加file
			$this->addFile($data['file']);
			$this->files[$file]['fnCount'] += 1;
			$this->files[$file]['fn'][$func] = $hash;

			$this->data[$hash]['fn'][$func] = $data;
			$this->namespaces[$namespace]['fnCount'] += 1;
		}
		if (!isset($this->functions[$func])) {
			$this->functions[$func] = $hash;
		}
		return $this;

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
}