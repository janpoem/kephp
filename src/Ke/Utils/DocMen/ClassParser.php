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

use ReflectionProperty;

class ClassParser
{

	public $type = 'class';

	protected $class = null;

	protected $reflection = null;

	public $namespace = null;

	public $className = null;

	public $parent = null;

	public $isFinal = false;

	public $isAbstract = false;

	public $isInternal = false;

	public $doc = null;

	public $startLine = -1;

	public $endLine = -1;

	public $file = null;

	public $props = [];

	public $methods = [];

	public $constants = [];

	public $traits = [];

	public $impls = [];

	public function __construct(string $class, string $type = null)
	{
		$this->class = $class;
		if (!empty($type))
			$this->type = $type;
	}

	public function parse(SourceScanner $scanner)
	{
		switch ($this->type) {
			case 'interface' :
				if (!interface_exists($this->class, true))
					return $this;
				break;
			case 'trait' :
				if (!trait_exists($this->class, true))
					return $this;
				break;
			default :
				if (!class_exists($this->class, true))
					return $this;
		}

		$ref = $this->reflection = new \ReflectionClass($this->class);
		$this->className = $ref->getName();
		$this->namespace = $ref->getNamespaceName();
		$this->parent = $ref->getParentClass();
		if (!empty($this->parent))
			$this->parent = $this->parent->getName();

		$this->doc = $scanner->filterComment($ref->getDocComment());

		$this->file = $scanner->filterPath($ref->getFileName());
		$this->startLine = $ref->getStartLine();
		$this->endLine = $ref->getEndLine();
		$this->isAbstract = $ref->isAbstract();
		$this->isFinal = $ref->isFinal();
		$this->isInternal = $ref->isInternal();

		$defaultProps = [];

		$traits = $ref->getTraits();
		foreach ($traits as $trait) {
			$traitName = $trait->getName();
			$this->traits[$traitName] = $traitName;
		}

		$impls = $ref->getInterfaces();
		foreach ($impls as $impl) {
			$implName = $impl->getName();
			$this->impls[$implName] = $implName;
		}

		$constants = $ref->getConstants();
		foreach ($constants as $name => $value) {
			$this->constants[$name] = [
				'value' => $value,
				'type'  => gettype($value),
			];
		}

		$props = $ref->getProperties();
		foreach ($props as $prop) {
			$this->parseProp($scanner, $prop, $defaultProps);
		}

		$methods = $ref->getMethods();
		foreach ($methods as $method) {
			$parser = new FuncParser($method);
			$this->methods[$method->getShortName()] = $parser->parse($scanner);
		}

		$scanner->addClass($this->className, $this);

		return $this;
	}

	public function parseProp(SourceScanner $scanner, ReflectionProperty $prop, array $defaultProps = null)
	{
		$name = $prop->getName();
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