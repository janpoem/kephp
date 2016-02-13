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

	public $classTableList = 'ui compact table celled';

	public $tagScopeName   = 'span';
	public $classScopeName = 'scope-name';

	public $tagClassMisc                  = 'div';
	public $classClassMiscMethodsCount    = 'ui label basic teal';
	public $classClassMiscPropertiesCount = 'ui label basic green';
	public $classClassMiscTraitsCount     = 'ui label basic brown';
	public $classClassMiscImplsCount      = 'ui label basic pink';
	public $classClassMiscConstantsCount  = 'ui label basic orange';

	public $tagClassMiscWrap   = 'div';
	public $classClassMiscWrap = 'ui labels';

	public $tagVar       = 'var';
	public $tagVarName   = 'span';
	public $tagVarType   = 'span';
	public $classVar     = 'var';
	public $classVarName = 'var-name';
	public $classVarType = 'var-type';

	public $tagScopeLabelNamespace   = 'a';
	public $tagScopeLabelInterface   = 'a';
	public $tagScopeLabelTrait       = 'a';
	public $classScopeLabelNamespace = 'ui tag label scope-name blue';
	public $classScopeLabelInterface = 'ui tag label scope-name brown';
	public $classScopeLabelTrait     = 'ui tag label scope-name pink';

	public $classMessage        = 'ui message';
	public $classMessageWarning = 'ui message warning';

	public $equalSpan = '<span class="func-args-equal">=</span>';

	public $tagDocComment   = 'div';
	public $classDocComment = 'doc-comment';


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

	public function scopeUri($scope, string $name, $query = null)
	{
		return $this->getWeb()->controllerLink($this->getDoc()->mkScopePath($scope, $name), $query);
	}

	public function scopeLink($scope, string $name, $query = null, $attr = null)
	{
		return $this->link($this->getDoc()->getShowName($scope, $name), $this->scopeUri($scope, $name, $query), $attr);
	}

	public function scopeName($scope, string $name, $attr = null, $tag = 'scope-name')
	{
		$doc   = $this->getDoc();
		$scope = $doc->filterScope($scope);
		$name  = $doc->getShowName($scope, $name);
		if (!is_array($attr))
			$attr = $this->attr2array($attr);

		$content = "<small>{$scope} </small>{$name}";

		if ($scope === DocMen::CLS) {
			if (!empty($attr['parent'])) {
				if (!empty($attr['parentLink'])) {
					$link = $this->scopeLink('class', $attr['parent'], $attr['parentLink']);
					$content .= "<small> extends {$link}</small>";
				} else {
					$content .= "<small> extends {$attr['parent']}</small>";
				}
				unset($attr['parent'], $attr['parentLink']);
			}
		} elseif ($scope === DocMen::METHOD) {
			$prefix = ($attr['data-access'] ?? 'public') . ' ';
			if ($attr['data-static'] === '1')
				$prefix = $prefix . 'static ';
			$content = "<small>{$prefix}</small>{$name}";
		} elseif ($scope === DocMen::PROP) {
			$prefix = ($attr['data-access'] ?? 'public') . ' ';
			if ($attr['data-static'] === '1')
				$prefix = $prefix . 'static ';
			$name    = '$' . $name;
			$content = "<small>{$prefix}</small>{$name}";
		}

		return $this->tag($tag, $content, $attr);
	}

	public function scopeNameLink($scope, string $name, $attr = null)
	{
		return $this->scopeName($scope, $this->scopeLink($scope, $name), $attr);
	}

	public function scopeLabel($scope, string $name, $attr = null)
	{
		$doc   = $this->getDoc();
		$scope = $doc->filterScope($scope);
		$name  = $doc->getShowName($scope, $name);
		if (!is_array($attr))
			$attr = $this->attr2array($attr);

		$content = $name;
		return $this->tag('scope-label-' . $scope, $content, $attr);
	}

	public function scopeLabelLink($scope, string $name, $query = null, $attr = null)
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$attr['href'] = $this->scopeUri($scope, $name, $query);
		return $this->scopeLabel($scope, $name, $attr);
//		return $this->link(, $this->scopeUri($scope, $name, $query));
	}

	public function getClassMiscFields() :array
	{
		return [
			'methodsCount'    => 'Methods',
			'propertiesCount' => 'Properties',
			'implsCount'      => 'Interfaces',
			'constantsCount'  => 'Constants',
			'traitsCount'     => 'Traits',
		];
	}

	public function fileUri(string $file, int $startLine = null, int $endLine = null, $query = null)
	{
		$path = $this->getDoc()->mkScopePath('file', $file);
		if (isset($startLine)) {
			$line = $startLine;
			if (isset($endLine))
				$line .= '-' . $endLine;
			$path .= '?line=' . $startLine . '#source.' . $line;
		}
		return $this->getWeb()->controllerLink($path, $query);
	}

	public function classMisc(array $data, $attr = null): string
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$misc = '';
		$address = '';
		if (!empty($data['file'])) {
			$file     = $this->getDoc()->getShowName('file', $data['file']);
			$fileLink = $this->fileUri($data['file'], $data['startLine'] ?? null, $data['endLine'] ?? null);
			if (isset($data['startLine']) && isset($data['endLine'])) {
				$file .= " <small>[{$data['startLine']}:{$data['endLine']}]</small>";
			}
			$address = $this->tag('address', $this->link($file, $fileLink), 'source-file');
		} elseif ($data['isInternal']) {
			$address = $this->tag('address', $this->scopeLink('file', ''), 'source-file');
		}

		$items = '';
		foreach ($this->getClassMiscFields() as $field => $name) {
			if (empty($data[$field])) {
				continue;
			} else {
				$count = $data[$field];
			}
			$inner = $name . ' <div class="detail">' . $count . '</div>';
			$items .= $this->tag('ClassMisc:' . $field, $inner, $attr);
		}
		$misc .= $this->tag('ClassMiscWrap', $items, $attr);

		if (!empty($data['doc']))
			$misc .= $this->docComment(DocMen::CLS, $data['doc']);

		return $misc . $address;
	}

	public function functionMisc(array $data, $attr = null): string
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$misc = '';
		if (!empty($data['file'])) {
			$file     = $this->getDoc()->getShowName('file', $data['file']);
			$fileLink = $this->fileUri($data['file'], $data['startLine'] ?? null, $data['endLine'] ?? null);
			if (isset($data['startLine']) && isset($data['endLine'])) {
				$file .= " <small>[{$data['startLine']}:{$data['endLine']}]</small>";
			}
			$misc .= $this->tag('address', $this->link($file, $fileLink), 'source-file');
		} elseif (isset($data['isInternal']) && $data['isInternal']) {
			$misc .= $this->tag('address', $this->scopeLink('file', ''), 'source-file');
		}
		$attr['class'] = 'misc';

		$comment = '';
//		if (!empty($data['doc']))
//			$comment = $this->commentDoc($this->getDoc()->getComment($data['doc']));

		return $this->tag('div', $misc, $attr) . $comment;
	}

	public function docComment(string $scope, $comment)
	{
		$doc = [];
		if (!empty($comment['header'])) {
			$doc[] = '__' . htmlentities($comment['header']) . '__';
		}
		if (!empty($comment['detail'])) {
			$doc[] = htmlentities($comment['detail']);
		}
		if ($scope === DocMen::CLS) {
			if (!empty($comment['property'])) {
				$doc[] = '__Public Properties__';
				foreach ($comment['property'] as $item) {
					$doc[] = '* `' . $item[0] . ' ' . $item[1] . ' ` ' . $item[2];
				}
			}
		}
		elseif ($scope === DocMen::FUNC || $scope === DocMen::METHOD) {
			if (!empty($comment['param'])) {
				$doc[] = '__Parameters__';
				foreach ($comment['param'] as $item) {
					$doc[] = '* `' . $item[0] . ' ' . $item[1] . ' ` ' . $item[2];
				}
			}
			if (!empty($comment['return'])) {
				$doc[] = '__Return__';
				$doc[] = '* `' . $comment['return'][0] . ' ' . $comment['return'][1] . ' ` ' . $comment['return'][2];
			}
		}
		if (!empty($comment['link'])) {
			$doc[] = '__Reference Links__';
			foreach ($comment['link'] as $item) {
				$doc[] = '> [' . $item[0] . '](' . $item[0] . ')';
			}
		}
		return $this->tag('DocComment', implode("\n\n", $doc));
	}

	public function functionBlock(array $data)
	{
		$address = '';
		if (!empty($data['file'])) {
			$file     = $this->getDoc()->getShowName('file', $data['file']);
			$fileLink = $this->fileUri($data['file'], $data['startLine'] ?? null, $data['endLine'] ?? null);
			if (isset($data['startLine']) && isset($data['endLine'])) {
				$file .= " <small>[{$data['startLine']}:{$data['endLine']}]</small>";
			}
			$address = $this->tag('address', $this->link($file, $fileLink), 'source-file');
		} elseif (isset($data['isInternal']) && $data['isInternal']) {
			$address = $this->tag('address', $this->scopeLink('file', ''), 'source-file');
		}
		$args = $data['params'] ?? [];
		$temp = [];
		foreach ($args as $arg) {
			$name  = '$' . $arg['name'];
			$value = $arg['defaultValue'];
			$type  = gettype($value);
			if ($arg['isReference'])
				$name = '&' . $name;
			if ($value === true) {
				$value = 'true';
			} elseif ($value === false) {
				$value = 'false';
			} elseif ($value === null) {
				$value = 'null';
			} elseif ($type === KE_STR) {
				if ($arg['name'] === 'salt') {
					$value = "''";
				} else {
					$value = "'" . htmlentities($value) . "'";
				}
			}
			if ($arg['hasType'])
				$name = $arg['type'] . ' ' . $name;

			if ($value === null && $arg['allowsNull'])
				$name .= ' = ' . $type;
			elseif ($value !== null) {
				if (is_array($value))
					$value = '[]';
				$name .= ' = ' . (string)$value;
			}
			$temp[] = $name;
		}
		$return = '';
		if ($data['returnType'] !== null) {
			$return = ': ' . $data['returnType'];
		}
		$prefix = 'function';
		if (!empty($data['class'])) {
			$prefix = $this->getDoc()->filterAccess($data['access']);
			if ($data['isStatic'])
				$prefix .= ' static';
		}

		$functionName = $prefix . ' ' . $data['name'] . '(' . implode(', ', $temp) . ')' . $return;
		if (strlen($functionName) > 100) {
			$functionName = $prefix . ' ' . $data['name'] . "(\n\t" . implode(",\n\t", $temp) . ')' . $return;
		}
		$block = [
			'```php',
			$functionName,
//			'{',
//			'}',
			'```',
		];
		$comment = '';
		if (!empty($data['doc']))
			$comment .= $this->docComment(DocMen::FUNC, $data['doc']);

		return $this->tag('DocComment', implode("\n", $block)) . $comment . $address;
	}

	public function functionArgs(array $data)
	{
		$html = [];
		$args = $data['params'] ?? [];
		foreach ($args as $arg) {
			$name         = $this->tag('var-name', '$' . $arg['name']);
			$defaultValue = $arg['defaultValue'];
			if ($arg['isReference'])
				$name = '<i>&</i>' . $name;

			$valueType = gettype($defaultValue);
			if ($defaultValue === true) {
				$defaultValue = 'true';
			} elseif ($defaultValue === false) {
				$defaultValue = 'false';
			} elseif ($valueType === KE_STR) {
				if ($arg['name'] === 'salt') {
					$defaultValue = "''";
				} else {
					$defaultValue = "'" . htmlentities($defaultValue) . "'";
				}
			}
			if ($arg['hasType'])
				$name = $this->tag('var-type', $arg['type']) . ' ' . $name;

			if ($defaultValue === null && $arg['allowsNull'])
				$name .= $this->equalSpan . $this->tag('var', 'null', $valueType);
			elseif ($defaultValue !== null) {
				if (is_array($defaultValue))
					$defaultValue = '[]';
				$name .= $this->equalSpan .
					$this->tag('var', (string)$defaultValue, $valueType);
			}

			$html[] = $name;
		}

		$return = '';
		if ($data['returnType'] !== null) {
			$return = ' : ' . $this->tag('var-type', $data['returnType']);
		}
		$content = '(' . implode('<span class="func-args-comma">,</span>', $html) . ')' . $return;

		return $this->tag('span', $content, 'func-args');
	}

	public function showClassItem($class, string $type, $name, array $item, $attr = null, $tag = 'h3')
	{
		if ($type === DocMen::METHOD) {
			return $this->functionBlock($item);
//			$head = $this->scopeName('method', $name, [
//				'data-static' => $item['isStatic'] ? '1' : '0',
//				'data-access' => $this->getDoc()->filterAccess($item['access']),
//			]);
//			$head .= $this->functionArgs($item);
//			return $this->tag($tag, $head, $attr) . $this->functionMisc($item);
		} elseif ($type === DocMen::PROP) {
			$head = $this->scopeName(DocMen::PROP, $name, [
				'data-static' => $item['isStatic'] ? '1' : '0',
				'data-access' => $this->getDoc()->filterAccess($item['access']),
			]);
			return $this->tag($tag, $head, $attr) . $this->functionMisc($item);
		} elseif ($type === DocMen::CONST) {
			$head  = $this->scopeName(DocMen::CONST, $name);
			$value = $this->equalSpan;
			if ($item['type'] === KE_STR)
				$item['value'] = "'" . htmlentities($item['value']) . "'";
			$value .= $this->tag('var', $item['value'], $item['type']);
			$head .= $this->tag('span', $value, 'func-args');
			return $this->tag($tag, $head, $attr) . $this->functionMisc($item);
		}
		return '';
	}

}