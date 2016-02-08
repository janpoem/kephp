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


use Ke\Web\Html;
use Ke\Web\Web;

class DocHtml extends Html
{

	protected $doc = null;

	protected $web = null;

	public function setDoc(DocMen $docMen)
	{
		$this->doc = $docMen;
		return $this;
	}

	public function getDoc(): DocMen
	{
		return $this->doc;
	}

	public function getWeb()
	{
		if (!isset($this->web))
			$this->web = Web::getWeb();
		return $this->web;
	}

//	protected $aliasTags = [
//		'buttons'      => 'div',
//		'inline'       => 'span',
//		'inline-group' => 'div',
//		'message'      => 'div',
//		'pagination'   => 'div',
//		'input-wrap'   => 'div',
//		'var'          => 'span',
//		'var-type'     => 'span',
//		'code-name'    => 'div',
//		'class-misc'   => 'div',
//		'var-name'     => 'span',
//	];
//
//	protected $baseClasses = [
//		'var-name'                 => 'var-name',
//		'var'                      => 'var',
//		'var:NULL'                 => 'var null',
//		'var:boolean'              => 'var bool',
//		'var:integer'              => 'var int',
//		'var:string'               => 'var str',
//		'var:double'               => 'var float',
//		'var:array'                => 'var array',
//		'var:object'               => 'var object',
//		'var:resource'             => 'var res',
//		'var-type'                 => 'var-type',
//		'var-type:NULL'            => 'var-type null',
//		'var-type:boolean'         => 'var-type bool',
//		'var-type:integer'         => 'var-type int',
//		'var-type:int'             => 'var-type int',
//		'var-type:string'          => 'var-type str',
//		'var-type:double'          => 'var-type float',
//		'var-type:array'           => 'var-type array',
//		'var-type:object'          => 'var-type object',
//		'var-type:resource'        => 'var-type res',
//		'code-name'                => 'code-name',
//		'code-name:namespace'      => 'code-name ns',
//		'code-name:class'          => 'code-name cls',
//		'code-name:final class'    => 'code-name cls',
//		'code-name:abstract class' => 'code-name cls',
//		'code-name:interface'      => 'code-name impl',
//		'code-name:trait'          => 'code-name trait',
//		'code-name:fn'             => 'code-name fn',
//		'code-name:file'           => 'code-name file',
//		'class-misc:props'         => 'ui green label',
//		'class-misc:methods'       => 'ui teal label',
//		'class-misc:impls'         => 'ui brown label',
//		'class-misc:traits'        => 'ui pink label',
//		'class-misc:nothing'       => 'ui label',
//		//		'code-label:props'         => 'ui teal label',
//		//		'class-misc:methods'       => 'ui blue label',
//		'code-label:interface'     => 'ui tag brown label',
//		'code-label:trait'         => 'ui tag pink label',
//		'code-label:namespace'     => 'ui tag blue label',
//		'code-label:nothing'       => 'ui label',
//	];
//
//	public function setDocLoader(DocLoader $docLoader)
//	{
//		$this->docLoader = $docLoader;
//		return $this;
//	}
//
//	public function getDocLoader(): DocLoader
//	{
//		return $this->docLoader;
//	}
//
//	public function filterCodeNameType(string $type = null): string
//	{
//		switch (strtolower($type)) {
//			case 'cls' :
//			case 'class' :
//				return 'class';
//			case 'ns' :
//			case 'namespace' :
//				return 'namespace';
//			case 'fn' :
//			case 'func' :
//			case 'function' :
//				return 'function';
//			case 'impl' :
//			case 'if' :
//			case 'interface' :
//				return 'interface';
//			case 'abstract class' :
//			case 'final class' :
//			case 'file' :
//				return $type;
//			case 'trait' :
//				return 'trait';
//			default :
//				return 'class';
//		}
//	}
//
//	public function mkFunctionArgs(array $args)
//	{
//		$html = [];
//		foreach ($args as $arg) {
//			$name = $this->mkTag('var-name', '$' . $arg['name']);
//			$defaultValue = $arg['defaultValue'];
//			if ($arg['isReference'])
//				$name = '<i>&</i>' . $name;
//
//			$valueType = gettype($defaultValue);
//			if ($defaultValue === true) {
//				$defaultValue = 'true';
//			}
//			elseif ($defaultValue === false) {
//				$defaultValue = 'false';
//			}
//			elseif ($valueType === KE_STR) {
//				$defaultValue = "'" . htmlentities($defaultValue) . "'";
//			}
//			if ($arg['hasType'])
//				$name = $this->mkTag('var-type', $arg['type'], ['type' => $arg['type']]) . ' ' . $name;
//
//			if ($defaultValue === null && $arg['allowsNull'])
//				$name .= '<span class="func-args-equal">=</span>' . $this->mkTag('var', 'null', ['type' => $valueType]);
//			elseif ($defaultValue !== null) {
//				if (is_array($defaultValue))
//					$defaultValue = '[]';
//				$name .= '<span class="func-args-equal">=</span>' .
//				         $this->mkTag('var', (string)$defaultValue, ['type' => $valueType]);
//			}
//
//			$html[] = $name;
//		}
//
//		return $this->mkTag('span', '(' . implode('<span class="func-args-comma">,</span>', $html) . ')', 'func-args');
//	}
//
//	public function mkCodeName(string $name, string $type = 'class', $attr = null): string
//	{
//		if (!is_array($attr))
//			$attr = $this->attr2array($attr);
//		$type = $this->filterCodeNameType($type);
//		$attr['type'] = $type;
//		if (!empty($attr['parent'])) {
//			if (is_string($attr['parent'])) {
//				$name .= ' <small>extends ' .
//				         $this->mkLink($attr['parent'], $this->mkCodeNameUri($attr['parent'], 'class')) .
//				         '</small>';
//			}
//			elseif (is_array($attr['parent'])) {
//				$parent = array_shift($attr['parent']);
//				if (!empty($parent)) {
//					$name .= ' <small>extends ' .
//					         $this->mkLink($parent, $this->mkCodeNameUri($parent, 'class', $attr['parent'])) .
//					         '</small>';
//				}
//			}
//
//		}
////		if (!empty($attr['parent'])) {
////			$name .= ' <small>extends ' . $attr['parent'] . '</small>';
////		}
//		if ($type === 'function')
//			$name .= $this->mkFunctionArgs($attr['args'] ?? []);
//		unset($attr['parent'], $attr['args']);
//		return $this->mkTag('code-name', $this->mkTag('small', $type) . ' ' . $name, $attr);
//	}
//
//	public function codeName(string $name = null, string $type = 'class', $attr = null)
//	{
//		print $this->mkCodeName($name, $type, $attr);
//		return $this;
//	}
//
//	public function mkCodeNameUri(string $name, string $type = 'class', $query = null)
//	{
//		$web = Web::getWeb();
//		$doc = $this->getDocLoader();
//		$uri = '';
//		if ($type === 'file') {
//			$uri = 'file/';
//			$uri .= $doc->convertFileNameToUri($name);
//		}
//		else {
//			if ($type === 'function')
//				$uri = 'fn/';
//			elseif ($type === 'namespace')
//				$uri = 'ns/';
//			else
//				$uri = 'cls/';
//			$uri .= $doc->convertClassToUri($name);
//		}
//		return $web->controllerOf($uri, $query);
//	}
//
//	public function mkCodeNameLink(string $name, string $type = 'class', $attr = null): string
//	{
//		if (!is_array($attr))
//			$attr = $this->attr2array($attr);
//		$type = $this->filterCodeNameType($type);
//		$attr['type'] = $type;
//		$uri = $this->mkCodeNameUri($name, $type, $attr['query'] ?? null);
//		return $this->mkCodeName($this->mkLink($name, $uri), $type, $attr);
//	}
//
//	public function codeNameLink(string $name, string $type = 'class', $attr = null)
//	{
//		print $this->mkCodeNameLink($name, $type, $attr);
//		return $this;
//	}
//
//	public function getTypeShortName($type)
//	{
//		switch (strtolower($type)) {
//			case 'cls' :
//			case 'class' :
//				return 'cls';
//			case 'ns' :
//			case 'namespace' :
//				return 'ns';
//			case 'fn' :
//			case 'func' :
//			case 'function' :
//				return 'fn';
//			case 'impl' :
//			case 'if' :
//			case 'interface' :
//				return 'impl';
//			case 'abstract class' :
//				return 'abs cls';
//			case 'final class' :
//				return 'final cls';
//			case 'file' :
//				return 'file';
//			case 'trait' :
//				return 'trait';
//			default :
//				return 'cls';
//		}
//	}
//
//	public function mkCodeLabel(string $name = null, string $type = 'class', $attr = null)
//	{
//		if (!is_array($attr))
//			$attr = $this->attr2array($attr);
//		$type = $this->filterCodeNameType($type);
//
//		$detail = '<div class="detail">' . $type . '</div>';
//		$uri = $this->mkCodeNameUri($name ?? '', $type, $attr['query'] ?? null);
//		if (empty($name)) {
//			if ($type === 'namespace') {
//				$name = $this->getDocLoader()->filterNamespace($name);
//			}
//		}
//		return $this->mkLink($name . $detail, $uri, $this->getBaseClass('code-label', $type));
//	}
//
//	public function codeLabel(string $name = null, string $type = 'class', $attr = null)
//	{
//		print  $this->mkCodeLabel($name, $type, $attr);
//		return $this;
//	}
//
//	public function getClassMiscFields() :array
//	{
//		return [
//			'methods' => 'Methods', 'props' => 'Properties', 'traits' => 'Traits', 'impls' => 'Interfaces',
//		];
//	}
//
//	public function mkClassMisc(array $clsData, $attr = null): string
//	{
//		if (!is_array($attr))
//			$attr = $this->attr2array($attr);
//		$misc = '';
//		if (!empty($clsData['file'])) {
//			$misc .= $this->mkTag('address', $clsData['file'], 'source-file');
//		}
//		elseif ($clsData['isInternal']) {
//			$misc .= $this->mkTag('address', 'PHP Internal', 'source-file');
//		}
//		foreach ($this->getClassMiscFields() as $field => $name) {
//			if (empty($clsData[$field]) || !is_array($clsData[$field])) {
//				$count = 0;
//				$attr['type'] = 'nothing';
//				continue;
//			}
//			else {
//				$count = count($clsData[$field]);
//				$attr['type'] = $field;
//			}
//			$inner = $name . ' <div class="detail">' . $count . '</div>';
//			$misc .= $this->mkTag('class-misc', $inner, $attr);
//		}
//		return $this->mkTag('div', $misc, 'class-misc');
//	}
//
//	public function classMisc(array $clsData, $attr = null)
//	{
//		print $this->mkClassMisc($clsData, $attr);
//		return $this;
//	}
//
//	public function getReturnQuery(string $name = null, string $type = null, bool $withQuery = true)
//	{
//		if ($withQuery)
//			return ['query' => ['return' => $this->filterCodeNameType($type) . ':' . $name]];
//		else
//			return ['return' => $this->filterCodeNameType($type) . ':' . $name];
//	}
//
//	public function parseReturnStr(string $str)
//	{
//		$split = explode(':', $str);
//		$type = $this->filterCodeNameType($split[0]);
//		$name = $split[1] ?? null;
//		return [$type, $name];
//	}
//
//	public function mkReturnLink($attr = null)
//	{
//		$str = $_GET['return'] ?? '';
//		if (empty($str))
//			return '';
//		list($type, $name) = $this->parseReturnStr($str);
//		if (empty($name))
//			return '';
//		if (!is_array($attr))
//			$attr = $this->attr2array($attr);
//		$type = $this->filterCodeNameType($type);
//
//		$detail = '<span class="detail">' . $type . '</span>';
//		$uri = $this->mkCodeNameUri($name ?? '', $type, $attr['query'] ?? null);
//		if (empty($name)) {
//			if ($type === 'namespace') {
//				$name = $this->getDocLoader()->filterNamespace($name);
//			}
//		}
//		$this->addClass($attr, 'ui red top right attached label');
//		return $this->mkLink('<i class="arrow right icon"></i>' . $name . $detail, $uri, $attr);
//	}
//
//	public function returnLink($attr = null)
//	{
//		print $this->mkReturnLink($attr);
//		return $this;
//	}
}