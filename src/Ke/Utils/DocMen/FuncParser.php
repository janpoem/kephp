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

use ReflectionMethod;
use ReflectionFunctionAbstract;
use ReflectionProperty;

class FuncParser
{

	private $func;

	public function __construct(ReflectionFunctionAbstract $func)
	{
		$this->func = $func;
	}

	public function parse(SourceScanner $scanner)
	{
		$ref = $this->func;
		$args = [];
		$params = $ref->getParameters();
		foreach ($params as $param) {
			$name = $param->getName();
			$index = $param->getPosition();
			$class = $param->getClass();
			if ($class)
				$class = $class->getName();
			$type = $param->getType();
			if ($type)
				$type = $type->__toString();

			$isDefaultValue = $param->isDefaultValueAvailable();
			$defaultValue = $isDefaultValue ? $param->getDefaultValue() : null;
//			$isDefaultConst = $param->isDefaultValueConstant();
//			$defaultConst = $isDefaultConst ? $param->getDefaultValueConstantName() : null;

			$args[$index] = [
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
				//				'defaultConst'     => $defaultConst,
			];
		}

		$returnType = $ref->getReturnType();
		if (!empty($returnType))
			$returnType = $returnType->__toString();

		$data = [
			'name'          => $ref->getName(),
			'namespace'     => $ref->getNamespaceName(),
			'sourceClass'   => null,
			'isStatic'      => true,
			'isFinal'       => true,
			'isAbstract'    => false,
			'isConstructor' => false,
			'isDestructor'  => false,
			'isInternal'    => $ref->isInternal(),
			'access'        => \ReflectionProperty::IS_PUBLIC,
			'file'          => $scanner->filterPath($ref->getFileName()),
			'args'          => $args,
			'startLine'     => $ref->getStartLine(),
			'endLine'       => $ref->getEndLine(),
			'doc'           => htmlentities($ref->getDocComment()),
			'returnType'    => $returnType,
		];

		if ($ref instanceof ReflectionMethod) {
			$data['sourceClass'] = $ref->getDeclaringClass()->getName();
			$access = ReflectionProperty::IS_PUBLIC;
			if ($ref->isPrivate())
				$access = ReflectionProperty::IS_PRIVATE;
			elseif ($ref->isProtected())
				$access = ReflectionProperty::IS_PROTECTED;
			$data['access'] = $access;
			$data['isStatic'] = $ref->isStatic();
			$data['isFinal'] = $ref->isFinal();
			$data['isAbstract'] = $ref->isAbstract();
			$data['isConstructor'] = $ref->isConstructor();
			$data['isDestructor'] = $ref->isDestructor();
		}

		return $data;
	}
}