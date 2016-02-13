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
	protected $export;

	protected $dirs = [];

	protected $files = [];
	protected $_files = [];
	protected $_filesSort = [
		'priority' => [],
		'external' => [],
		'depth'    => [],
		'name'     => [],
	];
	protected $fileExtPriorities = [
		'php'   => 1,
		'js'    => 5,
		'phtml' => 10,
		'html'  => 20,
	];

	protected $indexes = [];

	protected $data = [];

	protected $namespaces = [];

	protected $functions = [];

	protected $classes = [];

	protected $classAliases = [];

	public function __construct(string $dir, string $export)
	{
		$this->source = realpath($dir);
		if (empty($this->source) || !is_dir($this->source))
			throw new \Error("Please input a valid source directory!");
		if (empty($export))
			throw new \Error('Export directory can not be empty!');
		$this->export = $export;
		if (!is_dir($this->export))
			mkdir($this->export, 0755, true);
		$this->source = $this->addDir($this->source);
		$this->export = DocMen::convertUnixPath($this->export);
	}

	public function start()
	{
		$this->entry(new DirectoryIterator($this->source));
		$this->sort();
		return $this;
	}

	private function sort()
	{
		// 文件排序
		array_multisort(
			$this->_filesSort['priority'], SORT_ASC | SORT_NUMERIC,
			$this->_filesSort['external'], SORT_DESC | SORT_NUMERIC,
			$this->_filesSort['depth'], SORT_ASC | SORT_NUMERIC,
			$this->_filesSort['name'], SORT_ASC | SORT_STRING,
			$this->files);
		ksort($this->namespaces, SORT_ASC | SORT_STRING);
		ksort($this->classes, SORT_ASC | SORT_STRING);
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
		$fileData = $this->filterFile($path);
		if ($fileData !== false && $this->isParseFile($path)) {
			$fileParser = new FileParser($path);
			$fileParser->parse($this);

//			$fns = $fp->getFunctions();
//			if (!empty($fns)) {
//				foreach ($fns as $name => $fn) {
//					$this->addFunction($fn['namespace'], $name, $fn);
//				}
//			}
		}
		return $this;
	}

	public function addDir(string $dir)
	{
		$dir = DocMen::convertUnixPath($dir);
		if (in_array($dir, $this->dirs) === false) {
			$this->dirs[] = $dir;
		}
		return $dir;
	}

	public function getDirIndex(string $dir)
	{
		return array_search($dir, $this->dirs);
	}

	public function getDir(int $index)
	{
		return $this->dirs[$index] ?? false;
	}

	public function getFilePriority(string $file): int
	{
		if (empty($file))
			return -1;
		$ext = trim(strtolower(strrchr($file, '.')), '. ');
		return $this->fileExtPriorities[$ext] ?? DocMen::DEFAULT_PRIORITY;
	}

	public function filterFile(string $file = null)
	{
		$file = DocMen::convertUnixPath($file);
		if (!isset($this->_files[$file])) {
			$isFile = is_file($file) && is_readable($file);
			$dir    = $this->source;
			$base   = $dir . '/';
			if (!$isFile) {
				// 空文件，将视作为php internal
				if (empty($file)) {
					$dir      = $this->addDir('');
					$path     = '';
					$priority = 0;
				} else {
					return false;
				}
			} else {
				if (strpos($file, $base) === 0) {
					$path = substr($file, strlen($base));
				} else {
					$dir = compare_path($this->source, $file);
					if (empty($dir)) {
						$dir  = $this->addDir(dirname($file));
						$path = basename($file);
					} else {
						$dir  = $this->addDir($dir);
						$path = substr($file, strlen($dir . '/'));
					}
				}
			}
			$key  = $path;
			$id   = $this->getDirIndex($dir);
			$data = [
				'isFile' => is_file($file),
				'dir'    => $id,
				'path'   => $path,
			];
			// 写入基础数据
			$this->_files[$file] = $this->_files[$path] = $key;
			$this->files[$key]   = $data;
			// 写入排序数据
			$this->_filesSort['priority'][$path] = $this->getFilePriority($path);
			$this->_filesSort['external'][$path] = $id;
			$this->_filesSort['depth'][$path]    = count(explode('/', $path));
			$this->_filesSort['name'][$path]     = $path;
			return $data;
		}
		$index = $this->_files[$file];
		return $this->files[$index];
	}

	public function addNamespace(string $namespace)
	{
		if (!isset($this->namespaces[$namespace])) {
			$this->namespaces[$namespace] = $this->namespaceHash($namespace);
//			$this->addIndex(DocMen::NS, $namespace);
		}
		return $namespace;
	}

	public function namespaceHash(string $namespace): string
	{
		return hash('crc32b', $namespace);
	}

	public function addFunction(FuncParser $parser)
	{
		$ns   = $parser->namespace;
		$name = $parser->name;
		$hash = $this->namespaceHash($ns);
		if (!isset($this->data[$hash])) {
			$this->data[$hash] = [
				'cls' => [],
				'fn'  => [],
			];
		}
		if (!isset($this->data[$hash]['fn'][$name])) {
			$this->addNamespace($ns);
//			$this->filterFile($parser->file);
			$this->data[$hash]['fn'][$name] = get_object_vars($parser);

			$this->addIndex(DocMen::FUNC, $parser->fullName);
		}
		if (!isset($this->functions[$name])) {
			$this->functions[$name] = $hash;
		}
		return $this;
	}

	public function addClass(ClassParser $parser)
	{
		$ns   = $parser->namespace;
		$name = $parser->name;
		$hash = $this->namespaceHash($ns);
		if (!isset($this->data[$hash])) {
			$this->data[$hash] = [
				'cls' => [],
				'fn'  => [],
			];
		}
		if (!isset($this->data[$hash]['cls'][$name])) {
			$this->addNamespace($ns);
//			$this->filterFile($parser->file);
			$this->data[$hash]['cls'][$name] = get_object_vars($parser);

			$this->aliasClass($parser->name, $parser->shortName);
			$this->addIndex(DocMen::CLS, $parser->name);
		}
		if (!isset($this->classes[$name])) {
			$this->classes[$name] = $hash;
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

	public function aliasClass(string $fullName, string $shortName)
	{
		if (!empty($fullName) && !isset($this->classAliases[$fullName]))
			$this->classAliases[$fullName] = $fullName;
		if (!empty($shortName) && $shortName !== $fullName && !isset($this->classAliases[$shortName]))
			$this->classAliases[$shortName] = $fullName;
		return $this;
	}

	public function addIndex(string $scope, string $fullName)
	{
		if (!empty($fullName) && !isset($this->indexes[$fullName])) {
			$this->indexes[$fullName] = [
				DocMen::filterScope($scope),
				$fullName,
			];
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

	public function export()
	{
		$this->writeFile($this->getDataFile('main'), $this->getMainData());
		$this->writeFile($this->getDataFile('index'), $this->indexes);
		foreach ($this->data as $hash => $data) {
			$this->writeFile($this->getDataFile($hash), $data);
		}
		return $this;
	}

	public function getDataFile(string $fileName)
	{
		return $this->export . '/' . $fileName . '.php';
	}

	public function writeFile($file, array $data)
	{
		file_put_contents(predir($file), "<?php\r\nreturn " . var_export($data, true) . ";\r\n");
		return $this;
	}

	public function getMainData()
	{
		return [
			'source'     => $this->source,
			'export'     => $this->export,
			'dirs'       => $this->dirs,
			'files'      => $this->files,
			'namespaces' => $this->namespaces,
			'classes'    => $this->classes,
			'aliases'    => $this->classAliases,
			'functions'  => $this->functions,
		];
	}

}