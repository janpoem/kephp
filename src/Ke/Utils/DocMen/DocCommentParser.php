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

/**
 * 注释文档解析器
 *
 * @package Ke\Utils\DocMen
 */
class DocCommentParser
{

	const NOISE = '/* ';

	const REGEX_SPEC_LINK = '#\{([^\{\}\r\n]+)\}#';

	private $comment = '';

	private $scope = null;

	private $class = null;

	private $item = null;

	protected $allowMultiFields = [
		'link'     => true,
		'param'    => true,
		'property' => true,
	];

	public $header = '';

	public $detail = '';

	public $fields = [];

	public static function autoParse(
		$comment,
		SourceScanner $scanner,
		string $scope,
		string $class = null,
		string $item = null)
	{
		if (empty($comment) || !is_string($comment))
			return false;
		/** @var DocCommentParser $parser */
		$parser = new static($comment);
		$parser->setHelperData($scope, $class, $item)->parse($scanner);
		return $parser->export();
	}

	/**
	 * 注释文档解析器构造函数
	 *
	 * @param string|null $comment 注释文本的内容
	 */
	public function __construct(string $comment)
	{
		$this->comment = $this->stripNoise($comment);
	}

	/**
	 * 注册辅助信息，用于帮助更好的分析代码注释文本的信息，主要用于生成特定的链接。
	 *
	 * @param string $scope
	 * @param string $class
	 * @param string $item
	 * @return $this
	 */
	public function setHelperData(string $scope, string $class = null, string $item = null)
	{
		$this->scope = DocMen::filterScope($scope);
		$this->class = $class;
		$this->item  = $item;
		return $this;
	}

	public function parse(SourceScanner $scanner)
	{
		// 在解析字段和分拆注释之前，就可以提前将{xxx}做替换的准备，
		// 这里并不直接将这里的内容转换为markdown的[xxx](xxx)的模式，而是先将{}
		$this->fields = $this->parseFields();
		list($this->header, $this->detail) = $this->splitComment();
		return $this;
	}

	/**
	 * 是否允许多重字段
	 *
	 * @param string $field 字段名称
	 * @return bool 是否允许多重字段
	 */
	public function isAllowMulti(string $field): bool
	{
		return $this->allowMultiFields[$field] ?? false;
	}

	/**
	 * 清理注释的噪音，主要是去掉各种的`/`、`*`，并确保返回的注释不包含多于的空格、回车符等。
	 *
	 * @param string $comment 注释的文本内容
	 * @return string 返回清理过后的注释文本
	 */
	public function stripNoise(string $comment): string
	{
		if (empty($comment))
			return '';
		return preg_replace('#^(\*{1,}\s{0,1}|[\t\s]+\s{0,1}\*\s{0,1})#mi', '', trim(trim($comment, self::NOISE)));
	}

	public function parseFields(): array
	{
		if (empty($this->comment))
			return [];
		$fields        = [];
		$regex         = '#^\@([^\s]+)(?:[\s\t]+([^\s]+)(.*([\r\n]+(?!^\@)\s*.*)*))?#mi';
		$this->comment = preg_replace_callback($regex, function ($matches) use (&$fields) {
			$data  = $this->filterField($matches);
			$field = array_shift($data);
			if ($this->isAllowMulti($field)) {
				$fields[$field][] = $data;
			}
			else {
				$fields[$field] = $data;
			}
			return '';
		}, $this->comment);
		return $fields;
	}

	public function filterField(array $matches)
	{
		foreach ($matches as $index => $match) {
			$matches[$index] = trim($matches[$index]);
		}
		$data = [$matches[1], $matches[2]];
		if (!empty($matches[3])) {
			$regex = '#^([^\s]+)[\t\s]+(.*(?:[\r\n].*)*)#mi';
			if (preg_match($regex, $matches[3], $match)) {
				$data[]   = $match[1];
				$match[2] = preg_replace('#^([\t\s]+)#mi', '', $match[2]);
//				$match[2] = preg_replace('#([\r\n])#mi', '', $match[2]);
				$data[] = $match[2];
			}
			else {
				$data[] = $matches[3];
				$data[] = null;
			}
		}
		else {
			$data[] = null;
			$data[] = null;
		}
		return $data;
	}

	public function splitComment()
	{
		$header = '';
		$detail = preg_replace_callback('#^([^\r\n]+)#i', function (array $matches) use (&$header) {
			$header = $matches[1];
			return '';
		}, $this->comment);
		return [$header, trim($detail)];
	}

	public function export()
	{
		$data           = $this->fields;
		$data['header'] = $this->header;
		$data['detail'] = $this->detail;
		return $data;
	}
}