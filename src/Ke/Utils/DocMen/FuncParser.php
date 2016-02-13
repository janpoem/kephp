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

use Ke\Cli\Cmd\ScanSource;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionProperty;

class FuncParser
{

	/** @var ReflectionFunctionAbstract|ReflectionMethod|ReflectionFunction */
	private $reflection;

	protected $protectedParamNames = [
		'salt' => true,
	];

	public $name = '';

	public $fullName = '';

	public $class = false;

	public $namespace = '';

	public $access = 0;

	public $isStatic = false;

	public $isFinal = false;

	public $isAbstract = false;

	public $isInternal = false;

	public $isConstructor = false;

	public $isDestructor = false;

	public $doc = null;

	public $startLine = -1;

	public $endLine = -1;

	public $file = null;

	public $returnType = null;

	public $params = [];

	public static function autoParse(ReflectionFunctionAbstract $ref, SourceScanner $scanner): FuncParser
	{
		$parser = new static($ref);
		return $parser->parse($scanner);
	}

	public function __construct(ReflectionFunctionAbstract $ref)
	{
		$this->reflection = $ref;
	}

	public function export(): array
	{
		$data = [];
		foreach ($this as $field => $value) {
			if ($field === 'protectedParamNames' || $field === 'reflection')
				continue;
			$data[$field] = $value;
		}
		return $data;
	}

	public function parse(SourceScanner $scanner)
	{
		$ref = $this->reflection;

		$returnType = $ref->getReturnType();
		if (!empty($returnType))
			$returnType = $returnType->__toString();

		$this->name       = $ref->getName();
		$this->namespace  = $ref->getNamespaceName();
		$this->file       = $scanner->filterFile($ref->getFileName());
		$this->startLine  = $ref->getStartLine();
		$this->endLine    = $ref->getEndLine();
		$this->returnType = $returnType;
		if ($ref instanceof ReflectionMethod) {
			$access = ReflectionProperty::IS_PUBLIC;
			if ($ref->isPrivate())
				$access = ReflectionProperty::IS_PRIVATE;
			elseif ($ref->isProtected())
				$access = ReflectionProperty::IS_PROTECTED;
			$this->access        = $access;
			$this->class         = $ref->getDeclaringClass()->getName();
			$this->fullName      = $this->class . '::' . $this->name;
			$this->isStatic      = $ref->isStatic();
			$this->isFinal       = $ref->isFinal();
			$this->isAbstract    = $ref->isAbstract();
			$this->isInternal    = $ref->isInternal();
			$this->isConstructor = $ref->isConstructor();
			$this->isDestructor  = $ref->isDestructor();
			$this->doc           = DocCommentParser::autoParse($ref->getDocComment(), $scanner, DocMen::METHOD, $this->class, $this->name);
		} else {
			$this->access        = ReflectionProperty::IS_PUBLIC;
			$this->fullName      = (empty($this->namespace) ? '' : $this->namespace . '\\') . $this->name;
			$this->isStatic      = true;
			$this->isFinal       = true;
			$this->isAbstract    = false;
			$this->isInternal    = $ref->isInternal();
			$this->isConstructor = false;
			$this->isDestructor  = false;
			$this->doc           = DocCommentParser::autoParse($ref->getDocComment(), $scanner, DocMen::FUNC, null, $this->name);
		}
		$this->pushParams($scanner, $ref);

		if ($ref instanceof ReflectionMethod) {
			$scanner->addIndex(DocMen::METHOD, $this->fullName);
		}
		else {
			$scanner->addFunction($this);
		}
		return $this;
	}

	protected function pushParams(SourceScanner $scanner, ReflectionFunctionAbstract $ref)
	{
		$params = $ref->getParameters();
		if (empty($params))
			return $this;
		foreach ($params as $param) {
			$name  = $param->getName();
			$index = $param->getPosition();
			$class = $param->getClass();
			if ($class)
				$class = $class->getName();
			$type = $param->getType();
			if ($type)
				$type = $type->__toString();

			$isDefaultValue = $param->isDefaultValueAvailable();
			$defaultValue   = $isDefaultValue ? $param->getDefaultValue() : null;
			if (isset($this->protectedParamNames[$name])) {
				$newValue = null;
				settype($newValue, gettype($defaultValue));
				$defaultValue = $newValue;
			}
			$this->params[$index] = [
				'name'             => $name,
				'class'            => $class,
				'type'             => $type,
				'hasType'          => $param->hasType(),
				'isArray'          => $param->isArray(),
				'isCallable'       => $param->isCallable(),
				'allowsNull'       => $param->allowsNull(),
				'index'            => $index,
				'canBePassByValue' => $param->canBePassedByValue(),
				'isReference'      => $param->isPassedByReference(),
				'defaultValue'     => $defaultValue,
			];
		}

		return $this;
	}
}