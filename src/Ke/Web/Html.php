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

	private $dom = null;

//	public $classButton        = '';
//	public $classButtonReset   = '';
//	public $classButtonSubmit  = '';
//	public $classA             = '';
//	public $classInputText     = 'input';
//	public $classInputPassword = '';
//	public $classInputRadio    = '';
//	public $classInputCheckbox = '';
//	public $classTextarea      = '';
//	public $classSelect        = '';

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

	public function mergeAttr(array $attr, array $merges): array
	{
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
		if (empty($merges))
			return $attr;
		return $this->mergeAttr($attr, $merges);
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

	public function getTagBaseClass(string ...$tags)
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

	public function fillTagBaseClass(array $attr, string ...$tags)
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
		$merges = $this->fillTagBaseClass([
			'type' => $type, 'value' => $value,
		], 'input', $type);
		$attr = $this->attr($attr, $merges);
		return "<input{$attr}/>";
	}

	public function input(string $type, string $value = null, $attr = null)
	{
		print $this->mkInput($type, $value, $attr);
		return $this;
	}

	public function textInput(string $value, $attr = null)
	{
		echo $this->mkInput('text', $value, $attr);
		return $this;
	}

	public function password(string $value, $attr = null)
	{
		echo $this->mkInput('password', $value, $attr);
		return $this;
	}

	public function hidden(string $value, $attr = null)
	{
		echo $this->mkInput('hidden', $value, $attr);
		return $this;
	}

	public function radio(string $value, $attr = null)
	{
		print $this->mkInput('radio', $value, $attr);
		return $this;
	}

	public function checkbox(string $value, $attr = null)
	{
		print $this->mkInput('checkbox', $value, $attr);
		return $this;
	}

	public function mkLabel(string $text, $attr = null)
	{
		if (!empty($attr))
			$attr = $this->attr($attr);
		return "<label{$attr}>{$text}</label>";
	}

	public function label(string $text, $attr = null)
	{
		print $this->mkLabel($text, $attr);
		return $this;
	}

	public function mkTextarea(string $value, $attr = null)
	{
		if (!empty($attr))
			$attr = $this->attr($attr);
		return "<textarea{$attr}>{$value}</textarea>";
	}

	public function textarea(string $value, $attr = null)
	{
		print $this->mkTextarea($value, $attr);
		return $this;
	}

	public function mkSelectOption(array $options,
	                               string $selected = null,
	                               array &$result = [],
	                               string $label = null,
	                               int $deep = 0)
	{
		$label = trim($label);
		if (!empty($label))
			$result[] = sprintf('<optgroup label="%s">', $label);
		foreach ($options as $value => $text) {
			$selectedText = '';
			if (is_array($text) && !empty($text)) {
				$newLabel = $value;
				if (!empty($label))
					$newLabel = $label . ' - ' . $newLabel;
				$this->mkSelectOption($text, $selected, $result, $newLabel, $deep + 1);
			}
			else {

				if (equals($value, $selected))
					$selectedText = ' selected="selected"';
				$result[] = sprintf('<option value="%s"%s>%s</option>', $value, $selectedText, $text);

			}
		}
		if (!empty($label))
			$result[] = sprintf('</optgroup>', $label);
		return implode(PHP_EOL, $result);
	}

	public function mkSelect(array $options, string $selected = null, $attr = null)
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
		$attr = $this->attr($attr);
		return "<select{$attr}>" . $this->mkSelectOption($options, $selected) . '</select>';
	}

	public function select(array $options, string $selected = null, $attr = null)
	{
		print $this->mkSelect($options, $selected, $attr);
		return $this;
	}
}
