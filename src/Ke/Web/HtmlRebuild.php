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

class HtmlRebuild
{

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
		'src'         => ['filter' => 'filterLink'],
		'href'        => ['filter' => 'filterLink'],
		'action'      => ['filter' => 'filterLink'],
		'data-url'    => ['filter' => 'filterLink'],
		'data-href'   => ['filter' => 'filterLink'],
		'data-src'    => ['filter' => 'filterLink'],
		'data-ref'    => ['filter' => 'filterLink'],
		'data-action' => ['filter' => 'filterLink'],
		'data'        => ['flatten' => 1,],
		'style'       => ['merge' => 1,],
	];

	/** @var array 闭合标签 */
	protected $closingTags = [
		'meta'    => true, 'link' => true,
		'hr'      => true, 'br' => true, 'img' => true, 'input' => true, 'area' => true,
		'embed'   => true, 'keygen' => true, 'source' => true, 'base' => true,
		'col'     => true, 'param' => true, 'basefont' => true, 'frame' => true,
		'isindex' => true, 'wbr' => true, 'command' => true, 'track' => true,
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

	public function autoId(string $prefix)
	{
		return $prefix . '_auto_id_' . (++$this->autoId);
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
		// 如果西药将bb cc放入attr-b，需要：attr-a="ab" attr-b="bb cc"
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

	public function filterAttrName($name)
	{
		if (!is_string($name)) // 非字符串，不处理
			return false;
		$name = trim($name);
		if (empty($name) || is_numeric($name)) // 空字符串，0，数字，都不要
			return false;
		if ($this->xhtmlStyle) // 强制转小写
			$name = strtolower($name);
		return $name;
	}

	public function filterAttr($attr): array
	{
		$type = gettype($attr);
		if ($type === KE_STR) {
			// 解析字符串，就没有打扁不打扁的问题了
			$attr = $this->parseAttr($attr);
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
		if (isset($merges['class'])) {
			$this->attrAddClass($attr, $merges['class']);
			unset($merges['class']);
		}
		return $attr = array_merge($attr, $merges);
	}

	public function attr($attr, array $merges = null, string $prefix = null): string
	{
		// todo: 多层数组合并会有冲突，所以传递属性进来的时候，最好是一层的数组
		// 一般除了data属性外
		// attr: ['data-id' => 1]
		// merges: ['data' => ['id' => 2]]
		$attr = $this->filterAttr($attr);
		if (!empty($merges))
			$attr = $this->mergeAttr($attr, $merges);
		$result = '';
		foreach ($attr as $name => $value) {
			if (($name = $this->filterAttrName($name)) === false)
				continue;
			// 实际写入的key
			if (!empty($prefix))
				$name = "{$prefix}-{$name}";

		}
		return $result;
	}

	public function filterClass($class, array &$result = []): array
	{
		if (is_string($class)) {
			$class = trim($class);
			if (strpos($class, ' ') > 0) {
				$class = explode(' ', $class);
				$result = array_merge($result, array_flip(array_filter($class)));
			}
			else {
				$result[$class] = 1;
			}
		}
		elseif (is_array($class)) {
			array_walk_recursive($class, function ($item) use (&$result) {
				$item = trim($item);
				if (!empty($item)) {
					$this->filterClass($item, $result);
				}
			});
		}
		return array_keys($result);
	}

	public function attrAddClass(array &$attr, ...$classes): array
	{
		if (isset($attr['class'])) {
			if (is_array($attr['class']))
				$attr['class'][] = $classes;
			else
				$attr['class'] = [$attr['class'], $classes];
		}
		else
			$attr['class'] = $classes;
		return $attr;
	}

	public function mkTag(string $tag, string $content, $attr = null): string
	{
		$tag = strtolower(trim($tag));
		if (empty($tag))
			return $content;
		$isClosing = $this->isClosingTag($tag);
		$attr = $this->filterAttr($attr);
		return '';
	}
}