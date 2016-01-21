<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Utils;

use Exception;
use Ke\Adm\Model;

/*

// 实际数据库测试用例
$tree = Ke\Utils\SortAsTree::factoryModel('Region', 'id', 'parent', 'position', 'name');
$tree->sort(function ($tree, $index, $data, $depth) {
	echo str_repeat('->', $depth), $data['id'], '<br />';
});

// 自己构建虚拟数据进行测试
$data = array(
	array('id' => 1, 'parent_id' => 0, 'position' => 4),
	array('id' => 2, 'parent_id' => 1, 'position' => 2),
	array('id' => 12, 'parent_id' => 10, 'position' => 22),
	array('id' => 15, 'parent_id' => 3, 'position' => 23),
	array('id' => 14, 'parent_id' => 5, 'position' => 12),
	array('id' => 3, 'parent_id' => 2, 'position' => 4),
	array('id' => 4, 'parent_id' => 3, 'position' => 3),
	array('id' => 5, 'parent_id' => 0, 'position' => 2),
	array('id' => 6, 'parent_id' => 4, 'position' => 9),
	array('id' => 23, 'parent_id' => 15, 'position' => 23),
	array('id' => 16, 'parent_id' => 4, 'position' =>2),
	array('id' => 17, 'parent_id' => 16, 'position' =>3),
	array('id' => 18, 'parent_id' => 16, 'position' =>1),
	array('id' => 19, 'parent_id' => 6, 'position' =>2),
	array('id' => 20, 'parent_id' => 23, 'position' =>2),
);
$tree = new Ke\Utils\SortAsTree($data, null, SORT_ASC);
$tree->sort(function ($tree, $index, $data, $depth) {
	echo str_repeat('->', $depth), $data['id'], '<br/>';
});

 */

/**
 * Class SortAsTree
 *
 * 无限分类的树形列表
 *
 *
 * @package Agi\Util
 * @author  Janpoem created at 2014/10/22 11:16
 */
class SortAsTree
{

	private $sortModes = [
		SORT_ASC  => 1,
		SORT_DESC => 1,
	];

	private $sortTypes = [
		SORT_NUMERIC => 1,
		SORT_REGULAR => 1,
		SORT_STRING  => 1,
	];

	private $isPrepare = false;

	private $rootNode = 'p#root';

	private $startDepth = 0;

	// 每一节有什么节点
	private $levelNodes = [];

	// 每个节点属于第几级
	private $nodeLevels = [];

	private $maxLevel = 0;

	protected $primaryField = 'id';

	protected $parentField = 'parent_id';

	protected $sortField = 'position';

	// SORT_ASC | SORT_DESC
	protected $sortMode = SORT_ASC;

	// SORT_NUMERIC | SORT_REGULAR | SORT_STRING
	protected $sortType = SORT_REGULAR;

	protected $data = [];

	protected $parentGroups = [];

	protected $fields = false;

	public static function factoryModel(
		$model,
		$primaryField = null,
		$parentField = null,
		$sortField = null,
		$select = null
	) {
		if (!is_subclass_of($model, Model::class))
			throw new Exception('Not a Adm\\Model class!');
		$instance = new static();
		$instance->setFields($primaryField, $parentField, $sortField);
		$fields = implode(',', $instance->getFields());
		if (!empty($select))
			$fields .= ' ,' . $select;
		$instance->setData($model::find(['select' => $fields, 'array' => 1]));
		return $instance;
	}

	public static function __set_state(array $data)
	{
		$obj = new static();
		foreach ($data as $field => $value) {
			$obj->{$field} = $value;
		}
		return $obj;
	}

	public function __construct($data = null, $sortType = null, $sortMode = null)
	{
		if (isset($data))
			$this->setData($data);
		if (isset($sortType))
			$this->setSortType($sortType);
		if (isset($sortMode))
			$this->setSortMode($sortMode);
	}

	public function setFields($primary = null, $parent = null, $sort = null)
	{
		if (isset($primary))
			$this->setPrimaryField($primary);
		if (isset($parent))
			$this->setParentField($parent);
		if (isset($sort))
			$this->setSortField($sort);
		return $this;
	}

	public function setPrimaryField($field)
	{
		if ((is_numeric($field) && $field >= 0) || (is_string($field) && !empty($field)))
			$this->primaryField = $field;
		return $this;
	}

	public function setParentField($field)
	{
		if ((is_numeric($field) && $field >= 0) || (is_string($field) && !empty($field)))
			$this->parentField = $field;
		return $this;
	}

	public function setSort($field, $type = null, $mode = null)
	{
		$this->setSortField($field);
		if ($this->sortField !== false) {
			if (isset($type) && isset($this->sortTypes[$type]))
				$this->sortType = $type;
			if (isset($mode) && isset($this->sortModes[$mode]))
				$this->sortMode = $mode;
		}
		return $this;
	}

	public function setSortField($field)
	{
		if ((is_numeric($field) && $field >= 0) || (is_string($field) && !empty($field)))
			$this->sortField = $field;
		elseif ($field === false)
			$this->sortField = false;
		return $this;
	}

	public function setSortType($type)
	{
		if ($this->sortField !== false) {
			if (isset($this->sortTypes[$type]))
				$this->sortType = $type;
		}
		return $this;
	}

	public function setSortMode($mode)
	{
		if ($this->sortField !== false) {
			if (isset($this->sortModes[$mode]))
				$this->sortMode = $mode;
		}
		return $this;
	}

	public function getFields()
	{
		$fields = [$this->primaryField, $this->parentField];
		if ($this->sortField !== false)
			$fields[] = $this->sortField;
		return $fields;
	}

	public function getSortMode()
	{
		return $this->sortField === false ? false : $this->sortMode;
	}

	public function getSortType()
	{
		return $this->sortField === false ? false : $this->sortType;
	}

	public function getSort()
	{
		return $this->sortField === false ? false : [$this->sortField, $this->sortType, $this->sortMode];
	}

	public function setStartDepth($depth)
	{
		if (is_numeric($depth))
			$this->startDepth = $depth;
		return $this;
	}

	public function setData($data)
	{
		if (empty($data))
			return $this;
		foreach ($data as $row) {
			// 无效数据不处理
			if (!isset($row[$this->parentField]) || !isset($row[$this->primaryField]) ||
			    ($this->sortField !== false && !isset($row[$this->sortField]))
			) {
				continue;
			}
			// 取出fields的基础结构
			if ($this->fields === false)
				$this->fields = array_fill_keys(array_keys($row), 1);
			$this->data[$row[$this->primaryField]] = $row;
		}
		return $this;
	}

	public function getData()
	{
		return $this->data;
	}

	public function getItem($pk)
	{
		return isset($this->data[$pk]) ? $this->data[$pk] : false;
	}

	public function getDataColumns()
	{
		if (empty($this->data))
			return [];
		$v = array_values($this->data);
		return array_keys($v[0]);
	}

	public function prepare()
	{
		if ($this->isPrepare)
			return $this;
		$this->isPrepare = true;
		$enableSort = $this->sortField !== false;
		$pkMapNode = [];
		$parentGroups = [];
		$sortGroups = [];
		foreach ($this->data as $pk => $row) {
			if (!isset($row[$this->parentField]))
				throw new Exception('invalid parent field!');
			if (!isset($row[$this->primaryField]))
				throw new Exception('invalid primary field!');
			$parent = $row[$this->parentField];
			$sort = false;
			$node = $this->rootNode;
			if ($enableSort) {
				if (!isset($row[$this->sortField]))
					throw new Exception('invalid sort field!');
				$sort = $row[$this->sortField];
			}
			if (isset($this->data[$parent])) {
				$node = "p#{$parent}";
			}
			$pkMapNode[$pk] = $node;
			$parentGroups[$node][] = $pk;
			if ($enableSort)
				$sortGroups[$node][] = $sort;
		}

		$nodeLevels[$this->rootNode] = 0;
		$this->levelNodes[0][] = $this->rootNode;
		foreach ($parentGroups as $n => $group) {
			//////////////////////////////////////////////////////////////////////////////////
			// 排序
			//////////////////////////////////////////////////////////////////////////////////
			if ($enableSort)
				array_multisort($sortGroups[$n], $this->sortMode, $this->sortType, $parentGroups[$n]);
			//////////////////////////////////////////////////////////////////////////////////
			// 检索层级
			//////////////////////////////////////////////////////////////////////////////////
			if ($n !== $this->rootNode) {
				$pk = substr($n, 2);
				if (isset($nodeLevels[$pkMapNode[$pk]])) {
					$nodeLevels[$n] = $nodeLevels[$pkMapNode[$pk]] + 1;
				}
				else {
					$level = 1;
					while (!isset($nodeLevels[$pkMapNode[$pk]])) {
						$level += 1;
						$pk = substr($pkMapNode[$pk], 2);
					}
					$nodeLevels[$n] = $nodeLevels[$pkMapNode[$pk]] + $level;
				}
				$this->levelNodes[$nodeLevels[$n]][] = $n;
				if ($nodeLevels[$n] > $this->maxLevel)
					$this->maxLevel = $nodeLevels[$n];
			}
		}
		$this->nodeLevels = $nodeLevels;
		$this->parentGroups = $parentGroups;
		unset($pkMapNode, $parentGroups, $sortGroups);
		return $this;
	}

	public function loopNode($node, $callback, $depth = null, & $completed = [], & $index = 0)
	{
		if (!isset($this->parentGroups[$node]))
			return $this;
		if (!isset($depth) || !is_numeric($depth))
			$depth = $this->startDepth;
		if (strpos($node, 'p#') !== 0)
			$node = "p#{$node}";
		$isCallBack = isset($callback) && is_callable($callback);
		foreach ($this->parentGroups[$node] as $id) {
			$index += 1;
			$data = $this->data[$id];
			$isCallBack && call_user_func($callback, $this, $index, $data, $depth);
			$completed[$id] = 1;
			$this->loopNode("p#{$data[$this->primaryField]}", $callback, $depth + 1, $completed, $index);
		}
		return $this;
	}

	/**
	 * 12
	 * 1
	 * ->2
	 * ->->3
	 * ->->->15
	 * ->->->4
	 * ->->->->6
	 * 5
	 * ->14
	 */

	public function sort($callback = null)
	{
		if (!$this->isPrepare)
			$this->prepare();
		$isCallBack = isset($callback) && is_callable($callback);
		if ($isCallBack && !empty($this->data)) {
			$index = 0;
			$this->loopNode($this->rootNode, $callback, $this->startDepth, $completed, $index);
			// 防止有丢失了节点的数据
			$lostData = array_diff_key($this->data, $completed);
			if (!empty($lostData)) {
				foreach ($lostData as $item) {
					call_user_func($callback, $this, $index, $item, $this->startDepth);
					$index += 1;
				}
			}
		}
	}

//    public function sort($callback = null)
//    {
//        if (!$this->isPrepare)
//            $this->prepare();
//        $isCallBack = isset($callback) && is_callable($callback);
//        if ($callback) {
//            $children = $this->getChildren(null, true);
//            foreach ($children as $id) {
//                $callback($this->data[$id], 1);
//                $children = $this->getChildren($id, true);
//                foreach ($children as $id) {
//                    $callback($this->data[$id], 2);
//                    $children = $this->getChildren($id, true);
//                    foreach ($children as $id) {
//                        $callback($this->data[$id], 3);
//                        $children = $this->getChildren($id, true);
//                        foreach ($children as $id) {
//                            $callback($this->data[$id], 4);
//                            $children = $this->getChildren($id, true);
//                            foreach ($children as $id) {
//                                $callback($this->data[$id], 5);
//                            }
//                        }
//                    }
//                }
//            }
//        }
//    }

	public function getRoot($field = false)
	{
		if (!$this->isPrepare)
			$this->prepare();
		if ($field !== false)
			$field = isset($this->fields[$field]) ? $field : $this->primaryField;
		if ($field === $this->primaryField) {
			return $this->parentGroups[$this->rootNode];
		}
		else {
			$data = [];
			foreach ($this->parentGroups[$this->rootNode] as $index) {
				$data[] = $field === false ? $this->data[$index] : $this->data[$index][$field];
			}
			return $data;
		}
	}

	public function getLevel($level = 0, $field = false)
	{
		if (!$this->isPrepare)
			$this->prepare();
		if ($field !== false)
			$field = isset($this->fields[$field]) ? $field : $this->primaryField;
		if ($level === 0)
			return $this->getRoot($field);
		if (!isset($this->levelNodes[$level]))
			return [];
		$data = [];
		foreach ($this->levelNodes[$level] as $node) {
			if (isset($this->parentGroups[$node])) {
				foreach ($this->parentGroups[$node] as $index) {
					$data[] = $field === false ? $this->data[$index] : $this->data[$index][$field];
				}
			}
		}
		return $data;
	}

	public function getChildren($pk = null, $field = false)
	{
		if (!$this->isPrepare)
			$this->prepare();
		if ($field !== false)
			$field = isset($this->fields[$field]) ? $field : $this->primaryField;
		$data = [];
		$node = isset($pk) ? "p#{$pk}" : $this->rootNode;
		if (isset($this->parentGroups[$node])) {
			if ($field === $this->primaryField) {
				return $this->parentGroups[$node];
			}
			else {
				foreach ($this->parentGroups[$node] as $index) {
					$data[] = $field === false ? $this->data[$index] : $this->data[$index][$field];
				}
			}
		}
		return $data;
	}

	public function getAllChildren($pk = null, $field = false, $includeSelf = false)
	{
		if (!$this->isPrepare)
			$this->prepare();
		if ($field !== false)
			$field = isset($this->fields[$field]) ? $field : $this->primaryField;
		// 不指定起点，等于拿全数据
		if (empty($pk)) {
			if ($field === $this->primaryField)
				return array_keys($this->data);
			else
				return $field === false ? $this->data : array_column($this->data, $field);
		}
		// 只拿children的主字段
		$children = $this->getChildren($pk, $this->primaryField);
		$collection = [];
		if ($includeSelf && isset($this->data[$pk])) {
			$collection[$pk] = $field === false ? $this->data[$pk] : $this->data[$pk][$field];
		}
//        $times = 0;
		while (!empty($children)) {
			$temp = [];
			foreach ($children as $id) {
				$g = $this->getChildren($id, $this->primaryField);
				if (!isset($collection[$id]))
					$collection[$id] = $field === false ? $this->data[$id] : $this->data[$id][$field];
				if (!empty($g)) {
					foreach ($g as $i) {
						if (!isset($collection[$i]))
							$collection[$i] = $field === false ? $this->data[$i] : $this->data[$i][$field];
						$temp[$i] = $i;
//                        $times+=1;
					}
				}
			}
			$children = $temp;
			unset($temp);
		}
		return $collection;
	}

	public function getParent($pk, $field = false)
	{
		if (!$this->isPrepare)
			$this->prepare();
		if (!isset($this->data[$pk]))
			return false;
		if ($field !== false)
			$field = isset($this->fields[$field]) ? $field : $this->primaryField;
		$parentId = $this->data[$pk][$this->parentField];
		// 可能存在已经丢失上级的情况
		if (!isset($this->data[$parentId]))
			return false; // 表示为顶级了
		if ($field !== false)
			return $this->data[$parentId][$field];
		return $this->data[$parentId][$parentId];
	}

	public function getParents($pk, $field = false, $includeSelf = false)
	{
		if (!$this->isPrepare)
			$this->prepare();
		if ($field !== false)
			$field = isset($this->fields[$field]) ? $field : $this->primaryField;
		$parents = [];
		if ($includeSelf && isset($this->data[$pk])) {
			$parents[$pk] = $field === false ? $this->data[$pk] : $this->data[$pk][$field];
		}
		while (($parent = $this->getParent($pk, $this->primaryField)) !== false) {
			$parents[] = $field === false ? $this->data[$parent] : $this->data[$parent][$field];
			$pk = $parent;
		}
		return array_reverse($parents);
	}

	public function getMaxLevel()
	{
		if (!$this->isPrepare)
			$this->prepare();
		return $this->maxLevel + 1;
	}

}