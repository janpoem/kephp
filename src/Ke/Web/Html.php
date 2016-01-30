<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Web;

use Ke\Uri;

class Html
{


	/** @var array 布尔类型的属性名 */
	protected static $boolAttrs = [
		'readonly' => 1,
		'disabled' => 1,
		'checked'  => 1,
		'selected' => 1,
		'required' => 1,
		'multiple' => 1,
	];

	/** @var array 行内标签 */
	protected static $inlineTags = [
		'img'     => 1, 'hr' => 1, 'br' => 1, 'input' => 1, 'meta' => 1, 'area' => 1, 'embed' => 1, 'keygen' => 1,
		'source'  => 1, 'base' => 1, 'col' => 1, 'link' => 1, 'param' => 1, 'basefont' => 1, 'frame' => 1,
		'isindex' => 1, 'wbr' => 1, 'command' => 1, 'track' => 1,
	];

	protected $xhtmlStyle = true;

	protected $specialValueOptions = [
		'id'    => [
			'wrap'    => ['_', null],
			'replace' => [
				['-', '[', ']'],
				['_', '_', ''],
			],
		],
		'name'  => [
			'wrap'    => ['[', ']'],
			'replace' => ['-', '_'],
			'multi'   => true,
		],
		'class' => [
			'wrap'    => ['-', null],
			'replace' => ['_', '-'],
		],
	];

	protected $ignoreInputTypes = [
		'submit' => true,
		'button' => true,
		'reset'  => true,
	];

	private $dom = null;

	private $autoId = 0;

	public $labelColon = ' : ';

	public $classButton       = 'a';
	public $classButtonReset  = 'b';
	public $classButtonSubmit = 'c';
//	public $classA             = '';
//	public $classInputText     = 'input';
//	public $classInputPassword = '';
//	public $classInputRadio    = '';
	public $classInputCheckbox = 'cccc';
//	public $classTextarea      = '';
//	public $classSelect        = '';

	public $classInlineCheckbox = 'kf-inline';

	public $classFormRow       = 'kf-row';
	public $classFormRowSubmit = 'kf-row kf-row-submit';
	public $classFormField     = 'kf-field';
	public $classFormLabel     = 'kf-head';
	public $classFormInput     = 'kf-body';
	public $classFormStatic    = 'kf-static';

	public function mkAutoId(string $prefix)
	{
		return $prefix . '_auto_id_' . (++$this->autoId);
	}

	/**
	 * 拼接特定的HTML属性的值，主要针对id, name, class
	 *
	 * 默认
	 *
	 * @param string $type
	 * @param array  ...$fragments
	 * @return string
	 */
	public function mkSpecialValue(string $type, ...$fragments): string
	{
		if (empty($fragments))
			return '';
		if (!isset($this->specialValueOptions[$type]))
			$type = 'id';
		$ops = $this->specialValueOptions[$type];
		$result = '';
		$isMulti = false;
		$count = count($fragments);
		if ($fragments[$count - 1] === true)
			$isMulti = array_pop($fragments);
		array_walk_recursive($fragments, function ($fragment) use (&$result, $ops) {
			$fragment = trim($fragment);
			if (strlen($fragment) > 0) {
				if (empty($result))
					$result = $fragment;
				else
					$result .= "{$ops['wrap'][0]}{$fragment}{$ops['wrap'][1]}";
			}
		});
		if (!empty($result)) {
			if ($isMulti && isset($ops['multi']) && $ops['multi'])
				$result .= "{$ops['wrap'][0]}{$ops['wrap'][1]}";
			if (isset($ops['replace']) && isset($ops['replace'][0]) && isset($ops['replace'][1]))
				$result = str_replace($ops['replace'][0], $ops['replace'][1], $result);
		}
		return $result;
	}

	public function id(...$fragments): string
	{
		return $this->mkSpecialValue('id', ...$fragments);
	}

	public function cls(...$fragments): string
	{
		return $this->mkSpecialValue('class', ...$fragments);
	}

	public function name(...$fragments): string
	{
		return $this->mkSpecialValue('name', ...$fragments);
	}

	public function filterClass($class, array &$result = []): array
	{
		if (is_string($class)) {
			$class = explode(' ', $class);
			$result = array_merge($result, array_flip(array_filter($class)));
		}
		elseif (is_array($class)) {
			array_walk_recursive($class, function ($item) use (&$result) {
				$item = trim($item);
				if (!empty($item)) {
					$this->filterClass($item, $result);
				}
			});
		}
		return $result;
	}

	public function joinClass($class, ...$join): string
	{
		$class = $this->filterClass($class);
		if (!empty($join))
			$class = array_merge($class, $this->filterClass($join));
		return implode(' ', array_keys($class));
	}

	public function parseAttrByPreg(string $attr): array
	{
		$attr = trim($attr);
		if (empty($attr))
			return [];
		$data = [];
		// @todo 这个正则还有些问题，只是一个简单的实现，如：attr="a\"b"就匹配不到了，不过其实也够用了。
		// 正则表达式如果想完整的匹配是有些难度的，最好的方式，是使用DOMDocument来创建一个DOMElement，
		// 并将attr的字符串拼接成一个标签，然后再取回这个标签的属性列表，不过这种方式运行时间较长。
		//
		if (preg_match_all('#([^\+\s\=]+)(?:\=[\"\']([^\"]*)[\"\']\s?)?#', $attr, $matches)) {
			foreach ($matches[1] as $index => $name) {
				$value = isset($matches[2][$index]) ? $matches[2][$index] : null;
				$data[$name] = html_entity_decode($value);
			}
		}
		return $data;
	}

	public function getDom()
	{
		if (!isset($this->dom))
			$this->dom = new \DOMDocument();
		return $this->dom;
	}

	public function parseAttrByDom(string $attr): array
	{
		$attr = trim($attr);
		if (empty($attr))
			return [];
		$data = [];
		$dom = $this->getDom();
		$dom->loadHTML("<div {$attr}>");
		$els = $dom->getElementsByTagName('div');
		if (isset($els[0])) {
			/** @var \DOMAttr $item */
			foreach ($els[0]->attributes as $item) {
				$data[$item->name] = $item->value;
			}
		}
		return $data;
	}

	public function parseAttr(string $attr): array
	{
		return $this->parseAttrByPreg($attr);
	}

	function deepMergeArray(array $source, array $data, $isIgnoreEmpty = true)
	{
		if (empty($data))
			return $source;
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				if (!isset($source[$key]) || !is_array($source[$key]))
					$source[$key] = $value;
				else if (!empty($value))
					$source[$key] = $this->deepMergeArray($source[$key], $value, $isIgnoreEmpty);
			}
			else {
				if (empty($value) && $isIgnoreEmpty)
					continue;
				$source[$key] = $value;
			}
		}
		return $source;
	}

	public function attr($attr, array $merges = null, array $ignores = null, string $prefix = null)
	{
		$attr = $this->filterAttr($attr, $merges);
		$result = '';
		foreach ($attr as $key => $value) {
			if (empty($key) || !is_string($key))
				continue;
			if (isset($ignores[$key]))
				continue;
			// 实际写入的key
			if (!empty($prefix))
				$key = "{$prefix}-{$key}";
			$part = null;
			$join = ' ';
			if (isset(self::$boolAttrs[$key])) {
				// 是布尔类型的属性
				if (!empty($value)) {
					if ($this->xhtmlStyle) {
						$part = "{$key}=\"{$key}\"";
					}
					else {
						$part = $key;
					}
				}
			}
			else {
				$type = gettype($value);
				if ($type === KE_ARY && !empty($value)) {
					$join = '';
					$part = $this->attr($value, null, null, $key);
				}
				else {
					if ($value === true)
						$value = 1;
					elseif ($value === false)
						$value = 0;
					if (empty($value) && $value !== '0' && $value !== 0 && $value !== 0.00) {
						if ($key !== 'class' && $key !== 'name' && $key !== 'id') {
							if ($this->xhtmlStyle) {
								$part = "{$key}=\"\"";
							}
							else {
								$part = "{$key}";
							}
						}
					}
					else {
						$value = htmlentities($value);
						$part = "{$key}=\"{$value}\"";
					}
				}
			}
			if (!empty($part))
				$result .= $join . $part;
		}
		return $result;
	}

	public function filterAttr($attr, array $merges = null): array
	{
		$type = gettype($attr);
		if (empty($attr)) {
			$attr = [];
		}
		elseif ($type === KE_OBJ) {
			$attr = get_object_vars($attr);
		}
		elseif ($type === KE_STR) {
			$attr = $this->parseAttr($attr);
		}
		$make = false;
		/////////////////////////////////////////////
		// 特定的属性构建
		/////////////////////////////////////////////
		if (isset($merges['make'])) {
			$make = $merges['make'];
			unset($merges['make']);
		}
		elseif (isset($attr['make'])) {
			$make = $attr['make'];
			unset($attr['make']);
		}
		$makeAttr = [];
		if (!empty($make) && is_array($make)) {
			$last = array_pop($make);
			if (!empty($last) && is_array($last)) {
				foreach ($make as $field) {
					// 不要急于立刻写入$attr，要合并完成后，才写入
					$makeAttr[$field] = $this->mkSpecialValue($field, ...$last);
				}
			}
		}
		/////////////////////////////////////////////
		// class必须手动合并掉
		/////////////////////////////////////////////
		if (!empty($merges['class'])) {
			if (empty($attr['class']))
				$attr['class'] = '';
			if (!is_string($attr['class']))
				$attr['class'] = $this->joinClass($attr['class'], !empty($merges['class']) ? $merges['class'] : null);
			elseif (!empty($merges['class']))
				$attr['class'] = $this->joinClass($attr['class'], $merges['class']);
			// 合并数据中的class就丢弃掉了
			unset($merges['class']);
		}
		elseif (!empty($attr['class'])) {
			$attr['class'] = $this->joinClass($attr['class']);
		}
		if (isset($makeAttr['class'])) {
			if (empty($attr['class']))
				$attr['class'] = $makeAttr['class'];
			else
				$attr['class'] = $this->joinClass($attr['class'], $makeAttr['class']);
			unset($makeAttr['class']);
		}
		/////////////////////////////////////////////
		// 合并
		/////////////////////////////////////////////
		if (!empty($merges)) {
			if (empty($attr))
				$attr = $merges;
			else
				$attr = $this->deepMergeArray($attr, $merges, false); // 这里必须要使用深层合并，不能用浅层
		}
		if (!empty($makeAttr))
			$attr = $makeAttr + $attr;
		return $attr;
	}

	public function mkTag(string $tag = null, string $content, $attr = null) : string
	{
		$tag = strtolower(trim($tag));
		if (empty($tag))
			return $content;
		$isInline = isset(self::$inlineTags[$tag]);
		if ($isInline) {
			if ($tag === 'input')
				$merges['value'] = $content;
			elseif ($tag === 'img')
				$merges['src'] = $content;
			elseif ($tag === 'link')
				$merges['href'] = $content;
		}
		$merges = $this->fillTagBaseClass([], $tag);
		if (!empty($attr))
			$attr = $this->attr($attr, $merges);
		else
			$attr = $this->attr($merges);
		if ($isInline) {
			$html = "<{$tag}{$attr}/>";
		}
		else {
			$html = "<{$tag}{$attr}>{$content}</{$tag}>";
		}
		return $html;
	}

	public function tag(string $tag, string $content, $attr = null)
	{
		print $this->mkTag($tag, $content, $attr);
		return $this;
	}

	public function getTagBaseClass(...$tags)
	{
		if (empty($tags))
			return '';
		$tags = array_filter($tags);
		$field = implode(' ', $tags);
		$field = 'class' . str_replace(' ', '', ucwords($field));
		$class = '';
		if (isset($this->{$field}))
			$class = trim($this->{$field});
		return $class;
	}

	public function fillTagBaseClass(array $attr, ...$tags)
	{
		$class = $this->getTagBaseClass(...$tags);
		if (!empty($class)) {
			if (empty($attr['class']))
				$attr['class'] = $class;
			else
				$attr['class'] = $this->joinClass($attr['class'], $class);
		}
		return $attr;
	}

	public function preClass(string $class): string
	{
		if (!empty($class))
			return ' class="' . $class . '"';
		return '';
	}

	public function mkMeta(string $name = null, string $content = null, $attr = null)
	{
		$merges = [];
		if (!empty($name))
			$merges['name'] = $name;
		if (!empty($content))
			$merges['content'] = $content;
		$attr = $this->attr($attr, $merges);
		if (empty($attr))
			return '';
		return "<meta{$attr}/>";
	}

	public function meta(string $name = null, string $content = null, $attr = null)
	{
		print $this->mkMeta($name, $content, $attr);
		return $this;
	}

	public function mkLink(string $rel, $href = null, string $type = null, $attr = null)
	{
		$merges = [
			'rel'  => $rel,
			'href' => $this->filterHref($href),
		];
		if (!empty($type))
			$merges['type'] = $type;
		$attr = $this->attr($attr, $merges);
		return "<link{$attr}/>";
	}

	public function link(string $rel, $href = null, string $type = null, $attr = null)
	{
		print $this->mkLink($rel, $href, $type, $attr);
		return $this;
	}

	public function filterHref($href)
	{
		$type = gettype($href);
		if ($type === KE_ARY) {
			$href = Web::getWeb()->uri(array_shift($href), $href);
		}
		elseif ($type === KE_OBJ) {
			if (!($href instanceof Uri))
				$href = new Uri($href);
		}
		else {
			$href = Web::getWeb()->uri((string)$href);
		}
		return $href;
	}

	public function mkA(string $text, $href = null, $attr = null)
	{
		if (empty($text))
			return '';
		$html = '';
		// 空连接，不显示
		if (empty($text)) return $html;
		$merges = $this->fillTagBaseClass([
			'href' => $this->filterHref($href),
		], 'a');
		$attr = $this->attr($attr, $merges);
		$html = "<a{$attr}>{$text}</a>";
		return $html;
	}

	public function a(string $text, $href = null, $attr = null)
	{
		print $this->mkA($text, $href, $attr);
		return $this;
	}

	public function mkButton(string $text, string $type = 'button', $attr = null)
	{
		$attr = $this->filterAttr($attr);
		if (empty($attr['type']) && !empty($type))
			$attr['type'] = $type;
		$attr = $this->fillTagBaseClass($attr, 'button', $attr['type'] === 'button' ? null : $attr['type']);
		$attr = $this->attr($attr);
		return "<button{$attr}>{$text}</button>";
	}

	public function button(string $text, $attr = null)
	{
		print $this->mkButton($text, 'button', $attr);
		return $this;
	}

	public function submit(string $text, $attr = null)
	{
		print $this->mkButton($text, 'submit', $attr);
		return $this;
	}

	public function reset(string $text, $attr = null)
	{
		print $this->mkButton($text, 'reset', $attr);
		return $this;
	}

	public function mkInput(string $type, string $value = null, $attr = null)
	{
		$attr = $this->filterAttr($attr);
		$attr['type'] = $type;
		$attr['value'] = $value;
		$attr = $this->attr($this->fillTagBaseClass($attr, 'input', $type));
		return "<input{$attr}/>";
	}

	public function input(string $type = null, string $value = null, $attr = null)
	{
		print $this->mkInput($type, $value, $attr);
		return $this;
	}

	public function textInput(string $value = null, $attr = null)
	{
		echo $this->mkInput('text', $value, $attr);
		return $this;
	}

	public function password(string $value = null, $attr = null)
	{
		echo $this->mkInput('password', $value, $attr);
		return $this;
	}

	public function hidden(string $value = null, $attr = null)
	{
		echo $this->mkInput('hidden', $value, $attr);
		return $this;
	}

	public function preInlineInputLabel(string $type, string $input, string $label)
	{
		$class = $this->preClass($this->getTagBaseClass('inline', $type));
		return "<div{$class}>{$input}{$label}</div>";
	}

	public function mkGroupInput(string $type, $options, $value = null, $attr = null)
	{
		if (is_string($options)) {
			$options = [1 => $options];
		}
		if (!is_array($options) || empty($options))
			return [];
		$attr = $this->filterAttr($attr);
		$baseId = empty($attr['id']) ? $this->mkAutoId($type) : $attr['id'];
		$baseName = $attr['name'] ?? '';
		$count = count($options);
		$isMulti = $type === 'checkbox' && $count > 1;
		if ($isMulti && !empty($attr['name'])) {
			if (!preg_match('#\[\]$#', $attr['name'])) {
				$attr['name'] .= '[]';
			}
		}
		$result = [];
		if (!$isMulti && !empty($baseName)) {
			$result[] = $this->mkInput('hidden', null, ['name' => $baseName]);
		}
		foreach ($options as $val => $text) {
			$attr['checked'] = false;
			if (is_array($value))
				$attr['checked'] = array_search($val, $value) !== false;
			else
				$attr['checked'] = equals($val, $value);
			if ($count > 1)
				$attr['id'] = $baseId . '_' . $val;
			else
				$attr['id'] = $baseId;
			$label = '';
			$input = $this->mkInput($type, $val, $attr);
			if (!empty($text))
				$label = $this->mkLabel($text, $attr['id']);
			$result[] = $this->preInlineInputLabel($type, $input, $label);
		}
		return implode('', $result);
	}

	public function mkCheckbox($options, $value = null, $attr = null)
	{
		return $this->mkGroupInput('checkbox', $options, $value, $attr);
	}

	public function checkbox($options, $value = null, $attr = null)
	{
		print $this->mkCheckbox($options, $value, $attr);
		return $this;
	}

	public function mkRadio($options, $value = null, $attr = null)
	{
		return $this->mkGroupInput('radio', $options, $value, $attr);
	}

	public function radio($options, $value = null, $attr = null)
	{
		print $this->mkRadio($options, $value, $attr);
		return $this;
	}

	public function mkLabel(string $text, string $for = null, $attr = null)
	{
		if (empty($text))
			return ''; // make sure not empty!
		if (!empty($attr))
			$attr = $this->filterAttr($attr);
		else
			$attr = [];
		if (empty($attr['for']) && !empty($for))
			$attr['for'] = $for;
		$attr = $this->fillTagBaseClass($attr, 'label');
		$attr = $this->attr($attr);
		return "<label{$attr}>{$text}</label>";
	}

	public function label(string $text, string $for, $attr = null)
	{
		print $this->mkLabel($text, $attr);
		return $this;
	}

	public function mkTextarea(string $value = null, $attr = null)
	{
		if (!empty($attr))
			$attr = $this->attr($attr);
		return "<textarea{$attr}>{$value}</textarea>";
	}

	public function textarea(string $value = null, $attr = null)
	{
		print $this->mkTextarea($value, $attr);
		return $this;
	}

	public function mkSelectOption(array $options,
	                               $selected = null,
	                               array &$buffer = [],
	                               string $prefix = null,
	                               int $deep = 0)
	{
		$prefix = trim($prefix);
		if (!empty($prefix))
			$buffer[] = sprintf('<optgroup label="%s">', $prefix);
		foreach ($options as $value => $text) {
			$selectedText = '';
			if (is_array($text) && !empty($text)) {
				$newPrefix = $value;
				if (!empty($prefix))
					$newPrefix = $prefix . ' - ' . $newPrefix;
				$this->mkSelectOption($text, $selected, $buffer, $newPrefix, $deep + 1);
			}
			else {
				if (is_array($selected) && !empty($selected) && array_search($value, $selected) !== false)
					$selectedText = ' selected="selected"';
				elseif (equals($value, $selected))
					$selectedText = ' selected="selected"';
				$buffer[] = sprintf('<option value="%s"%s>%s</option>', $value, $selectedText, $text);

			}
		}
		if (!empty($prefix))
			$buffer[] = sprintf('</optgroup>', $prefix);
		return implode('', $buffer);
	}

	public function mkSelect(array $options, $selected = null, $attr = null, $defaultOption = null)
	{
		$attr = $this->filterAttr($attr);
		// 多选的处理，name字段的末尾，需要附加[]才能有效获取多个值
		if (!empty($attr['multiple'])) {
			if (!empty($attr['name'])) {
				if (!preg_match('#\[\]$#', $attr['name'])) {
					$attr['name'] .= '[]';
				}
			}
		}
		if (!empty($defaultOption))
			$options = ['' => $defaultOption] + $options;
		$attr = $this->attr($attr);
		return "<select{$attr}>" . $this->mkSelectOption($options, $selected) . '</select>';
	}

	public function select(array $options, $selected = null, $attr = null, $defaultOption = null)
	{
		print $this->mkSelect($options, $selected, $attr);
		return $this;
	}

	public function mkFormField(string $label, $value, string $type = null, $inputAttr = null)
	{
		if (empty($type))
			$type = 'text';
		$inputAttr = $this->filterAttr($inputAttr);
		$labelFor = '';
		$fieldAttr = [];
		if (!empty($inputAttr['id'])) {
			$labelFor = $inputAttr['id'];
			$fieldAttr['data-input-id'] = $inputAttr['id'];
		}
		if (empty($inputAttr['placeholder']) && ($type === 'text' || $type === 'password'))
			$inputAttr['placeholder'] = $label;
		$label = $this->mkLabel($label, $labelFor);
		$input = $this->mkInput($type, $value, $inputAttr);
		$fieldAttr = $this->fillTagBaseClass($fieldAttr, 'form', 'field');
		$fieldAttr = $this->attr($fieldAttr);
		$fieldLabelClass = $this->preClass($this->getTagBaseClass('form', 'field', 'label'));
		$fieldInputClass = $this->preClass($this->getTagBaseClass('form', 'field', 'input'));
		return "<div{$fieldAttr}><div{$fieldLabelClass}>{$label}</div><div{$fieldInputClass}>{$input}</div></div>";
	}

	public function preFormColumn(string $field, $value = null, array $column = [])
	{
		if (!isset($value) && !empty($column['default']))
			$value = $column['default'];
		$label = $column['label'] ?? $column['title'] ?? $field;
		$placeholder = $column['placeholder'] ?? $label;
		/////////////////////////////////////////////////////////
		$isRequire = false;
		if (!empty($column['require']))
			$isRequire = true;
		elseif (isset($column['empty']) && $column['empty'] !== true)
			$isRequire = true;
		/////////////////////////////////////////////////////////
		$isNumeric = false;
		$isDouble = false;
		// 过滤是否数值类型
		if (!empty($column['numeric'])) {
			$isNumeric = true;
			if ($column['numeric'] >= 3)
				$isDouble = true;
		}
		elseif (!empty($column['int']) || !empty($column['bigint'])) {
			$isNumeric = true;
		}
		elseif (!empty($column['float'])) {
			$isNumeric = $isDouble = true;
		}
		/////////////////////////////////////////////////////////
		$type = 'text';
		if (!empty($column['edit']))
			$type = $column['edit'];
		elseif (!empty($column['options']))
			$type = 'select';
		elseif (!empty($column['email']))
			$type = 'email';
		elseif ($isNumeric)
			$type = 'number'; // 并不强制变为number，如果用户指定为text，则仍使用text

		$inputMake = $column['prefix'] ?? [];
		if (!is_array($inputMake))
			$inputMake = (array)$inputMake;
		$inputMake[] = $field;
		$inputId = $this->id($inputMake);
		$inputName = $this->name($inputMake);
		$inputClass = $this->cls($inputMake);
		$inputAttr = [
			'id'    => $inputId,
			'name'  => $inputName,
			'class' => $inputClass,
		];
		/////////////////////////////////////////////////////////
		// 属性绑定
		if ($type !== 'checkbox' && $type !== 'radio') {
			if ($isRequire)
				$inputAttr['require'] = true;
			if ($isNumeric && $type === 'number') {
				if (!empty($column['min']) && is_numeric($column['min']))
					$inputAttr['min'] = $column['min'];
				if (!empty($column['max']) && is_numeric($column['max']))
					$inputAttr['max'] = $column['max'];
				if ($isDouble)
					$inputAttr['step'] = 'any'; // html5 input type...
			}
			if ($type === 'text' || $type === 'password' || $type === 'email' || $type === 'url') {
				if (!empty($column['max']))
					$inputAttr['maxlength'] = $column['max'];
			}
		}
		if (!empty($column['disabled']))
			$inputAttr['disabled'] = true;
		if (!empty($column['readonly']))
			$inputAttr['readonly'] = true;

		if ($type === 'hidden') {
			$label = '';
			$input = $this->mkInput($type, $value, $inputAttr);
		}
		elseif ($type === 'checkbox' || $type === 'radio') {
			$input = '';
			if (!empty($column['options']))
				$input = $this->mkGroupInput($type, $column['options'], $value, $inputAttr);
			// 没有指定options，暂时不知道该输出什么？以label内容作为输出？
		}
		else {
			if (!empty($label)) {
				$label .= $this->labelColon;
				$label = $this->mkLabel($label, $inputId);
			}


			if ($type === 'select') {
				$input = $this->mkSelect($column['options'] ?? [], $value, $inputAttr,
					$column['defaultOption'] ?? null);
			}
			elseif ($type === 'static') {
				$input = $this->mkTag('div', $value, $this->fillTagBaseClass($inputAttr, 'form', 'static'));
			}
			else {
				$input = $this->mkInput($type, $value, $inputAttr);
			}
		}

		return [
			'type'    => $type,
			'label'   => $label,
			'input'   => $input,
			'inputId' => $inputId,
		];
	}

	public function mkFormColumn(string $field, $value = null, array $column = [], Form $form = null)
	{
		$data = $this->preFormColumn($field, $value, $column);
		if ($data['type'] === 'hidden') {
			return $data['input'];
		}
		else {
			$fieldAttr = $this->fillTagBaseClass([
				'data-field-id' => $data['inputId'],
			], 'form', 'field');
			$fieldAttr = $this->attr($fieldAttr);
			$labelClass = $this->preClass($this->getTagBaseClass('form', 'label'));
			$inputClass = $this->preClass($this->getTagBaseClass('form', 'input'));
			return "<div{$fieldAttr}><div{$labelClass}>{$data['label']}</div><div{$inputClass}>{$data['input']}</div></div>";
		}
	}

	public function mkFormGroup()
	{

	}

	public function formColumn(string $field, $value = null, array $column = [], Form $form = null)
	{
		print $this->mkFormColumn($field, $value, $column);
		return $this;
	}

	public function mkFormRow(string $content, $isSubmit = false)
	{
		if ($isSubmit)
			$class = $this->preClass($this->getTagBaseClass('form', 'row', 'submit'));
		else
			$class = $this->preClass($this->getTagBaseClass('form', 'row'));
		return "<div{$class}>{$content}</div>";
	}
}
