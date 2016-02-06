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

use Ke\Adm\Pagination;
use Ke\Uri;

/**
 * Html辅助器重构版本
 *
 * @package Ke\Web
 */
class Html
{

	const DATA_ARRAY        = 0;
	const DATA_ARRAY_ACCESS = 1;
	const DATA_GENERATOR    = 2;

	const TABLE_BODY              = 'table_body';
	const TABLE_HEAD_FROM_COLUMNS = 'table_head_columns';
	const TABLE_HEAD_FROM_DATA    = 'table_head_data';

	const ERR_EMPTY_TABLE_DATA   = 400;
	const ERR_EMPTY_TABLE_HEAD   = 401;
	const ERR_INVALID_TABLE_DATA = 402;
	const TABLE_TAIL_HEAD        = 'table:tail';

	const PAG_FIRST    = 'pagination:first';
	const PAG_LAST     = 'pagination:last';
	const PAG_PREV     = 'pagination:prev';
	const PAG_NEXT     = 'pagination:next';
	const PAG_CUR      = 'pagination:current';
	const PAG_TOTAL    = 'pagination:total';
	const PAG_ITEM     = 'pagination:item';
	const PAG_ELLIPSIS = 'pagination:ellipsis';
	const PAG_JUMP     = 'pagination:jump';
	const PAG_ROW      = 'pagination:row';


	const MSG_DEFAULT = 'default'; // 默认的，正常、普通的消息
	const MSG_SUCCESS = 'success'; // 成功，通过，很好
	const MSG_NOTICE  = 'notice'; // 提示、通知，可忽略
	const MSG_WARN    = 'warning'; // 警告，但不中断，不致命
	const MSG_ERROR   = 'error'; // 错误，中断，致命

	/** @var \DOMDocument */
	private $DOM = null;

	private $autoId = 0;

	protected $xhtmlStyle = true;

	/** @var array 属性定义 */
	protected $attrs = [
		'readonly'    => ['type' => 'bool'],
		'disabled'    => ['type' => 'bool'],
		'checked'     => ['type' => 'bool'],
		'selected'    => ['type' => 'bool'],
		'required'    => ['type' => 'bool'],
		'multiple'    => ['type' => 'bool'],
		'src'         => ['type' => 'link'],
		'href'        => ['type' => 'link'],
		'action'      => ['type' => 'link'],
		'data-url'    => ['type' => 'link'],
		'data-href'   => ['type' => 'link'],
		'data-src'    => ['type' => 'link'],
		'data-ref'    => ['type' => 'link'],
		'data-action' => ['type' => 'link'],
	];

	/** @var array 闭合标签 */
	protected $closingTags = [
		'meta'    => true, 'link' => true,
		'hr'      => true, 'br' => true, 'img' => true, 'input' => true, 'area' => true,
		'embed'   => true, 'keygen' => true, 'source' => true, 'base' => true,
		'col'     => true, 'param' => true, 'basefont' => true, 'frame' => true,
		'isindex' => true, 'wbr' => true, 'command' => true, 'track' => true,
	];

	/** @var array 标签的基础样式class */
	protected $baseClasses = [
//		'buttons'         => '',
//		'button'          => '',
//		'button:link'     => '',
//		'button:button'   => '',
//		'button:submit'   => '',
//		'button:reset'    => '',
//		'select'          => '',
//		'select:multiple' => '',
//		'input'           => '',
//		'input:text'      => '',
//		'input:password'  => '',
//		'input:hidden'    => '',
//		'input:number'    => '',
//		'input:email'     => '',
//		'input:url'       => '',
//		'inline-group'    => '',
//		'inline:radio'    => '',
//		'inline:checkbox' => '',
//		'label:inline'    => '',
//		'table:list'      => '',
//		'message:default' => '',
//		'message:success' => '',
//		'message:warning' => '',
//		'message:notice'  => '',
//		'message:error'   => '',
	];

	protected $aliasTags = [
		'buttons'            => 'div',
		'inline'             => 'span',
		'inline-group'       => 'div',
		'message'            => 'div',
		'pagination-wrap'    => 'div',
		'pagination-link'    => 'a',
		'pagination-current' => 'strong',
	];

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

	protected $labelColon = ' : ';

	protected $messages = [
		self::ERR_EMPTY_TABLE_DATA   => '输入的表(Table)数据为空！',
		self::ERR_INVALID_TABLE_DATA => '输入了无效的表(Table)数据！',
		self::ERR_EMPTY_TABLE_HEAD   => '表头(Table Head)字段为空！',
		self::TABLE_TAIL_HEAD        => '操作',
		self::PAG_FIRST              => '首页',
		self::PAG_LAST               => '末页',
		self::PAG_PREV               => '上一页',
		self::PAG_NEXT               => '下一页',
		self::PAG_CUR                => '第 %s 页',
		self::PAG_TOTAL              => '共 %s 页',
		self::PAG_ITEM               => '%d',
		self::PAG_ELLIPSIS           => '...',
		self::PAG_JUMP               => '跳转',
		self::PAG_ROW                => '{first}{prev}{links}{next}{last} {current} / {total} {button}',
	];

	protected $pagination = [
		'links'     => 6,
		'firstLast' => true,
		'prevNext'  => true,
		'jump'      => 'input',
	];

	public function isClosingTag(string $tag): bool
	{
		return !empty($this->closingTags[$tag]);
	}

	public function getClosingTags(): array
	{
		return array_keys($this->closingTags);
	}

	public function setClosingTag(string $tag, bool $isClosing)
	{
		$this->closingTags[$tag] = $isClosing;
		return $this;
	}

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

	public function mkId(...$fragments): string
	{
		return $this->mkSpecialValue('id', ...$fragments);
	}

	public function mkClass(...$fragments): string
	{
		return $this->mkSpecialValue('class', ...$fragments);
	}

	public function mkName(...$fragments): string
	{
		return $this->mkSpecialValue('name', ...$fragments);
	}

	public function setXhtmlStyle(bool $isXhtmlStyle)
	{
		$this->xhtmlStyle = $isXhtmlStyle;
		return $this;
	}

	public function isXhtmlStyle(): bool
	{
		return $this->xhtmlStyle;
	}

	public function setBaseClasses(array $classes)
	{
		$this->baseClasses = array_merge($this->baseClasses, $classes);
		return $this;
	}

	public function getBaseClasses(): array
	{
		return $this->baseClasses;
	}

	public function getMessage($key)
	{
		if (isset($this->messages[$key]))
			return $this->messages[$key];
		if (is_string($key))
			return $key;
		return false;
	}

	public function setMessage($key, string $message)
	{
		$this->messages[$key] = $message;
		return $this;
	}

	public function getMessages(): array
	{
		return $this->messages;
	}

	public function setMessages(array $messages)
	{
		$this->messages = $messages + $this->messages;
		return $this;
	}

	public function aliasTags(array $tags)
	{
		$this->aliasTags = array_merge($this->aliasTags, $tags);
		return $this;
	}

	public function aliasTag(string $specialTag, string $htmlTag)
	{
		$this->aliasTags[$specialTag] = $htmlTag;
		return $this;
	}

	public function getAliasTags()
	{
		return $this->aliasTags;
	}

	public function getAliasTag(string $specialTag, string $defaultTag = 'div')
	{
		return $this->aliasTags[$specialTag] ?? $defaultTag;
	}

	public function defineAttrs(array $attrs)
	{
		$this->attrs = array_merge($this->attrs, $attrs);
		return $this;
	}

	public function parseAttrByPreg(string $attr): array
	{
		$attr = trim($attr);
		if (empty($attr))
			return [];
		$result = [];
		// backup
		// ([^\+\s\=]+)(?:=["']?(?:.(?!["']?\s+(?:\S+)=|[>"']))+.["']?)?
		// ([^\s\=\+]+)(?:=(([\"\'])([^\3]*(?=\\\3)*.*)\3))?
		// last version
		// ([^\+\s\=]+)(?:\=[\"']([^\"]*)[\"']\s?)?
		$regex = '#([^\+\s\=]+)(?:\=([\"\'])([^\"\']*)\2|\=([^\"\'\s]*))?#';
		// attr-a="ab" attr-b=bb cc
		// ['attr-a' => 'ab', 'attr-b' => 'bb', 'cc' => '']
		// 如果需要将bb cc放入attr-b，需要：attr-a="ab" attr-b="bb cc"
		if (preg_match_all($regex, $attr, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$result[$match[1]] = $match[3] ?? '';
			}
		}
		return $result;
	}

	/**
	 * @return \DOMDocument
	 */
	public function getDOM()
	{
		if (!isset($this->DOM))
			$this->DOM = new \DOMDocument();
		return $this->DOM;
	}

	public function parseAttrByDOM(string $attr): array
	{
		$attr = trim($attr);
		if (empty($attr))
			return [];
		$data = [];
		$dom = $this->getDOM();
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
		$attr = trim($attr);
		if (empty($attr))
			return [];
		elseif ($attr[0] === '?') {
			parse_str(substr($attr, 1), $result);
			return $result;
		}
		else {
			return $this->parseAttrByPreg($attr);
		}
	}

	public function filterLink($link)
	{
		$type = gettype($link);
		if ($type === KE_ARY) {
			$link = Web::getWeb()->uri(array_shift($link), $link);
		}
		elseif ($type === KE_OBJ) {
			if (!($link instanceof Uri))
				$link = new Uri($link);
		}
		else {
			$link = Web::getWeb()->uri((string)$link);
		}
		return $link;
	}

	public function filterAttr(string $name, $value, array $attr)
	{
		$name = trim($name);
		if (empty($name) || is_numeric($name)) // 空字符串，0，数字，都不要
			return false;
		if ($this->xhtmlStyle) // 强制转小写
			$name = strtolower($name);
		$setting = $this->attrs[$name] ?? [];
		if (isset($setting['type'])) {
			if ($setting['type'] === 'bool') {
				if (empty($value))
					return false;
				return [$name, $name];
			}
			elseif ($setting['type'] === 'link') {
				return [$name, $this->filterLink($value)];
			}
		}
		$type = gettype($value);
		if ($type === KE_STR)
			$type = trim(KE_STR);
		// 这两个比较特殊
		if ($name === 'id' || $name === 'name') {
			if (empty($value))
				return false;
			elseif ($type === KE_ARY) {
				// todo: 数组的话，自动构建特定的属性
				return false;
			}
			elseif ($type === KE_STR)
				return [$name, $value];
			else
				return false;
		}
		elseif ($name === 'class') {
			if (empty($value))
				return false;
			elseif ($type === KE_ARY) {
				$value = $this->filterClass($value);
				if (!empty($value))
					return [$name, implode(' ', $value)];
				else
					return false;
			}
			elseif ($type === KE_STR)
				return [$name, $value]; // 字符串，就直接返回给他
			else
				return false;
		}
		else {
			if (empty($value) && $value !== 0 && $value !== '0' && $value !== 0.00) {
				if ($name === 'value')
					return [$name, ''];
				else
					return [$name, $name];
			}
			elseif ($type === KE_ARY || $type === KE_OBJ)
				return false;
			elseif ($type === KE_STR)
				return [$name, htmlentities($value)];
			else
				return [$name, (string)$value];
		}
	}

	public function attr2array($attr): array
	{
		$type = gettype($attr);
		if ($type === KE_STR) {
			// 解析字符串，就没有打扁不打扁的问题了
			$attr = ['class' => $attr];
		}
		elseif ($type === KE_OBJ) {
			$attr = get_object_vars($attr);
		}
		elseif ($type !== KE_ARY) {
			$attr = [];
		}
		return $attr;
	}

	public function mergeAttr(array $attr, array $merges): array
	{
		if (empty($attr))
			return $merges;
		if (isset($merges['class'])) {
			$this->addClass($attr, $merges['class']);
			unset($merges['class']);
		}
		return array_merge($attr, $merges);
	}

	public function mkAttr($attr, array $merges = null): string
	{
		// 不再支持多层的数组传入，除了特定一些属性以外，但会将整个数组传递过去，不会再循环递进的生成属性名
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		if (!empty($merges))
			$attr = $this->mergeAttr($attr, $merges);
		$result = '';
		foreach ($attr as $name => $value) {
			if (($segment = $this->filterAttr($name, $value, $attr)) === false)
				continue;
			$result .= ' ' . $segment[0];
			if ($segment[1] === $segment[0]) {
				if ($this->xhtmlStyle)
					$result .= '="' . $segment[0] . '"';
				continue;
			}
			$result .= '="' . $segment[1] . '"';
		}
		return $result;
	}

	public function attr($attr, array $merges = null)
	{
		print $this->mkAttr($attr, $merges);
		return $this;
	}

	public function filterClass($class, array &$result = []): array
	{
		$type = gettype($class);
		if ($type === KE_STR) {
			$class = trim($class);
			if (strpos($class, ' ') > 0) {
				$class = explode(' ', $class);
				$result = array_merge($result, array_flip(array_filter($class)));
			}
			else {
				$result[$class] = 1;
			}
		}
		elseif ($type === KE_ARY) {
			array_walk_recursive($class, function ($item) use (&$result) {
				$item = trim($item);
				if (!empty($item)) {
					$this->filterClass($item, $result);
				}
			});
		}
		else {
			return [];
		}
		return array_keys($result);
	}

	public function joinClass(...$classes): string
	{
		return implode(' ', $this->filterClass($classes));
	}

	public function mkClassAttr(...$classes): string
	{
		$classes = $this->filterClass($classes);
		if (!empty($classes))
			return ' class="' . implode(' ', $classes) . '"';
		return '';
	}

	public function classAttr(...$classes)
	{
		print $this->mkClassAttr(...$classes);
		return $this;
	}

	public function addClass(array &$attr, $class): array
	{
		if (empty($class))
			return $attr;
		if (isset($attr['class'])) {
			if (is_array($attr['class']))
				$attr['class'][] = $class;
			else
				$attr['class'] = [$attr['class'], $class];
		}
		else
			$attr['class'] = $class;
		return $attr;
	}

	public function getBaseClass(string $tag, ...$types): string
	{
		// 模式1
//		$key = $tag;
//		$baseClass = $this->baseClasses[$tag] ?? '';
//		if (!empty($types)) {
//			$types = array_filter($types);
//			if (!empty($types)) {
//				$key .= ':' . implode('_', $types);
//			}
//			if (!empty($this->baseClasses[$key]))
//				$baseClass .= (empty($baseClass) ? '' : ' ') . $this->baseClasses[$key];
//		}
//		return $baseClass;
		// 模式2
		$key = $tag;
		$baseClass = $this->baseClasses[$tag] ?? '';
		if (!empty($types)) {
			$types = array_filter($types);
			if (!empty($types)) {
				$key .= ':' . implode('_', $types);
			}
			if (!empty($this->baseClasses[$key]))
				$baseClass = $this->baseClasses[$key];
		}
		return $baseClass;
	}

	public function filterContent($content, array &$buffer = null): string
	{
		$type = gettype($content);
		if (is_callable($content)) {
			return call_user_func($content, $this);
		}
		elseif ($type === KE_STR)
			return $content;
		elseif ($type === KE_ARY) {
			$buffer = $buffer ?? [];
			foreach ($content as $item) {
				if (is_array($item)) {
					$buffer[] = $this->mkTag(...$item);
				}
				else {
					$buffer[] = (string)$item;
				}
			}
			return implode('', $buffer);
		}
		else
			return (string)$content;
	}

	public function preTag(string &$tag, &$content, array &$attr = null)
	{
		/////////////////////////////////////////////////////////////////
		// step.1
		// filter the require attributes for the tag.
		// We try to alias some special tag(types) to another tag,
		// and then we can use the base class to fill them.
		// As this to make the html UI methods can be reuse.
		// So do not modify the tag name to the right name at this step.
		/////////////////////////////////////////////////////////////////

		if ($tag === 'button') {
			if (empty($attr['type']))
				$attr['type'] = 'button';
			elseif ($attr['type'] !== 'button' && $attr['type'] !== 'submit' && $attr['type'] !== 'reset') {
				/** @link http://www.w3schools.com/tags/tag_button.asp */
				$attr['href'] = $attr['type'];
				$attr['type'] = 'link';
			}
		}

		/////////////////////////////////////////////////////////////////
		// step.2
		// Get special tag(types) css class and add them to the attributes.
		/////////////////////////////////////////////////////////////////

		$this->addClass($attr, $this->getBaseClass($tag, $attr['type'] ?? null));

		/////////////////////////////////////////////////////////////////
		// step.3
		// convert special tag(types) to right name.
		/////////////////////////////////////////////////////////////////

		if ($tag === 'button') {
			if ($attr['type'] === 'link') {
				$tag = 'a';
				unset($attr['type']); // forget type
			}
		}
		else {
			if (isset($this->aliasTags[$tag]))
				$tag = $this->aliasTags[$tag];
		}

		/////////////////////////////////////////////////////////////////
		// step.4
		// At the last, filter the non-string content
		/////////////////////////////////////////////////////////////////

		if (!empty($content) && !is_string($content))
			$content = $this->filterContent($content);

		// preTagName method do not need return anything, please use reference var
	}

	public function mkTag(string $tag = null, $content = null, $attr = null): string
	{
		$tag = strtolower(trim($tag));
		if (empty($tag))
			$tag = 'div';
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$preMethod = 'pre' . $tag;
		if (method_exists($this, $preMethod))
			$this->{$preMethod}($tag, $content, $attr);
		else
			$this->preTag($tag, $content, $attr);
		$result = $content;
		if (!empty($tag)) {
			$attr = $this->mkAttr($attr);
			if ($this->isClosingTag($tag)) {
				if ($this->xhtmlStyle)
					$result = "<{$tag}{$attr}/>";
				else
					$result = "<{$tag}{$attr}>";
			}
			else {
				$result = "<{$tag}{$attr}>{$content}</{$tag}>";
			}
		}
//		if (isset($attr['before']) || isset($attr['after'])) {
//			return $this->wrap($result, $attr['before'], $attr['after'], $attr['wrap'] ?? [],
//				$attr['wrapTag'] ?? 'div');
//		}
		return $result;
	}

	public function tag(string $tag = null, $content, $attr = null)
	{
		print $this->mkTag($tag, $content, $attr);
		return $this;
	}

	public function mkButton(string $text, $type = 'button', $attr = null)
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$attr['type'] = $type;
		return $this->mkTag('button', $text, $attr);
	}

	public function button(string $text, $type = 'button', $attr = null)
	{
		print $this->mkButton($text, $type, $attr);
		return $this;
	}

	public function preButtons(string &$tag, &$buttons, array &$attr = null)
	{
		if (empty($buttons) || !is_array($buttons)) {
			$buttons = empty($buttons) ? 'Button' : (string)$buttons;
			$tag = 'button'; // return the default button
			$attr['type'] = 'button';
			$this->addClass($attr, $this->getBaseClass('button', 'button'));
		}
		else {
			$content = '';
			foreach ($buttons as $button) {
				$content .= $this->mkButton(...(array)$button);
			}
			$buttons = $content;
			$tag = 'div';
			$this->addClass($attr, $this->getBaseClass('buttons'));
		}
	}

	public function mkButtons($buttons, $attr = null)
	{
		return $this->mkTag('buttons', $buttons, $attr);
	}

	public function buttons($buttons, $attr = null)
	{
		print $this->mkButtons($buttons, $attr);
		return $this;
	}

	public function mkLink(string $text, $href, $attr = null)
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$attr['href'] = $href;
		return $this->mkTag('a', $text, $attr);
	}

	public function link(string $text, $href, $attr = null)
	{
		print $this->mkLink($text, $href, $attr);
		return $this;
	}

	public function mkImg($src, $attr = null)
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$attr['src'] = $src;
		return $this->mkTag('img', null, $attr);
	}

	public function img($src, $attr = null)
	{
		print $this->mkImg($src, $attr);
		return $this;
	}

	public function preSelect(string &$tag, &$value = null, &$attr = null)
	{
		$tag = 'select';
		$value = $this->mkSelectOptions($attr['options'] ?? [], $value, $attr['defaultOption'] ?? null);
		unset($attr['options'], $attr['defaultOption']);
		if (!empty($attr['name'])) {
			if (!empty($attr['multiple'])) {
				if (!preg_match('#\[\]$#', $attr['name'])) {
					$attr['name'] .= '[]';
				}
			}
		}
		$this->addClass($attr, $this->getBaseClass($tag, empty($attr['multiple']) ? null : 'multiple'));
	}

	public function mkSelect($options, $selected = null, $attr = null, string $defaultOption = null)
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$attr['options'] = $options;
		$attr['defaultOption'] = $defaultOption;
		return $this->mkTag('select', $selected, $attr);
	}

	public function select($options, $selected = null, $attr = null, string $defaultOption = null)
	{
		print $this->mkSelect($options, $selected, $attr, $defaultOption);
		return $this;
	}

	public function filterOptions($options): array
	{
		if (is_object($options))
			return get_object_vars($options);
		elseif (is_array($options))
			return $options;
		else
			return [1 => (string)$options];
	}

	public function mkSelectOptions($options,
	                                $selected = null,
	                                string $defaultOption = null,
	                                $groupLabel = null,
	                                array &$buffer = null,
	                                int $deep = 0)
	{
		if (!is_array($options))
			$options = $this->filterOptions($options);
		$result = '';
		$groupLabel = trim($groupLabel);
		if (isset($defaultOption))
			$options = ['' => $defaultOption] + $options;
		foreach ($options as $value => $text) {
			if (is_array($text)) {
				if (empty($text))
					continue;
				$newLabel = $value;
				if (!empty($groupLabel))
					$newLabel = $groupLabel . ' - ' . $newLabel;
				$this->mkSelectOptions($text, $selected, null, $newLabel, $buffer, $deep + 1);
				continue;
			}
			$isSelected = false;
			if (is_array($selected) && !empty($selected) && array_search($value, $selected) !== false)
				$isSelected = true;
			elseif (equals($value, $selected))
				$isSelected = true;
			$result .= $this->mkTag('option', (string)$text, [
				'value'    => $value,
				'selected' => $isSelected,
			]);
		}
		if (!empty($groupLabel))
			$result = $this->mkTag('optgroup', $result, ['label' => $groupLabel]);
		$buffer[] = $result;
		if (count($buffer) > 1) {
			rsort($buffer);
			return implode('', $buffer);
		}
		return $result;
	}

	public function selectOptions($options, $selected = null, string $defaultOption = null)
	{
		print $this->mkSelectOptions($options, $selected, $defaultOption);
		return $this;
	}

	public function preInput(string &$tag, &$value = null, array &$attr = null)
	{
		if (empty($attr['type']))
			$attr['type'] = 'text';
		$type = $attr['type'];
		$attr['value'] = $value;
		$value = null;
		$this->addClass($attr, $this->getBaseClass($tag, $type));
	}

	public function mkInput(string $type, $value = null, $attr = null)
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		// input:select 还是太诡异
//		if ($type === 'select') {
//			return $this->mkTag('select', $value, $attr);
//		}
//		elseif ($type === 'textarea') {
//			return $this->mkTextarea($value, $attr);
//		}
		if (!empty($type))
			$attr['type'] = $type;
		return $this->mkTag('input', $value, $attr);
	}

	public function input(string $type, $value = null, $attr = null)
	{
		print $this->mkInput($type, $value, $attr);
		return $this;
	}

	public function mkTextarea(string $value = null, $attr = null)
	{
		return $this->mkTag('textarea', $value, $attr);
	}

	public function textarea(string $value = null, $attr = null)
	{
		print $this->mkTextarea($value, $attr);
		return $this;
	}

	public function mkLabel(string $text, string $for = null, $attr = null)
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		if (!empty($for))
			$attr['for'] = $for;
		return $this->mkTag('label', $text, $attr);
	}

	public function label(string $text, string $for, $attr = null)
	{
		print $this->mkLabel($text, $for, $attr);
		return $this;
	}

	public function mkGroupInput(string $type, $options, $value = null, $attr = null)
	{
		if (!is_array($options))
			$options = $this->filterOptions($options);
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$attr['type'] = $type;
		$baseId = empty($attr['id']) ? $this->mkAutoId($type) : $attr['id'];
		$baseName = $attr['name'] ?? '';
		$count = count($options);
		$isMulti = $type !== 'radio' && $count > 1;
		if ($isMulti && !empty($attr['name'])) {
			if (!preg_match('#\[\]$#', $attr['name'])) {
				$attr['name'] .= '[]';
			}
		}
		$result = '';
		if (!empty($baseName)) {
			$result .= $this->mkInput('hidden', null, ['name' => $baseName]);
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
				$label = $this->mkLabel($text, $attr['id'], ['class' => $this->getBaseClass('label', 'inline')]);
			$result .= $this->mkFormField($label, $input, ['type' => $type]);
		}
		return $result;
	}

	public function mkGroupHidden(array $values, array $ignores = [], string &$html = null, array $prefix = [])
	{
		$html = $html ?? '';
		$index = 0;
		foreach ($values as $field => $value) {
			if (isset($ignores[$field]))
				continue;
			if (is_array($value)) {
				$newPrefix = $prefix;
				$newPrefix[] = $field;
				$this->mkGroupHidden($value, $ignores, $html, $newPrefix);
				continue;
			}
			$name = $prefix;
			if (is_string($field) || (is_numeric($field) && intval($field) !== $index)) {
				$name[] = $field;
				$name = $this->mkSpecialValue('name', $name);
			}
			else {
				$name = $this->mkSpecialValue('name', $name);
				$name .= '[]';
			}
			$html .= $this->mkInput('hidden', (string)$value, ['name' => $name]);
			$index++;
		}
		return $html;
	}

	public function mkFormField(string $label, string $input, $attr = null)
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		if ($attr['type'] === 'checkbox' || $attr['type'] === 'radio') {
			$tag = 'span';
			$content = $input . $label;
		}
		else {
			$tag = 'div';
			$content = $label . $input;
		}
		$this->addClass($attr, $this->getBaseClass('field'));
		return $this->mkTag($tag, $content, $attr);
	}

	public function formField(string $label, string $input, $attr = null)
	{
		print $this->mkFormField($label, $input, $attr);
		return $this;
	}

	public function mkFormRow(string $label, string $input, $attr = null, bool $isGrouped = false)
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$content = $label . $input;
		$this->addClass($attr, $this->getBaseClass('row'));
		return $this->mkTag('div', $content, $attr);
	}

	public function formRow(string $label, string $input, $attr = null, bool $isGrouped = false)
	{
		print $this->mkFormRow($label, $input, $attr, $isGrouped);
		return $this;
	}


	public function filterFormColumn(string $field, $value = null, array $column = []): array
	{
		if (!empty($column['placeholder']))
			$placeholder = $column['placeholder'];
		elseif (!empty($column['label']))
			$placeholder = $column['label'];
		elseif (!empty($column['title']))
			$placeholder = $column['title'];
		else
			$placeholder = $field;
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

		$fields = $column['prefix'] ?? [];
		if (is_string($fields))
			$fields = [$fields];
		elseif (is_object($fields))
			$fields = [(string)$fields];
		$fields[] = $field;

		$id = $this->mkId($fields);
		$attr = [
			'type'  => $type,
			'id'    => $id,
			'name'  => $this->mkName($fields),
			'class' => 'field_' . $id,
		];

		/////////////////////////////////////////////////////////
		// 属性绑定
		/////////////////////////////////////////////////////////
		if ($isRequire)
			$attr['require'] = true;
		if (!empty($column['disabled']))
			$inputAttr['disabled'] = true;
		if (!empty($column['readonly']))
			$inputAttr['readonly'] = true;
		if ($type !== 'checkbox' && $type !== 'radio') {
			if ($isNumeric && $type === 'number') {
				if (!empty($column['min']) && is_numeric($column['min']))
					$attr['min'] = $column['min'];
				if (!empty($column['max']) && is_numeric($column['max']))
					$attr['max'] = $column['max'];
				if ($isDouble)
					$attr['step'] = 'any'; // html5 input type...
			}
			if ($type === 'text' || $type === 'password' || $type === 'email' || $type === 'url') {
				if (!empty($column['max']))
					$attr['maxlength'] = $column['max'];
			}
			if ($type !== 'select')
				$attr['placeholder'] = $placeholder;
		}

		return $attr;
	}

	public function mkFormColumn(string $field, $value = null, array $column = [])
	{
		if (!isset($value) && !empty($column['default']))
			$value = $column['default'];
		$inputAttr = $this->filterFormColumn($field, $value = null, $column);
		$type = $inputAttr['type'];
		$showLabel = true;
		if (isset($column['showLabel']) && $column['showLabel'] === false)
			$showLabel = false;
		$label = $column['label'] ?? $column['title'] ?? $field;
		$isGrouped = false;
		if ($type === 'hidden') {
			return $this->mkInput($type, $value, $inputAttr);
		}
		else {
			if ($showLabel && !empty($label)) {
				$label .= $this->labelColon;
				$label = $this->mkLabel($label, $inputAttr['id']);
			}
			else {
				$label = '';
			}
			if ($type === 'select') {
				$input = $this->mkSelect($column['options'] ?? [], $value, $inputAttr,
					$column['defaultOption'] ?? null);
			}
			elseif ($type === 'static') {
				$input = $this->mkTag('div', $value,
					$this->addClass($inputAttr, $this->getBaseClass('form', 'static')));
			}
			else {
				if (!empty($column['options'])) {
					$input = $this->mkGroupInput($type, $column['options'], $value, $inputAttr);
				}
				else {
					$input = $this->mkInput($type, $value, $inputAttr);
				}
			}
			if (isset($column['before']) || isset($column['after'])) {
				$input = $this->wrap($input, $column['before'], $column['after'], $column['wrap'] ?? [], 'input-wrap');
			}
		}
		return $this->mkFormRow($label, $input, $isGrouped);
	}

	public function formColumn(string $field, $value = null, array $column = [])
	{
		print $this->mkFormColumn($field, $value, $column);
		return $this;
	}

	public function filterTableHead(string $field,
	                                $column,
	                                $type = self::TABLE_HEAD_FROM_COLUMNS,
	                                array $mergeColumn = null)
	{
		if ($type === self::TABLE_HEAD_FROM_DATA) {
			$column = ['label' => $field];
		}
		else {
			if (is_string($column))
				$column = ['label' => $column];
			elseif (is_object($column))
				$column = ['label' => (string)$column];
			elseif (!is_array($column))
				$column = ['showTable' => !empty($column)];
		}
		// 附加设定
		if (!empty($mergeColumn))
			$column = array_merge($column, $mergeColumn);
		if (!isset($column['label']))
			$column['label'] = $column['title'] ?? $field;
		if (isset($column['tableClass']))
			$column['class'] = $this->mkClassAttr($column['tableClass'] ?? null);
		if (!empty($column['hidden']))
			$column = ['showTable' => false];
		return $column;
	}

	public function filterValueShow(string $field, $value, array $column = null): string
	{
		if (!empty($column['timestamp'])) {
			if (is_numeric($value) && $value > 0)
				$value = date('Y-m-d H:i:s', $value);
			else
				$value = '';
		}
		if (isset($column['onShow']) && is_callable($column['onShow'])) {
			$value = call_user_func($column['onShow'], $this, $value, $column);
		}
		return $value;
	}

	public function mkTableList($rows, array $options = null)
	{
		$options['rows'] = $rows;
		return Web::getWeb()->getContext()->loadComponent('table_list', $options);
	}

	public function tableList($rows, array $options = null)
	{
		print $this->mkTableList($rows, $options);
		return $this;
	}

	public function mkMessage($message, $type = self::MSG_DEFAULT, $attr = null)
	{
		if (!is_array($attr))
			$attr = $this->attr2array($attr);
		$attr['type'] = $type;
		return $this->mkTag('message', $message, $attr);
	}

	public function message($message, $type = self::MSG_DEFAULT, $attr = null)
	{
		print $this->mkMessage($message, $type, $attr);
		return $this;
	}

	public function mkPaginate(Pagination $pagination = null, $attr = null)
	{
		if (!isset($pagination))
			return '';

		$linksCount = intval($this->pagination['links']);
		$prevNext = !empty($this->pagination['prevNext']);
		$firstLast = !empty($this->pagination['firstLast']);
		$jump = $this->pagination['jump'];

		$pageField = $pagination->field;
		$pageTotal = $pagination->total;
		$pageCurrent = $pagination->current;

		$els = [
			'links'   => '',
			'prev'    => '',
			'next'    => '',
			'first'   => '',
			'last'    => '',
			'total'   => '',
			'current' => '',
			'button'  => '',
		];

		if ($pageTotal > $linksCount) {
			$half = (int)($linksCount / 2);
			$start = $pageCurrent - $half;
			if ($start < 1) $start = 1;
			$over = $start + $linksCount;
//				$over = $start + $linksCount - ($firstLast ? ($start == 1 ? 2 : 3) : 1);
			if ($over > $pageTotal) {
				$over = $pageTotal;
				$start = $over - $linksCount;
				if ($start <= 1) $start = 1;
			}
		}
		else {
			$start = 1;
			$over = $pageTotal;
		}

		$uri = Web::getWeb()->http->newUri();

		if ($linksCount > 0) {
			$item = $this->getMessage(self::PAG_ITEM);
			$ellipsis = $this->getMessage(self::PAG_ELLIPSIS);
			if ($start > 1) {
				if (!$firstLast) {
					$els['links'] .= $this->mkTag('pagination-link', sprintf($item, 1), [
						'href' => $uri->setQuery([$pageField => 1], true),
					]);
					$start += 1;
					if ($start > 2)
						$els['links'] .= $ellipsis;
				}
				else {
					$els['links'] .= $ellipsis;
				}
			}
			if (!$firstLast && $over < $pageTotal)
				$over -= 1;
			for ($i = $start; $i <= $over; $i++) {
				$text = sprintf($item, $i);
				if ($i === $pageCurrent) {
					$tag = 'pagination-current';
					$attr = null;
				}
				else {
					$tag = 'pagination-link';
					$attr = [
						'href' => $uri->setQuery([$pageField => $i], true),
					];
				}
				$els['links'] .= $this->mkTag($tag, $text, $attr);
			}
			if ($over < $pageTotal) {
				if (!$firstLast) {
					if ($over < $pageTotal - 1)
						$els['links'] .= $ellipsis;
					$els['links'] .= $this->mkTag('pagination-link', sprintf($item, $pageTotal), [
						'href' => $uri->setQuery([$pageField => $pageTotal], true),
					]);
				}
				else {
					$els['links'] .= $ellipsis;
				}
			}
		}

		if ($firstLast) {
			$els['first'] = $this->mkTag($start === 1 ? 'pagination-current' : 'pagination-link',
				$this->getMessage(self::PAG_FIRST), [
					'href' => $uri->setQuery([$pageField => 1], true),
				]);
			$els['last'] = $this->mkTag($over === $pageTotal ? 'pagination-current' : 'pagination-link',
				$this->getMessage(self::PAG_LAST), [
					'href' => $uri->setQuery([$pageField => $pageTotal], true),
				]);
		}

		if ($prevNext) {
			$els['prev'] = $this->mkTag($pageCurrent === 1 ? 'pagination-current' : 'pagination-link',
				$this->getMessage(self::PAG_PREV), [
					'href' => $uri->setQuery([$pageField => $pageCurrent - 1], true),
				]);
			$els['next'] = $this->mkTag($pageCurrent === $pageTotal ? 'pagination-current' : 'pagination-link',
				$this->getMessage(self::PAG_NEXT), [
					'href' => $uri->setQuery([$pageField => $pageCurrent + 1], true),
				]);
		}

		if (!empty($jump)) {
			if ($jump === 'input') {
				$el = $this->mkInput('number', $pageCurrent, [
					'step' => 1,
					'min'  => 1,
					'max'  => $pageTotal,
					'name' => $pageField,
				]);
			}
			else {
				$pages = range(1, $pageTotal);
				$el = $this->mkSelect(array_combine($pages, $pages), $pageCurrent, ['name' => $pageField]);
			}
			$els['current'] = $this->mkLabel(sprintf($this->getMessage(self::PAG_CUR), $el));
			$els['button'] = $this->mkButton($this->getMessage(self::PAG_JUMP), 'submit');
		}
		else {
			$els['current'] = sprintf($this->getMessage(self::PAG_CUR), $pageCurrent);
		}

		$els['total'] = sprintf($this->getMessage(self::PAG_TOTAL), $pageTotal);

		$row = substitute($this->getMessage(self::PAG_ROW), $els);
		if (!empty($jump)) {
			$row .= $this->mkGroupHidden($uri->getQueryData(), [$pageField => 1]);
			$row = $this->mkTag('form', $row, ['action' => $uri, 'method' => 'get']);
		}

		return $this->mkTag('pagination-wrap', $row);
	}

	public function paginate(Pagination $pagination = null, $attr = null)
	{
		print $this->mkPaginate($pagination, $attr);
		return $this;
	}

	public function wrap($content, $before = null, $after = null, $attr = null, string $type = 'div')
	{
		if (!is_string($content))
			$this->filterContent($content);
		if (!empty($before) && !is_string($before))
			$this->filterContent($before);
		if (!empty($after) && !is_string($after))
			$this->filterContent($after);
		return $this->mkTag($type, [$before, $content, $after,], $attr);
	}
}