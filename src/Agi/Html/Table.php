<?php

namespace Agi\Html;

use Adm\DataList;
use Adm\Model;
use Agi\Util\SortAsTree;
use App;
use Agi\Html\Builder\Html;

/**
 * Class Table
 *
 * @package Agi\Html
 * @author Janpoem created at 2014/10/7 18:58
 */
class Table
{

    const MODEL_CLASS = '\\Adm\\Model';

    /** @var Component */
    protected $component;

    /** @var \Agi\Html\Builder\Html */
    protected $html;

    /** @var Reference */
    protected $reference;

    protected $data = array();

    protected $model;

    protected $prefix;

    protected $columns = array();

    protected $showColumns = array();

    protected $hideColumns = array();

    protected $checkboxField = 'id';

    protected $rowTail = false;

    protected $onRow = null;

    protected $border = true;

    protected $hover = true;

    protected $pagination = array();

    /** @var SortAsTree */
    protected $tree = null;

    protected $sortable = false;

    protected $sortFields = array();

    protected $deleteButton = null;

    protected $cssClass = '';

    public function __construct(Component $component, array $options = null, $data = null)
    {
        $this->bindComponent($component);
        $this->init($options, $data);
    }

    /**
     * 绑定View Component
     *
     * @param Component $component
     * @return $this
     */
    public function bindComponent(Component $component)
    {
        $this->component = $component;
        $this->html = $component->html();
        $this->reference = $component->ref();
        return $this;
    }

    public function setBuilder(Html $builder)
    {
        $this->html = $builder;
        return $this;
    }

    public function init(array $options = null, $data = null)
    {
        if (isset($options['model']) && is_subclass_of($options['model'], self::MODEL_CLASS))
            $this->model = $options['model'];
//        if (!empty($options['columns']) && is_array($options['columns']))
//            $this->columns = $options['columns'];
        if (!empty($options['showColumns']) && is_array($options['showColumns']))
            $this->showColumns = $options['showColumns'];
        if (!empty($options['hideColumns']) && is_array($options['hideColumns'])) {
            $this->hideColumns = array_fill_keys($options['hideColumns'], true);
        }

        if (!empty($options['class'])) {
            $this->cssClass = $options['class'];
        }

//        elseif (!empty($this->columns))
//            $this->showColumns = array_keys($this->columns);
        if (isset($options['onRow']))
            $this->onRow = $options['onRow'];
        if (isset($options['rowTail']) && is_callable($options['rowTail']))
            $this->rowTail = $options['rowTail'];
        else
            $this->rowTail = false;
        if (isset($options['prefix']) && is_string($options['prefix']))
            $this->prefix = strtolower($options['prefix']);
        if (isset($options['checkboxField'])) {
            if (is_string($options['checkboxField']))
                $this->checkboxField = $options['checkboxField'];
            elseif ($options['checkboxField'] === false)
                $this->checkboxField = false;
        }

        if (isset($options['border']))
            $this->border = !empty($options['border']);
        if (isset($options['hover']))
            $this->hover = !empty($options['hover']);

        if (!empty($options['sort']) && is_array($options['sort'])) {
            $this->sortable = true;
            $this->sortFields = $options['sort'];
        }

        if (!empty($options['deleteButton']))
            $this->deleteButton = $options['deleteButton'];

        if (!empty($data))
            $this->setData($data);
        if (!empty($this->model)) {
            $class = $this->model;
            if (empty($this->columns)) {
                $table = $class::getTable();
                $this->columns = $table->getColumns();
            }
            if (empty($this->prefix))
                $this->prefix = strtolower($this->model);
            if (!empty($this->checkboxField))
                $this->checkboxField = $class::getPrimaryKey();
        }
        if (empty($this->showColumns)) {
            if (isset($this->data[0])) {
                if (is_array($this->data[0]))
                    $this->showColumns = array_keys($this->data[0]);
                elseif (is_object($this->data[0])) {
                    $admModel = self::MODEL_CLASS;
                    if ($this->data[0] instanceof $admModel) {
                        $class = get_class($this->data[0]);
                        $this->showColumns = $this->filterShowColumnsFromModelColumns($class::getStaticColumns());
                    } elseif ($this->data[0] instanceof \ArrayObject) {
                        $clone = $this->data[0]->getArrayCopy();
                        $this->showColumns = array_keys($clone);
                    }
                }
            } elseif (!empty($this->columns)) {
                $this->showColumns = $this->filterShowColumnsFromModelColumns($this->columns);
            } elseif (isset($this->tree)) {
                $this->showColumns = $this->tree->getDataColumns();
            }
        } else {
            foreach ($this->showColumns as $field) {
                if (!empty($this->columns[$field]['sort']))
                    $this->sortFields[$field] = $this->columns[$field]['sort'];
            }
        }
        if (!empty($options['columns']) && is_array($options['columns'])) {
            foreach ($options['columns'] as $field => $column) {
                if (!isset($this->columns[$field]))
                    $this->columns[$field] = $column;
                else
                    $this->columns[$field] = array_merge_recursive($this->columns[$field], $column);
            }
        }
        $this->checkboxField = !empty($this->checkboxField) ? $this->checkboxField : false;
        if (empty($this->prefix))
            $this->prefix = 'at_' . mt_rand(100000, 999999);
        return $this;
    }

    public function filterShowColumnsFromModelColumns(array $columns)
    {
        $model = $this->model;
        $pk = empty($model) ? null : $model::getPrimaryKey();
        $showColumns = array();
        // 优先把主键挪到最前面显示
        if (!empty($pk))
            $showColumns[] = $pk;
        foreach ($columns as $field => $column) {
            if (!empty($column['hidden']))
                continue;
            if (!empty($column['sort']))
                $this->sortFields[$field] = $column['sort'];
            if (!empty($pk) && $field === $pk) {
                continue;
            }
            $showColumns[] = $field;
        }
        return $showColumns;
    }

    public function setData($data)
    {
        $type = gettype($data);
        if ($type === PHP_ARY) {
            $this->data = $data;
        } elseif ($type === PHP_OBJ) {
            if ($data instanceof \ArrayObject) {
                $this->data = $data;
                if ($data instanceof DataList) {
                    $this->setPagination($data->getPagination());
                    $model = $data->getModel();
                    if (!empty($model) && empty($this->model))
                        $this->model = $model;
                }
            } elseif ($data instanceof SortAsTree) {
                $this->tree = $data;
            }
        }
        if (!empty($this->data) && isset($this->data[0]) && is_subclass_of($this->data[0], self::MODEL_CLASS) && empty($this->model))
            $this->model = get_class($this->data[0]);
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setPagination(array $pagination)
    {
        $this->pagination = $pagination;
        return $this;
    }

    public function getPagination()
    {
        return $this->pagination;
    }

    public function hasPagination()
    {
        return !empty($this->pagination);
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getColumnTitle($field)
    {
        if (isset($this->columns[$field]['title'])) {
            if(is_array($this->columns[$field]['title'])) {
                $index = count($this->columns[$field]['title']) - 1;
                return $this->columns[$field]['title'][$index];
            }
            return $this->columns[$field]['title'];
        }
        //@Easy 添加了以下判断 if (isset($this->table))
//        if (isset($this->table))
//            return $this->table->getColumnTitle($field);
        $lang = App::getLang();
        if (isset($lang["{$this->model}#{$field}"]))
            return $lang["{$this->model}#{$field}"];
        else if (isset($lang[$field]))
            return $lang[$field];
        else
            return ucwords(strtr($field, '_', ' '));
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function hasCheckbox()
    {
        return $this->checkboxField !== false;
    }

    public function getCheckboxField()
    {
        return $this->checkboxField;
    }

    public function hasRowTail()
    {
        return $this->rowTail !== false;
    }

    public function hasBorder()
    {
        return $this->border;
    }

    public function hasHoverEffect()
    {
        return $this->hover;
    }

    public function callRowTail($data)
    {
        if ($this->rowTail !== false)
            return call_user_func($this->rowTail, $this, $data);
        return null;
    }

    public function callOnRow($data)
    {
        $clone = null;
        if (is_array($data))
            $clone = new \ArrayObject($data, \ArrayObject::ARRAY_AS_PROPS);
        elseif (is_object($data))
            $clone = new \ArrayObject(get_object_vars($data), \ArrayObject::ARRAY_AS_PROPS);
        if (is_callable($this->onRow)) {
            if (isset($clone))
                call_user_func($this->onRow, $this, $data, $clone);
        }
        return $clone;
    }

    public function getColumnCount()
    {
        $count = count($this->showColumns);
        if ($this->hasCheckbox())
            $count += 1;
        if ($this->hasRowTail())
            $count += 1;
        return $count;
    }

    public function getModelColumnOptionValue($field, $value)
    {
        if (!isset($this->model))
            return $value;
        $class = $this->model;
        return $class::getColumnOptionValue($field, $value);
    }

    public function getId()
    {
        return $this->html->mkId($this->prefix, 'table');
    }

    public function getSortFields()
    {
        return $this->sortFields;
    }

    public function getSortTree()
    {
        return isset($this->tree) ? $this->tree : false;
    }

    public function getDeleteButtonId()
    {
        return $this->deleteButton;
    }

    public function getHideColumns()
    {
        return $this->hideColumns;
    }

    public function render()
    {
        $pair = $this->html->getTablePair($this, $this->cssClass);

        echo $pair[0];
        $this->html->tableHead($this, $this->showColumns);
        if (isset($this->tree)) {
            $this->html->tableBody($this, $this->showColumns, $this->tree);
        } else {
            $this->html->tableBody($this, $this->showColumns, $this->data);
        }

        echo $pair[1];
        if ($this->hasPagination())
            $this->html->paginate($this->getPagination());
        // 全部加载完，输出一下Table的js
        $checkboxField = $this->getCheckboxField();
        if ($checkboxField !== false) {
            $id = $this->getId();
            ?>
            <script type="text/javascript">
                (function() {
                    var id = '<?php echo $id; ?>', table = document.getElementById(id), field = '<?php echo $checkboxField; ?>';
                    var loop = function(table, fn) {
                        for (var i = 1; i < table.rows.length; i++) {
                            var cells = table.rows[i].cells;
                            var input = cells[0].children[0];
                            fn && fn.call(null, input);
                        }
                    };
                    if (table) {
                        var checkAll = document.getElementById(id + '_check_all');
                        checkAll.onclick = function() {
                            loop(table, function(input) {
                                input.checked = checkAll.checked;
                            });
                        };
                        table.getCheckedItems = function() {
                            var results = [];
                            loop(table, function(input) {
                                input.checked && results.push(input.value);
                            });
                            return results;
                        };
                        table.getCheckedInputs = function() {
                            var results = [];
                            loop(table, function(input) {
                                input.checked && results.push(input);
                            });
                            return results;
                        };
                    }
                })();
            </script>
        <?php
        }

        $this->html->tableScript($this);
    }
}
 