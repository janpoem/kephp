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

use Throwable;
use ReflectionClass;
use ReflectionProperty;

class ClassParser
{

	protected $class = null;

	protected $scope = DocMen::CLS;

	protected $reflection = null;

	protected $scopeBasePositions = [
		DocMen::METHOD => 1,
		DocMen::PROP   => 2,
		DocMen::CONST  => 1000,
	];

	private $isExists = false;

	private $isLoad = false;

	public $type = 'class';

	public $name = '';

	public $shortName = '';

	public $namespace = null;

	public $parent = null;

	public $isFinal = false;

	public $isAbstract = false;

	public $isInternal = false;

	public $doc = null;

	public $startLine = -1;

	public $endLine = -1;

	public $file = null;

	public $traits = [];

	public $impls = [];

	public $packages = [];

	private $sort = [];

//	protected $props = [];
//
//	protected $methods = [];
//
//	protected $constants = [];

	public function __construct(string $class, string $type = null)
	{
		$this->class = $class;
		if (!empty($type))
			$this->type = $type;
	}

	public function load()
	{
		$classExists = 'class_exists';
		if ($this->type === 'interface')
			$classExists = 'interface_exists';
		elseif ($this->type === 'trait')
			$classExists = 'trait_exists';
//		else
//			$this->type = 'class'; // 这里是否需要修正一下class
		$this->isExists = $classExists($this->class, true);
		$this->isLoad   = true;
		return $this;
	}

	public function isExists()
	{
		if ($this->isLoad === false)
			$this->load();
		return $this->isExists;
	}

	public function getReflection()
	{
		if (!isset($this->reflection)) {
			$this->reflection = new ReflectionClass($this->class);
		}
		return $this->reflection;
	}

	public function parse(SourceScanner $scanner)
	{
		if (!$this->isExists())
			return $this;
		try {
			// 这里还是有可能会出错的，所以还是要try ... catch
			$ref = $this->getReflection();
		}
		catch (Throwable $thrown) {
			return $this;
		}
		// 基础信息解析
		$this->name       = $ref->getName();
		$this->shortName  = $ref->getShortName();
		$this->namespace  = $ref->getNamespaceName();
		$this->parent     = $this->getParentClass($ref);
		$this->doc        = DocCommentParser::autoParse($ref->getDocComment(), $scanner, $this->scope, $this->name,
			null);
		$this->file       = $scanner->filterPath($ref->getFileName());
		$this->startLine  = $ref->getStartLine();
		$this->endLine    = $ref->getEndLine();
		$this->isAbstract = $ref->isAbstract();
		$this->isFinal    = $ref->isFinal();
		$this->isInternal = $ref->isInternal();

		// 使用的Traits
		$traits = $ref->getTraits();
		foreach ($traits as $trait) {
			$name                = $trait->getName();
			$this->traits[$name] = $name;
		}

		// 实现的接口
		$impls = $ref->getInterfaces();
		foreach ($impls as $impl) {
			$name               = $impl->getName();
			$this->impls[$name] = $name;
		}

//		$this->pushConstants($scanner, $ref);
		$this->pushMethods($scanner, $ref);

//
//		$props = $ref->getProperties();
//		foreach ($props as $prop) {
//			$this->parseProp($scanner, $prop, $defaultProps);
//		}
//
//		$methods = $ref->getMethods();
//		foreach ($methods as $method) {
//			$parser                                 = new FuncParser($method);
//			$this->methods[$method->getShortName()] = $parser->parse($scanner);
//		}
//
//		$scanner->addClass($this->className, $this);

		return $this;
	}

	protected function getParentClass(ReflectionClass $ref)
	{
		$parent = $ref->getParentClass();
		if (!empty($this->parent))
			return $parent->getName();
		return false;
	}

	protected function getScopeBasePosition(string $scope): int
	{
		return $this->scopeBasePositions[$scope] ?? 10000;
	}

	protected function pushConstants(SourceScanner $scanner, ReflectionClass $ref)
	{
		$constants = $ref->getConstants();
		if (empty($constants))
			return $this;
		$key      = 'Constants';
		$scope    = DocMen::CONST;
		$position = $this->getScopeBasePosition($scope);
		if (!isset($this->packages[$key])) {
			$this->packages[$key] = [
				'scope' => $scope,
				'class' => $this->name,
				'items' => [],
			];
		}
		foreach ($constants as $name => $value) {
			$this->packages[$key]['items'][$name] = [
				'name'  => $name,
				'value' => $value,
				'type'  => gettype($value),
			];
		}
		ksort($this->packages[$key]['items']);
		$this->sort[$key] = $position;
		return $this;
	}

	protected function pushMethods(SourceScanner $scanner, ReflectionClass $ref)
	{
		$methods = $ref->getMethods();
		if (empty($methods))
			return $this;
		$scope    = DocMen::METHOD;
		$position = $this->getScopeBasePosition($scope);
		foreach ($methods as $name => $method) {
			FuncParser::autoParse($method, $scanner);
		}
		return $this;
	}

	public function parseProp(SourceScanner $scanner, ReflectionProperty $prop, array $defaultProps = null)
	{
		$name   = $prop->getName();
		$access = ReflectionProperty::IS_PUBLIC;
		if ($prop->isPrivate())
			$access = ReflectionProperty::IS_PRIVATE;
		elseif ($prop->isProtected())
			$access = ReflectionProperty::IS_PROTECTED;
		$doc = $prop->getDocComment();
		if (!empty($doc))
			$doc = htmlentities($doc);
		$this->props[$name] = [
			'sourceClass' => $prop->getDeclaringClass()->getName(),
			'isStatic'    => $prop->isStatic(),
			'access'      => $access,
			'isDefault'   => $prop->isDefault(),
			'doc'         => $scanner->filterComment($doc),
			'default'     => array_key_exists($name, $defaultProps) ? $defaultProps[$name] : null,
		];
	}

}