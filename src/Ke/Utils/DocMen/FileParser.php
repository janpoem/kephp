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


class FileParser
{

	protected $path = '';

	protected $content = false;

	protected $regexNamespace = '#^namespace[\s\t]+([a-zA-Z0-9\_\\\\]+)\;#im';

	protected $regexClass = '#^((?:(?:abstract|final)[\s\t]+)?class|interface|trait)[\s\t]+([a-zA-Z0-9\_]+)(?:[\s\t]+extends[\s\t]+([a-zA-Z0-9\_\\\\]+))?(?:[\s\t]+implements[\s\t]+([a-zA-Z0-9\_\\\\\,\s]+))?[\r\n]+\{#m';

	protected $regexFunction = '#^[\s\t]*function[\s\t]+([a-zA-Z0-9\_]+)\(#m';

	private $isParsed = false;

	private $namespace = null;

	private $classes = [];

	private $functions = [];

	private $constants = [];

	public function __construct(string $file)
	{
		$this->path = $file;
		if (!is_file($this->path))
			throw new \Error('Please input a valid file!');
	}

	public function getContent()
	{
		if ($this->content === false)
			$this->content = file_get_contents($this->path);
		return $this->content;
	}

	public function parse(SourceScanner $scanner)
	{
		$this->getContent();
		$this->parseNamespace($scanner);
		$this->parseClasses($scanner);
		$this->parseFunctions($scanner);
	}

	protected function parseNamespace(SourceScanner $scanner)
	{
		if (preg_match($this->regexNamespace, $this->content, $matches)) {
			$this->namespace = trim($matches[1], KE_PATH_NOISE);
			if (!empty($this->namespace))
				$scanner->addNamespace($this->namespace);
			else
				$this->namespace = null;
		}
		return $this;
	}

	protected function parseClasses(SourceScanner $scanner)
	{
		if (preg_match_all($this->regexClass, $this->content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$type = $match[1];
				$class = $match[2];
				if (!empty($this->namespace)) {
					if (!empty($class)) {
						$class = add_namespace($class, $this->namespace);
					}
				}
				$clsParser = new ClassParser($class, $type);
				$clsParser->parse($scanner);
			}
		}
		return $this;
	}

	protected function parseImpls(string $impl = null)
	{
		if (empty($impl))
			return null;
		$result = [];
		$split = explode(',', $impl);
		foreach ($split as $item) {
			$item = trim($item);
			if (!empty($item)) {
				if (!empty($this->namespace)) {
					$item = add_namespace($item, $this->namespace);
				}
				$result[] = $item;
			}
		}
		if (empty($result))
			return null;
		return $result;
	}

	protected function parseFunctions(SourceScanner $scanner)
	{
		if (preg_match_all($this->regexFunction, $this->content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$fn = trim($match[1]);
				if (!function_exists($fn)) {
					require $this->path;
				}
				if (!isset($this->functions[$fn])) {
					$parser = new FuncParser(new \ReflectionFunction($fn));
					$this->functions[$fn] = $parser->parse($scanner);
				}
			}
		}
		return $this;
	}

	protected function parseFunction(\ReflectionFunction $ref)
	{

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

		return [
			'name'      => $ref->getName(),
			'namespace' => $ref->getNamespaceName(),
			'file'      => $this->path,
			'args'      => $args,
			'startLine' => $ref->getStartLine(),
			'endLine'   => $ref->getEndLine(),
			'doc'       => htmlentities($ref->getDocComment()),
		];
	}

	public function getNamespace()
	{
		return $this->namespace;
	}

	public function getPath()
	{
		return $this->path;
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