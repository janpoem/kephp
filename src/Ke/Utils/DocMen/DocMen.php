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

use Ke\Web\Asset;
use Ke\Web\Web;

/**
 * DocMen，主要为文档数据的加载器，并且辅助查询
 *
 * @package Ke\Utils\DocMen
 */
class DocMen
{

	const NS_STYLE_OLD_PEAR = 0;
	const NS_STYLE_NEW      = 1;
	const NS_STYLE_MIXED    = 2;

	const PHP_INTERNAL = '&lt;PHP Internal&gt';

	const DEFAULT_PRIORITY = 100;

	const CLS    = 'class';
	const IMPL   = 'interface';
	const TRAIT  = 'trait';
	const NS     = 'namespace';
	const FUNC   = 'function';
	const METHOD = 'method';
	const PROP   = 'property';
	const CONST  = 'constant';
	const FILE   = 'file';
	const INDEX  = 'index';

	private static $registerInstances = [];

	private $scanner = null;

	private $scannerOptions = [];

	private $isPrepare = false;

	private $routePath = 'docmen';

	private $docDir = '';

	private $loadMainFile = false;

	private $loadComment = false;

	protected $showFile = true;

	protected $generable = true;

	protected $asset = null;

	protected $routeScopes = [
		self::CLS  => self::CLS,
		self::NS   => self::NS,
		self::FUNC => self::FUNC,
		self::FILE => self::FILE,
	];

	protected $source;

	protected $export;

	protected $dirs = [];

	protected $files = [];

	protected $namespaces = [];

	protected $classes = [];

	protected $functions = [];

	protected $data = [];

	protected $comments = [];

	protected $missed = [];


	/**
	 * 向全局的Web分发路由器注册一个（多个）DocMen实例
	 *
	 * @param DocMen[] ...$docs 需要注册的DocMen实例
	 * @return bool 返回是否添加成功
	 */
	public static function register(DocMen ...$docs)
	{
		$routes = [];
		foreach ($docs as $doc) {
			if (isset(self::$registerInstances[$doc->routePath]))
				continue;
			self::$registerInstances[$doc->routePath] = $doc;
			$routes += $doc->getRoutes();
		}
		if (!empty($routes)) {
			Web::registerRoutes($routes);
			return true;
		}
		return false;
	}

	public static function remove(DocMen $doc)
	{
		static::removePath($doc->routePath);
	}

	public static function removePath(string $path)
	{
		if (isset(self::$registerInstances[$path])) {
			unset(self::$registerInstances[$path]);
			Web::removeRoute($path);
			return true;
		}
		return false;
	}

	public static function getAllInstances()
	{
		foreach (self::$registerInstances as $name => $docMen) {
			yield $name => $docMen;
		}
	}

	/**
	 * @param string $path
	 * @return DocMen
	 * @throws \Error
	 */
	public static function getInstance(string $path)
	{
		if (isset(self::$registerInstances[$path]))
			return self::$registerInstances[$path];
		throw new \Error('Does not found DocMen instance register by "' . $path . '"!');
	}

	public static function getStdAssetLibraries()
	{
		// ['vendor/prism/prism', 'css', ['id' => 'prism_theme_css']],
		// ['vendor/prism/prism', 'js'],
		return [
			'docmen' => [
				['//cdn.bootcss.com/semantic-ui/2.1.8/semantic.min.css', 'css'],
				['//cdn.bootcss.com/jquery/1.12.0/jquery.js', 'js'],
				['//cdn.bootcss.com/jquery.address/1.6/jquery.address.min.js', 'js'],
				['//cdn.bootcss.com/semantic-ui/2.1.8/semantic.min.js', 'js'],
				['//cdn.bootcss.com/marked/0.3.5/marked.min.js', 'js'],
				'prism',
			],
			'prism'  => [
				['http://7xqwoj.com1.z0.glb.clouddn.com/prism%2Fprism.css', 'css', ['id' => 'prism_theme_css']],
				['http://7xqwoj.com1.z0.glb.clouddn.com/prism%2Fprism.js', 'js',],
			],
		    'hljs' => [
			    ['//cdn.bootcss.com/highlight.js/9.1.0/highlight.min.js', 'js'],
			    ['//cdn.bootcss.com/highlight.js/9.1.0/styles/tomorrow-night.min.css', 'css', ['id' => 'hljs_theme_css']],
		    ],
		];
	}

	public static function filterScope($scope)
	{
		switch ($scope) {
			case 'ns' :
			case 'namespace' :
				return self::NS;
			case 'fn' :
			case 'func' :
			case 'function' :
				return self::FUNC;
			case 'method' :
				return self::METHOD;
			case 'prop' :
			case 'property' :
				return self::PROP;
			case 'const' :
			case 'constant' :
				return self::CONST;
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
				return self::CLS;
			default :
				if (empty($scope))
					return self::INDEX;
				else
					return self::CLS;
		}
	}

	public static function filterName($scope, string $name)
	{
		switch (static::filterScope($scope)) {
			case self::FILE :
				if (empty($name))
					return '';
				return $name;
			case self::NS :
			case self::FUNC :
			default :
				if (empty($name))
					return '';
				return str_replace('/', '\\', $name);
		}
	}

	public static function getShowName($scope, string $name)
	{
		switch (static::filterScope($scope)) {
			case self::NS :
				if (empty($name))
					return 'Global';
			case self::FILE :
				if (empty($name))
					return 'PHP Internal';
			case self::FUNC :
			default :
		}
		return $name;
	}

	public static function mkScopePath($scope, string $name)
	{
		switch (static::filterScope($scope)) {
			case self::NS :
				return 'namespace/' . static::convertClassToUri($name);
			case self::FILE :
				return 'file/' . $name;
			case self::FUNC :
				return 'function/' . static::convertClassToUri($name);
			default :
				return self::CLS . '/' . static::convertClassToUri($name);
		}
	}

	public static function convertClassToUri(string $name)
	{
		return str_replace('\\', '/', $name);
	}

	public static function revertClass(string $name)
	{
		return str_replace('/', '\\', $name);
	}

	public static function packetClassData(array $data): array
	{
		$className = $data['name'];
		$packages  = [];
		$sort      = [];
		$position  = 1;
		foreach ($data['methods'] as $method) {
			$sourceClass = $method['sourceClass'];
			if ($sourceClass === $className)
				$key = 'Methods';
			else
				$key = $sourceClass . '::Methods';
			if (!isset($packages[$key])) {
				$packages[$key] = [
					'class' => $sourceClass,
					'type'  => self::METHOD,
					'items' => [],
					'count' => 0,
				];
				$sort[$key]     = $position;
				$position *= 10;
			}
			$packages[$key]['items'][$method['name']] = $method;
			$packages[$key]['count'] += 1;
		}
		$position = 2;
		foreach ($data['props'] as $name => $prop) {
			$sourceClass = $prop['sourceClass'];
			if ($sourceClass === $className)
				$key = 'Properties';
			else
				$key = $sourceClass . '::Properties';
			if (!isset($packages[$key])) {
				$packages[$key] = [
					'class' => $sourceClass,
					'type'  => self::PROP,
					'items' => [],
					'count' => 0,
				];
				$sort[$key]     = $position;
				$position *= 10;
			}
			$packages[$key]['items'][$name] = $prop;
			$packages[$key]['count'] += 1;
		}
		if (!empty($data['constants'])) {
			$key            = 'Constants';
			$position       = 1000;
			$packages[$key] = [
				'class' => $data['className'],
				'type'  => self::CONST,
				'items' => $data['constants'],
				'count' => count($data['constants']),
			];
			$sort[$key]     = $position;
		}
		array_multisort($sort, SORT_ASC, $packages);
		return $packages;
	}

	public static function filterAccess($value): string
	{
		switch ($value) {
			case \ReflectionMethod::IS_PRIVATE :
				return 'private';
			case \ReflectionMethod::IS_PROTECTED :
				return 'protected';
			case \ReflectionMethod::IS_PUBLIC :
			default :
				return 'public';
		}
	}

	public static function convertTagAttr(string $name): string
	{
		return str_replace(['/', '\\', '::'], '_', $name);
	}

	public static function convertUnixPath(string $path = null): string
	{
		if (empty($path))
			return '';
		if (strpos($path, KE_DS_WIN) > 0)
			return str_replace('\\', '/', $path);
		return $path;
	}

	public function __construct(string $dir, string $sourceDir, string $routePath = null, \Closure $fn = null)
	{
		$this->docDir = $dir;
		$this->source = $sourceDir;
		if (!is_dir($sourceDir))
			throw new \Error("Source directory does not exist, or it is not a directory!");
		if (!empty($routePath))
			$this->setRoutePath($routePath);
		if (isset($fn))
			$fn->call($this);
	}

	public function isShowFile()
	{
		return $this->showFile;
	}

	public function setShowFile(bool $isShow)
	{
		if ($this->showFile !== $isShow) {
			$this->showFile = $isShow;
			Web::updateRoute($this->routePath, $this->getRoutes()[$this->routePath]);
		}
		return $this;
	}

	public function isGenerable()
	{
		return $this->generable;
	}

	public function setGenerable(bool $generable)
	{
		$this->generable = $generable;
		return $this;
	}

	public function setRoutePath(string $path)
	{
		$path = trim($path, KE_PATH_NOISE);
		if (!empty($path))
			$this->routePath = $path;
		return $this;
	}

	public function getRoutePath(): string
	{
		return $this->routePath;
	}

	public function getRoutes(): array
	{
		return [
			$this->routePath => [
				'class'    => DocController::class,
				'mappings' => [
					['({scope}(/{name})?)', '#show', ['scope' => $this->getRouteScopes(), 'name' => '.*']],
				],
			],
		];
	}

	public function getRouteScopes()
	{
		$scopes = $this->routeScopes;
		if (!$this->isShowFile()) {
			unset($scopes[DocMen::FILE]);
		}
		return implode('|', array_keys($scopes));
	}

	public function setScannerOptions(array $options)
	{
		$this->scannerOptions = array_merge($this->scannerOptions, $options);
		return $this;
	}

	public function getScanner(): SourceScanner
	{
		if (!isset($this->scanner)) {
			$this->scanner = new SourceScanner($this->source, $this->docDir);
			if (!empty($this->scannerOptions))
				$this->scanner->setOptions($this->scannerOptions);
		}
		return $this->scanner;
	}

	public function filterParams(array $params)
	{
		$data  = $params['data'] ?? [];
		$scope = $this->filterScope($data['scope'] ?? null);
		$name  = $data['name'] ?? null;
		if ($scope !== self::INDEX) {
			if ($scope === self::FILE)
				$name = ext($name, $params['format'] ?? null);
			$name = $this->filterName($scope, $name);
		}
		else {
			$name = null;
		}
		return [$scope, $name];
	}

	public function prepare()
	{
		if ($this->isPrepare)
			return $this;
//		if (empty($this->docDir) || !is_dir($this->docDir))
//			throw new \Error("Please input a valid directory!");
		$this->loadMainData();
		return $this;
	}

	public function getLoadMainFile()
	{
		return $this->docDir . DS . 'main.php';
	}

	public function getHashDataFile(string $hash)
	{
		return $this->docDir . DS . $hash . '.php';
	}

	public function getIndexDataFile()
	{
		return $this->docDir . DS . 'index.php';
	}

	public function getExportDir()
	{
		return $this->docDir;
	}

	public function getSourceDir()
	{
		return $this->source;
	}

	public function isLoadMainDataFile()
	{
		return $this->loadMainFile;
	}

	public function loadMainData()
	{
		if ($this->loadMainFile === false) {
			$file = $this->getLoadMainFile();
			if (is_file($file)) {
				$this->loadMainFile = $file;
				$data               = import($this->getLoadMainFile(), null, KE_IMPORT_ARRAY);
				foreach ($data as $key => $value)
					$this->{$key} = $value;
			}
		}
		return $this;
	}

	public function loadComment()
	{
		if ($this->loadComment === false) {
			$this->loadComment = true;
//			$this->comments    = import($this->getCommentFile(), null, KE_IMPORT_ARRAY);
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

	public function getAllMissedItems()
	{
		foreach ($this->missed as $name => $data) {
			yield $name => $data;
		}
	}

	public function getAllNamespacesCount()
	{
		return count($this->namespaces);
	}

	public function getAllClassesCount()
	{
		return count($this->classes);
	}

	public function getAllFunctionsCount()
	{
		return count($this->functions);
	}

	public function getAllFilesCount()
	{
		return count($this->files);
	}

	public function getAllMissedItemsCount()
	{
		return count($this->missed);
	}

	public function getNamespace($name)
	{
		if (!isset($this->namespaces[$name]))
			return false;
		$hash = $this->namespaces[$name];
		return $this->loadHashData($hash);
	}

	public function getClass($name)
	{
		if (!isset($this->classes[$name]))
			return false;
		$hash     = $this->classes[$name];
		$hashData = $this->loadHashData($hash);
		return $hashData['cls'][$name] ?? false;
	}

	public function getFile($name)
	{
		if (!isset($this->files[$name]))
			return false;
		$data = $this->files[$name];
		$dir  = $this->dirs[$data['dir']] ?? '';
		$path = $dir . '/' . $data['path'];
		$path = real_path($path);
		//
		$data['source'] = '';
		if ($path !== false && is_file($path)) {
			$data['source'] = file_get_contents($path);
		}
		return $data;
	}

	public function getMethodTableListOptions(): array
	{
		return [
			'columns' => [
				'isStatic' => ['label' => 'Static', 'options' => [1 => 'static', 0 => '']],
				'access'   => [
					'label'   => 'Access',
					'options' => [

					],
				],
				'name'     => ['label' => 'Method'],
			],
			'attr'    => ['class' => 'sortable striped'],
		];
	}

	public function getComment($docHash)
	{
//		if (empty($docHash))
//			return null;
//		if ($this->loadComment === false)
//			$this->loadComment();
//		return $this->comments[$docHash] ?? null;
	}

	public function setAsset(Asset $asset)
	{
		$this->asset = $asset;
		return $this;
	}

	public function getAsset()
	{
		if (!isset($this->asset))
			$this->asset = Asset::getInstance('docmen')->setLibraries(static::getStdAssetLibraries());
		return $this->asset;
	}
}