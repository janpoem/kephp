<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke;

/**
 * Php的输出缓冲控制器
 *
 * 这个类其实基本上都需要使用到，所以作为Core下必备的类。
 *
 * @package Ke\Core
 */
class OutputBuffer
{

	const NODE_ROOT = 'root';

	const NODE_STARTUP = 'startup';

	/** 执行冲刷 */
	const DO_FLUSH = 0;

	/** 执行清理，并获取 */
	const DO_CLEAN = 1;

	private static $instance = null;

	private $root = 0;

	/** @var null|string 当前节点 */
	private $node = null;

	/** @var int 当前的缓冲的层数的深度 */
	private $level = -1;

	/** @var array 当前缓冲实例的节点记录，以节点 => 缓冲层数存储 */
	private $nodes = [];

	private $outputs = [];

	private $autoIndex = 0;

	/**
	 * @return OutputBuffer
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	final private function __construct()
	{
		$level = ob_get_level();
		$this->root = $level;
		$this->setNode(self::NODE_ROOT, $level);
		if ($level <= 0) {
			$this->start(self::NODE_STARTUP);
		} else {
			$this->start(self::NODE_STARTUP);
//			$this->setNode(self::NODE_STARTUP, $level);
		}
	}

	protected function mkAutoNode()
	{
		return 'ob_' . (++$this->autoIndex);
	}

	private function setNode($node, $level)
	{
		$this->nodes[$node] = $level;
		$this->outputs[$node] = null;
		$this->node = $node;
		$this->level = $level;
		return $this;
	}

	protected function addNode($node, $isStartOb = false)
	{
		if (empty($node))
			$node = $this->mkAutoNode();
		if (isset($this->outputs[$node]))
			return $this;
		if ($isStartOb)
			ob_start();
		$level = ob_get_level();
		if ($level > $this->level) {
			$this->setNode($node, $level);
		}
		return $this;
	}

	public function start($node = null)
	{
		return $this->addNode($node, true);
	}

	public function rolling($target = null, $action = self::DO_CLEAN)
	{
		$to = 0;
		if (isset($this->nodes[$target])) {
			// 如果存在这个节点，表示回滚到这个节点
			$to = $this->nodes[$target];
		} elseif (is_numeric($target)) {
			if ($target < 0) {
				// 当目标小于0的时候，则表示回滚多少层数
				$to = $this->level + 1 + $target;
			} elseif ($target >= 0) {
				// 大于0，表示回滚到具体的某个层数
				$to = $target;
			}
			if ($to > $this->level)
				$to = $this->level;
			elseif ($to < 0)
				$to = 0;
		} elseif (!isset($this->nodes[$target])) {
			return $this;
		}
		if ($to < $this->root)
			$to = $this->root;
		$levels = array_flip($this->nodes);
		$level = ob_get_level();
		$node = $this->node;
		while ($level >= $to) {
			if (isset($levels[$this->level])) {
				$node = $levels[$this->level];
				unset($levels[$this->level], $this->nodes[$node]);
			}
			if ($action === self::DO_CLEAN) {
				$content = ob_get_contents();
				if (!empty($content)) {
					if (!isset($this->outputs[$node]))
						$this->outputs[$node] = $content;
					else
						$this->outputs[$node] = $content . $this->outputs[$node];
				}
				ob_end_clean();
			} elseif ($action === self::DO_FLUSH) {
				ob_end_flush();
			}
			$level--;
			$this->level = $level;
			$this->node = isset($levels[$this->level]) ? $levels[$this->level] : null;
		}
		return $this;
	}

	public function clean($target)
	{
		return $this->rolling($target, self::DO_CLEAN);
	}

	public function flush($target)
	{
		return $this->rolling($target, self::DO_FLUSH);
	}

	public function getFunctionBuffer($node, $fn)
	{
		if (empty($node))
			$node = $this->mkAutoNode();
		if (!isset($this->outputs[$node]) && is_callable($fn)) {
			$this->start($node);
			try {
				call_user_func($fn);
			} catch (\Exception $ex) {
			}
			$this->clean($node);
			return $this->getOutput($node, true);
		}
		return false;
	}

	public function getImportBuffer($file)
	{
		$node = $this->mkAutoNode();
		if (!isset($this->outputs[$node]) && is_file($file) && is_readable($file)) {
			$this->start($node);
			try {
				require $file;
			} catch (\Exception $ex) {
			}
			$this->clean($node);
			return $this->getOutput($node, true);
		}
		return false;
	}

	public function getOutputKeys()
	{
		return array_keys(array_filter($this->outputs));
	}

	public function getOutputs()
	{
		$keys = func_get_args();
		if (empty($keys)) {
			$outputs = array_filter($this->outputs);
		} else {
			$outputs = array_intersect_key($this->outputs, array_flip($keys));
		}
		return $outputs;
	}

	public function getOutput($node, $isRemove = false)
	{
		if (isset($this->outputs[$node])) {
			$output = $this->outputs[$node];
			if ($isRemove)
				unset($this->outputs[$node]);
			return $output;
		}
		return '';
	}
}