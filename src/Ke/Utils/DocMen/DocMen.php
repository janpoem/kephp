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

use Ke\Web\Web;

/**
 * DocMen，主要为文档数据的加载器，并且辅助查询
 *
 * @package Ke\Utils\DocMen
 */
class DocMen
{

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

	private $isPrepare = false;

	private $routePath = 'docmen';

	private $docDir = '';

	private $loadMainFile = false;

	private $loadComment = false;

	protected $routeScopes = [
		self::CLS  => self::CLS,
		self::NS   => self::NS,
		self::FUNC => self::FUNC,
		self::FILE => self::FILE,
	];

	protected $source;

	protected $export;

	protected $files = [];

	protected $namespaces = [];

	protected $classes = [];

	protected $functions = [];

	protected $data = [];

	protected $comments = [];

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
			Web::removeRoutes($path);
			return true;
		}
		return false;
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
		throw new \Error('Does not found DocMen instance register by ' . $path . '!');
	}

	public function __construct(string $dir, string $sourceDir, string $routePath = null)
	{
		$this->docDir = $dir;
		$this->source = real_dir($sourceDir);
		if (empty($this->source))
			throw new \Error("Source directory does not exist, or it is not a directory!");
		if (!empty($routePath))
			$this->setRoutePath($routePath);
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
		return implode('|', array_keys($this->routeScopes));
	}

	public function filterParams(array $params)
	{
		$data = $params['data'] ?? [];
		$scope = $this->filterScope($data['scope'] ?? null);
		$name = $data['name'] ?? null;
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

	public function getCommentFile()
	{
		return $this->docDir . DS . 'comment.php';
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
				$data = import($this->getLoadMainFile(), null, KE_IMPORT_ARRAY);
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
			$this->comments = import($this->getCommentFile(), null, KE_IMPORT_ARRAY);
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

	public function filterScope($scope)
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

	public function filterName($scope, string $name)
	{
		switch ($this->filterScope($scope)) {
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

	public function getShowName($scope, string $name)
	{
		switch ($this->filterScope($scope)) {
			case self::NS :
				if (empty($name))
					return '&lt;Global&gt;';
			case self::FILE :
				if (empty($name))
					return '&lt;PHP Internal&gt;';
			case self::FUNC :

			default :

		}
		return $name;
	}

	public function mkScopePath($scope, string $name)
	{
		switch ($this->filterScope($scope)) {
			case self::NS :
				return 'namespace/' . $this->convertClassToUri($name);
			case self::FILE :
				return 'file/' . $name;
			case self::FUNC :
				return 'function/' . $this->convertClassToUri($name);
			default :
				return self::CLS . '/' . $this->convertClassToUri($name);
		}
	}

	public function convertClassToUri(string $name)
	{
		return str_replace('\\', '/', $name);
	}

	public function revertClass(string $name)
	{
		return str_replace('/', '\\', $name);
	}

	public function getNamespace($name)
	{
		if (!isset($this->namespaces[$name]))
			return false;
		$hash = $this->namespaces[$name]['hash'];
		return $this->loadHashData($hash);
	}

	public function getClass($name)
	{
		if (!isset($this->classes[$name]))
			return false;
		$hash = $this->classes[$name];
		$hashData = $this->loadHashData($hash);
		return $hashData['cls'][$name] ?? false;
	}

	public function getFile($name)
	{
		if (!isset($this->files[$name]))
			return false;
		$data = $this->files[$name];
		$path = $data['dir'] . '/' . $data['path'];
		$path = realpath($path);
		$data['source'] = '';
		if ($path !== false && is_file($path)) {
			$data['source'] = file_get_contents($path);
		}
		return $data;
	}

	public function packetClassData(array $data)
	{
		$className = $data['className'];
		$packages = [];
		$sort = [];
		$position = 1;
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
				$sort[$key] = $position;
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
				$sort[$key] = $position;
				$position *= 10;
			}
			$packages[$key]['items'][$name] = $prop;
			$packages[$key]['count'] += 1;
		}
		if (!empty($data['constants'])) {
			$key = 'Constants';
			$position = 1000;
			$packages[$key] = [
				'class' => $data['className'],
				'type'  => self::CONST,
				'items' => $data['constants'],
				'count' => count($data['constants']),
			];
			$sort[$key] = $position;
		}
		array_multisort($sort, SORT_ASC, $packages);
		return $packages;
	}

	public function getPacketName($name)
	{
		switch ($name) {
			case 'methods_static' :
				return 'Static Methods';
			case 'methods' :
				return 'Methods';
			case 'props_static' :
				return 'Static Properties';
			case 'props' :
				return 'Properties';
		}
		return $name;
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

	public function filterAccess($value)
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

	public function convertTagAttr(string $name)
	{
		return str_replace(['/', '\\', '::'], '_', $name);
	}

	public function getComment($docHash)
	{
		if (empty($docHash))
			return null;
		if ($this->loadComment === false)
			$this->loadComment();
		return $this->comments[$docHash] ?? null;
	}
}