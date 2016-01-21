<?php

namespace Agi\Html\Builder;

use Agi\Html\Component;
use Agi\Html\Form;
use Agi\Html\Table;
use \App;
use \Adm\DataList;
use \Agi\Util\String;
use \Agi\Http\Request;
use \Agi\Html\Options;

/**
 * Class Base
 *
 * @package Agi\Html\Builder
 * @author Janpoem created at 2014/10/5 13:41
 */
class Base
{

    protected $boolAttributes = array(
        'readonly' => 1,
        'disabled' => 1,
        'checked'  => 1,
        'selected' => 1,
        'required' => 1,
    );

    protected $paginateOptions = array(
        'baseUri'       => null,
        'count'         => 9, # 定义分页的link的数量
        'isForward'     => true, # 是否有导向按钮 定义是否出现上一页、下一页
        'isPolar'       => true, # 是否有两极按钮 定义是否出现第一页、最后一页
        'item'          => '%s',
        'prev'          => null,
        'next'          => null,
        'omission'      => '...',
        'classWrap'     => 'paginate-box',
        'classItem'     => 'paginate-item',
        'classCurrent'  => 'paginate-item paginate-curr',
        'classPrev'     => 'paginate-item paginate-prev paginate-forward',
        'classNext'     => 'paginate-item paginate-next paginate-forward',
        'classOmission' => 'paginate-item paginate-omission'
    );

    public $formAttr = array();

    public $formCls = 'af-form';

    public $formRowCls = 'af-row';

    public $formRowErrorCls = 'af-has-error';

    public $formTipCls = 'af-tip';

    public $formErrorCls = 'af-error';

    public $formLabelCls = 'af-label';

    public $radioLabelInlineCls = 'radio-inline';

    public $checkboxLabelInlineCls = 'checkbox-inline';

    public $inputCls = 'af-input';

    public $inputErrCls = '';

    public $clearCls = 'box';

    public $textInfoCls = 'aui-info';

    public $textErrorCls = 'aui-error';

    public $textSuccessCls = 'aui-success';

    public $textWarnCls = 'aui-warning';

    public $submitButtonCls = 'aui-btn af-btn af-submit';

    public $tableWrapCls = 'at-wrap';

    public $tableCls = 'at-table';

    public $tableBorderCls = 'at-table-border';

    public $tableHoverCls = 'at-table-hover';

    public $tableRowCls = 'at-row';

    public $tableCellCls = 'at-cell';

    public $tableCheckboxCellCls = 'at-checkbox-cell';

    public $tableTailCellCls = 'at-tail-cell';

    public $emptyDataCls = 'empty-data';

    public function pushClass($class, $push = null)
    {
        $result = array();
        if (!empty($class)) {
            $type = gettype($class);
            if ($type === PHP_STR)
                $result[] = trim($class);
            elseif ($type === PHP_ARY)
                $result[] = implode(' ', $class);
        }
        if (!empty($push)) {
            $type = gettype($push);
            if ($type === PHP_STR)
                $result[] = trim($push);
            elseif ($type === PHP_ARY)
                $result[] = implode(' ', $push);
        }
        if (empty($result))
            return '';
        return implode(' ', $result);
    }

    public function filterAttr($attr, array $merges = null)
    {
        $type = gettype($attr);
        if (empty($attr)) {
            $type = PHP_ARY;
            $attr = array();
        } elseif ($type === PHP_OBJ) {
            $type = PHP_ARY;
            $attr = get_object_vars($attr);
        } elseif ($type === PHP_STR) {
            $type = PHP_ARY;
            parse_str(urldecode($attr), $attr); // 防止直接传入urlencode了的字符串
        }
        if ($type === PHP_ARY) {
            if (!empty($merges)) {
                if (empty($attr))
                    $attr = $merges;
                else {
                    if (isset($attr['class']) && isset($merges['class'])) {
                        $attr['class'] = $this->pushClass($attr['class'], $merges['class']);
                        unset($merges['class']);
                    }
                    if (!empty($merges))
                        $attr = array_merge($attr, $merges);
                }
            }
        }
        return $attr;
    }

    public function mkAttr(array $attr, array $ignores = null)
    {
        $builder = array();
        foreach ($attr as $key => $value) {
            if (empty($key) || !is_string($key))
                continue;
            if (isset($ignores[$key]))
                continue;
            if (isset($this->boolAttributes[$key])) {
                // 是布尔类型的属性
                if (!empty($value))
                    $builder[] = "{$key}=\"{$key}\"";
            } else {
                $strValue = String::from($value);
                if (empty($strValue) && $strValue !== '0')
                    $builder[] = $key;
                else
                    $builder[] = "{$key}=\"{$value}\"";
            }
        }
        if (!empty($builder))
            return ' ' . implode(' ', $builder);
        return '';
    }

    /**
     * 构建html attribute字符串
     *
     * @param mixed $attr
     * @param array $merges 合并覆盖值
     * @param array $ignores 跳过值
     * @return string
     */
    public function attr($attr, array $merges = null, array $ignores = null)
    {
        return $this->mkAttr($this->filterAttr($attr, $merges), $ignores);
    }

    public function options($options, $firstOption = null, $firstText = null)
    {
        if (!($options instanceof Options)) {
            $type = gettype($options);
            if ($type === PHP_OBJ) {
                $type = PHP_ARY;
                $options = get_object_vars($options);
            } elseif ($type === PHP_STR) {
                $type = PHP_ARY;
                parse_str($options, $options);
            }
            if ($type !== PHP_ARY)
                $options = array();
            $options = Options::create($options, $firstOption, $firstText);
        }
        return $options;
    }

    public function optionsFromColumn(array $column)
    {
        $options = array();
        if (isset($column['options'])) {
            if (is_array($column['options']))
                $options = $column['options'];
            elseif (is_object($column['options'])) {
                if ($column['options'] instanceof Options)
                    return $column['options'];
                else
                    $options = get_object_vars($column['options']);
            }
        }
        if ($options instanceof Options)
            return $options;
        if ($column[0] === 'select' || $column[0] === 'multi-select') {
            $firstOption = null;
            $firstValue = null;
            if (isset($column['firstOption']))
                $firstOption = $column['firstOption'];
            if (isset($column['firstValue']))
                $firstValue = $column['firstValue'];
            if (isset($column['textField']) && isset($column['valueField'])) {
                $options = Options::createByList($options, $column['valueField'], $column['textField'], $firstOption, $firstValue);
            } else {
                $options = Options::create($options, $firstOption, $firstValue);
            }
        }
        return $options;
    }

    /**
     * 生成一个a标签
     *
     * @param string $text
     * @param string|array|null $url
     * @param mixed $attr
     * @param bool $isOutput
     * @return Base|string
     */
    public function a($text, $url = null, $attr = null, $isOutput = true)
    {
        // link的文字内容为空，即不要输出任何内容
        if (empty($text))
            return null;
        $text = String::from($text); // 转换一下文字类型
        if (empty($url))
            $url = linkUri('/'); // 空连接，当回首页
        elseif ($url !== '#') {
            if (is_array($url))
                $url = call_user_func_array('linkUri', $url);
            elseif (is_string($url)) {
                // #hello，就不做处理了
                if ($url[0] !== '#')
                    $url = linkUri($url);
            }
        } else
            $url = '#'; // 其他类型，一律忽略不管
        // 下属指定的值，视作将attr作为isOutput的参数使用
        if ($attr === true || $attr === 1 || $attr === false || $attr === 0) {
            $isOutput = intval($attr);
            $attr = null;
        }
        $attr = $this->attr($attr, array('href' => $url));
        $html = "<a{$attr}>{$text}</a>";
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    /**
     * 生成一个input[type=text]
     *
     * 区别3.x版本，input只生成一个input，hidden、password有专属对应的方法
     *
     * @param string $value
     * @param mixed $attr
     * @param bool $isOutput
     * @return Base|string
     */
    public function input($type, $value = null, $attr = null, $isOutput = true)
    {
        $attr = $this->attr($attr, array('type' => $type, 'value' => String::from($value), 'class' => $this->inputCls));
        $html = "<input{$attr}/>";
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    public function hidden($value = null, $attr = null, $isOutput = true)
    {
        $attr = $this->attr($attr, array('type' => 'hidden', 'value' => String::from($value)));
        $html = "<input{$attr}/>";
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    public function password($value = null, $attr = null, $isOutput = true)
    {
        $attr = $this->attr($attr, array('type' => 'password', 'value' => String::from($value)));
        $html = "<input{$attr}/>";
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    private function group($type = 'radio', $options, $attr = null, $checked = null, $isOutput = true)
    {
        $checkedField = $type === 'option' || $type === 'multi-option' ? 'selected' : 'checked';
        $isMultiCheck = $type === 'checkbox' || $type === 'multi-option' ? true : false;
//        $attr = $this->attr($attr, null, array($checkedField => true), true);
        $attr = $this->filterAttr($attr);
        unset($attr[$checkedField]);
        $html = array();
        $rand = mt_rand(100000, 999999);
        $options = static::options($options);
        if ($type === 'radio') {
            $hiddenAttr = array();
            if (isset($attr['name']))
                $hiddenAttr['name'] = $attr['name'];
            $html[] = static::hidden($checked, $hiddenAttr, false);
        }
        if ($options instanceof Options && !$options->isEmpty()) {
            $isChecked = false;
            $options->each(function ($index, $value, $text) use (
                $type,
                & $html,
                $attr,
                $rand,
                $checked,
                & $isChecked,
                $checkedField,
                $isMultiCheck
            ) {
                if ($checkedField === 'checked')
                    $attr['type'] = $type;
                $attr['value'] = $value;
                if ($type === 'radio') {
                    if (empty($attr['name']))
                        $attr['name'] = "agi_ui_radio_{$rand}";
                } elseif ($type === 'checkbox') {
                    if (empty($attr['name']))
                        $attr['name'] = "agi_ui_radio_{$rand}[]";
                    else
                        $attr['name'] .= "[]";
                }
                if (is_array($checked) && !empty($checked)) {
                    if (($isMultiCheck || !$isChecked) && array_search($value, $checked) !== false) {
                        $isChecked = true;
                        $attr[$checkedField] = true;
                    }
                } elseif (($isMultiCheck || !$isChecked) && compareValue($value, $checked)) {
                    $isChecked = true;
                    $attr[$checkedField] = true;
                }

                $attr = $this->attr($attr);
                if ($checkedField === 'selected')
                    $html[] = "<option{$attr}>{$text}</option>";
                else {
                    $labelInlineCls = $type === 'radio' ? $this->radioLabelInlineCls : $this->checkboxLabelInlineCls;
                    $html[] = "<label class=\"{$labelInlineCls}\"><input{$attr}/>{$text}</label>";
                }

            });
        }
        $html = implode('', $html);
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    public function radio($options, $attr = null, $checked = null, $isOutput = true)
    {
        return $this->group('radio', $options, $attr, $checked, $isOutput);
    }

    public function checkbox($options, $attr = null, $checked = null, $isOutput = true)
    {
        return $this->group('checkbox', $options, $attr, $checked, $isOutput);
    }

    public function select($options, $attr = null, $selected = null, $isOutput = true)
    {
        $attr = $this->attr($attr, array('class' => $this->inputCls), null);
        $options = $this->group('option', $options, null, $selected, false);
        $html = "<select{$attr}>{$options}</select>";
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    public function multiSelect($options, $attr = null, $selected = null, $isOutput = true)
    {
        $attr = $this->filterAttr($attr, array('multiple' => 'multiple'));
        if (empty($attr['name']))
            $attr['name'] = 'agi_ui_multi_select_' . mt_rand(100000, 999999) . '[]';
        else
            $attr['name'] .= '[]';
        $attr = $this->attr($attr);
        $options = $this->group('multi-option', $options, null, $selected, false);
        $html = "<select{$attr}>{$options}</select>";
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    public function textarea($value, $attr = null, $isOutput = true)
    {
        $attr = $this->attr($attr, array('class' => $this->inputCls), array('value' => 1));
        $html = "<textarea{$attr}>{$value}</textarea>";
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    protected function initPaginateOptions(array $options = null)
    {
        if (empty($options))
            $options = $this->paginateOptions;
        else
            $options = array_merge($this->paginateOptions, $options);
        $lang = App::getLang();
        if (empty($options['baseUri']))
            $options['baseUri'] = Request::current()->uri;
        if (empty($options['item']))
            $options['item'] = $lang['page.item'];
        if ($options['isForward']) {
            if (empty($options['prev']))
                $options['prev'] = $lang['page.prev'];
            if (empty($options['next']))
                $options['next'] = $lang['page.next'];
        }
        return $options;
    }

    /**
     * 分页处理，目前只支持对DataList进行分页处理
     *
     * @param array $pagination
     * @param array $options
     * @param bool $isOutput
     * @return array
     */
    public function paginate(array $pagination, array $options = null, $isOutput = true)
    {
        if (empty($pagination) || !isset($pagination['pageCount']) || !is_numeric($pagination['pageCount']))
            return null;
        if ($options === true || $options === 1 || $options === false || $options === 0) {
            $isOutput = intval($options);
            $options = null;
        }
        $options = static::initPaginateOptions($options);

        $baseUri = $options['baseUri'];
        $pageCount = $pagination['pageCount'];
        $pageNumber = $pagination['pageNumber'];
        $recordCount = $pagination['recordCount'];
        $linkCount = $options['count'];
        $isPolar = $options['isPolar'];
        $isForward = $options['isForward'];

        if ($pageCount > $linkCount) {
            $half = (int)($linkCount / 2) - 1;
            $start = $pageNumber - $half;
            if ($start < 1) $start = 1;
            $over = $start + $linkCount - ($isPolar ? ($start == 1 ? 2 : 3) : 1);
            if ($over > $pageCount) {
                $over = $pageCount;
                $start = $over - ($linkCount - ($isPolar ? ($over == $pageCount ? 2 : 3) : 1));
                if ($start <= 1) $start = 1;
            }
        } else {
            $start = 1;
            $over = $pageCount;
        }

        $html = array();
        $prev = 0;
        $next = 0;
        $pageField = 'page';
        $class = '';
        $text = '';
        $uri = $baseUri;
        if ($isForward) {
            $prev = $pageNumber - 1;
            $next = $pageNumber + 1;
            if ($next > $pageCount) $next = $pageCount;
            if ($prev < 1) $prev = 1;
        }

        $attr = $this->attr(array(
            'class'        => $options['classWrap'],
            'page-size'    => $pagination['pageSize'],
            'page-number'  => $pageNumber,
            'page-count'   => $pageCount,
            'record-count' => $recordCount,
        ), null, null);
        $html[] = "<div{$attr}>";

        if ($isForward) {
            $class = $options['classPrev'];
            $text = $options['prev'];
            if ($pageNumber === $prev)
                $html[] = "<span class=\"{$class}\">{$text}</span>";
            else {
                $uri = linkUri($baseUri, array($pageField => $prev));
                $html[] = "<a href=\"{$uri}\" class=\"{$class}\">{$text}</a>";
            }
        }

        if ($start !== 1) {
            $class = $options['classItem'];
            $text = sprintf($options['item'], 1);
            $uri = linkUri($baseUri, array($pageField => 1));
            $html[] = "<a href=\"{$uri}\" class=\"{$class}\">{$text}</a>";
            if ($start - 1 !== 1)
                $html[] = "<span class=\"{$options['classOmission']}\">{$options['omission']}</span>";
        }

        $index = $start;
        while ($index <= $over) {
            $text = sprintf($options['item'], $index);
            $class = $index === $pageNumber ? $options['classCurrent'] : $options['classItem'];
            if ($index == $pageNumber) {
                $html[] = "<span class=\"{$class}\">{$text}</span>";
            } else {
                $uri = linkUri($baseUri, array($pageField => $index));
                $html[] = "<a href=\"{$uri}\" class=\"{$class}\">{$text}</a>";
            }
            $index++;
        }

        if ($over !== $pageCount) {
            if ($over + 1 !== $pageCount)
                $html[] = "<span class=\"{$options['classOmission']}\">{$options['omission']}</span>";

            $class = $options['classItem'];
            $text = sprintf($options['item'], $pageCount);
            $uri = linkUri($baseUri, array($pageField => $pageCount));

            $html[] = "<a href=\"{$uri}\" class=\"{$class}\">{$text}</a>";
        }

        if ($isForward) {
            $text = $options['next'];
            $class = $options['classNext'];
            if ($pageNumber === $next)
                $html[] = "<span class=\"{$class}\">{$text}</span>";
            else {
                $uri = linkUri($baseUri, array($pageField => $next));
                $html[] = "<a href=\"{$uri}\" class=\"{$class}\">{$text}</a>";
            }
        }

        $html[] = '</div>';

        $html = implode('', $html);

        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    /**
     * 过滤http引用资源的url
     *
     * @param string $url
     * @param null $ext
     * @return null|string
     */
    public function filterHttpRef($url, $ext = null)
    {
        if (empty($url) || !is_string($url))
            return false;
        if (!preg_match('#^((?:([a-z]+)\:)?\/\/)#i', $url)) {
            if (!empty($ext))
                $url = ext($url, $ext);
            $url = httpUri($url);
        }
        return $url;
    }

    public function link($url, $rel, $type = null, array $attr = null, $isOutput = true)
    {
        if (empty($url))
            return null;
        $merges = array('rel' => $rel, 'href' => $this->filterHttpRef($url));
        if (!empty($type) && is_string($type))
            $merges['type'] = $type;
        $attr = $this->attr($attr, $merges);
        $html = "<link{$attr} />";
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    public function tip($message, $addClass = null, $isOutput = true)
    {
        if ($addClass === true || $addClass === 1 || $addClass === false || $addClass === 0) {
            $isOutput = $addClass;
            $addClass = null;
        }
        $class = $this->pushClass($this->textInfoCls, $addClass);
        $html = "<div class=\"{$class}\">{$message}</div>";
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    public function error($message, $addClass = null, $isOutput = true)
    {
        if ($addClass === true || $addClass === 1 || $addClass === false || $addClass === 0) {
            $isOutput = $addClass;
            $addClass = null;
        }
        $class = $this->pushClass($this->textErrorCls, $addClass);
        $html = "<div class=\"{$class}\">{$message}</div>";
        if ($isOutput) {
            echo $html;
            return $this;
        } else {
            return $html;
        }
    }

    public function mkId($prefix, $suffix = null)
    {
        $id = null;
        if (!empty($prefix))
            $id = $prefix;
        if (!empty($suffix))
            $id .= "_{$suffix}";
        return strtr(strtolower($id), '-', '_');
    }

    public function mkClass($prefix, $suffix = null)
    {
        $class = null;
        if (!empty($prefix))
            $class = $prefix;
        if (!empty($suffix))
            $class .= "-{$suffix}";
        return strtr(strtolower($class), '_', '-');
    }

    public function mkGroupClass($columns)
    {

    }

    public function getFormModeClass($mode = null)
    {
        return 'af-form-' . $mode;
    }

    public function formTag($attr, $mode = null)
    {
        $formAttr = $this->formAttr;
        $formAttr['class'] = $this->formCls;
        if (!empty($mode))
            $formAttr['class'] .= ' ' . $this->getFormModeClass($mode);
        $attr = $this->attr($attr, $formAttr);
        $html = "<form{$attr}>";
        echo $html;
    }

    public function formRowStart(Form $form, $field, $index, $mode = null)
    {
        $error = $form->getError($field);
        $rowClass = "{$this->clearCls} {$this->formRowCls} af-row-" . ($index + 1);
        $rowClass .= $index % 2 === 0 ? ' af-row-odd' : ' af-row-even';
        if (!empty($mode)) {
            $mode = strtr($mode, '/', '-');
            $rowClass .= ' af-' . $mode . '-row';
        }
        if ($error !== false)
            $rowClass .= ' ' . $this->formRowErrorCls;
        echo "<div class=\"{$rowClass}\">";
    }

    public function formRowClose()
    {
        echo '</div>';
    }

    public function formHead(Form $form, $label, $forId, $colon, $isRequire = false)
    {
        if (!empty($colon))
            $label .= "<span class=\"af-colon\">{$colon}</span>";
        if ($isRequire)
            $label .= '<span class="af-require"></span>';
        $label = "<div class=\"af-head\"><label class=\"{$this->formLabelCls}\" for=\"{$forId}\">{$label}</label></div>";
        echo $label;
    }

    public function formBodyStart(Form $form, $error = null)
    {
        echo '<div class="af-body">';
    }

    public function formBodyClose(Form $form)
    {
        echo '</div>';
    }

    public function formBody(Form $form, $mode, $field, array $attr, array $column)
    {
        $tip = empty($column['tip']) ? null : $column['tip'];
        $value = $form->getValue($field);
        $error = $form->getError($field);
        if ($error !== false) {
            if (empty($attr['class']))
                $attr['class'] = $this->inputErrCls;
            else
                $attr['class'] .= ' ' . $this->inputErrCls;
        }
        $this->formBodyStart($form, $error);
        if ($mode === 'text' || $mode === 'password' || $mode === 'file' || $mode === 'textarea') {
            $attr['placeholder'] = $attr['title'];
            if ($mode === 'textarea')
                $this->textarea($value, $attr);
            else
                $this->input($mode, $value, $attr);
        } elseif ($mode === 'select' || $mode === 'multi-select') {
            $options = $this->optionsFromColumn($column);
            if ($mode === 'select')
                $this->select($options, $attr, $value);
            elseif ($mode === 'multi-select')
                $this->multiSelect($options, $attr, $value);
        } elseif ($mode === 'radio' || $mode === 'checkbox') {
            $options = $this->optionsFromColumn($column);
            if ($mode === 'radio')
                $this->radio($options, $attr, $value);
            else
                $this->checkbox($options, $attr, $value);
        } else {
            $widget = null;
            $locals = array();
            if ($mode === 'widget') {
                if (!empty($column[1]) && is_string($column[1]))
                    $widget = $column[1];
                if (!empty($column[2]) && is_array($column[2]))
                    $locals = $column[2];
            } else {
                $widget = "form/{$mode}";
            }
            $locals['attr'] = $attr;
            $locals['form'] = $form;
            $locals['field'] = $field;
            $locals['value'] = $value;
            $locals['column'] = $column;
            if (!empty($widget))
                $form->getComponent()->widget($widget, $locals);
        }
        if (!empty($error)) {
            $this->formBodyErrorPlugs($field, $value, $error);
            $this->error($error, $this->formErrorCls);
        }
        if (!empty($tip))
            $this->tip($tip, $this->formTipCls);
        $this->formBodyClose($form);
    }

    public function formBodyErrorPlugs($field, $value, $error)
    {

    }

    public function submitButton(Form $form, $text)
    {
        echo "<button class=\"{$this->submitButtonCls}\" type=\"submit\">{$text}</button>";
    }

    public function emptyData($isOutput = true)
    {
        $text = App::getLang()->get('table.empty_data');
        $html = "<div class=\"{$this->emptyDataCls}\">{$text}</div>";
        if ($isOutput)
            echo $html;
        return $html;
    }

    public function tableStart(Table $table, $addClass = null)
    {
        $class = $this->pushClass($this->tableCls, $addClass);
        $id = $table->getId();
        if ($table->hasBorder())
            $class .= " {$this->tableBorderCls}";
        if ($table->hasHoverEffect())
            $class .= " {$this->tableHoverCls}";
        echo "<div class=\"{$this->tableWrapCls}\"><table id=\"{$id}\" class=\"{$class}\">";
    }

    public function tableClose(Table $table)
    {
        echo "</table></div>";
    }

    public function tableHead(Table $table, array $showColumns)
    {
        echo '<thead>';
        if (empty($showColumns)) {
            echo '<tr><th>', App::getLang()->get('table.empty_columns'), '</th></tr>';
        } else {
            $hasCheckbox = $table->hasCheckbox();
            $hasRowTail = $table->hasRowTail();
            $prefix = $table->getPrefix();
            $html = null;
            $id = $table->getId();
            foreach ($showColumns as $field) {
                $title = $table->getColumnTitle($field);
                $class = $this->tableCellCls;
                $class .= ' ' . $this->mkClass($prefix, $field) . '-cell';
                $html .= "<th class=\"{$class}\">{$title}</th>";
            }
            if ($hasCheckbox) {
                $html = "<th class=\"{$this->tableCellCls} {$this->tableCheckboxCellCls}\"><input type=\"checkbox\" id=\"{$id}_check_all\" data-target=\"#{$id}\" /></th>{$html}";
            }
            if ($hasRowTail)
                $html .= '<th class="'.$this->tableCellCls. ' ' .$this->tableTailCellCls.'">' . App::getLang()->get('table.row_tail_title') . '</th>';
            $html = "<tr>{$html}</tr>";
            echo $html;
        }
        echo '</thead>';
    }

    public function tableBody(Table $table, array $showColumns, $data)
    {
        echo '<tbody>';
        if (empty($showColumns)) {
            echo '<tr><td>', App::getLang()->get('table.empty_columns'), '</td></tr>';
        } elseif (empty($data)) {
            $columnCount = $table->getColumnCount();
            $html = $this->emptyData(false);
            echo "<tr><td rowspan=\"{$columnCount}\">{$html}</td></tr>";
        } else {
            $checkboxField = $table->getCheckboxField();
            $hasRowTail = $table->hasRowTail();
            $allHtml = null;
            $prefix = $table->getPrefix();
            $id = $table->getId();
            $columnClasses = array();
            $columns = $table->getColumns();
            foreach ($data as $index => $row) {
                $rowClass = $this->tableRowCls;
                $rowClass .= ' ' . $this->tableRowCls . '-' . ($index + 1);
                $rowClass .= ' ' . $this->tableRowCls . (($index % 2 === 0) ?  '-odd' : '-even');
                $html = "<tr class=\"{$rowClass}\">";
                $clone = $table->callOnRow($row);
                if ($checkboxField !== false) {
                    $checkboxValue = $row[$checkboxField];
                    $html .= "<td class=\"{$this->tableCellCls} {$this->tableCheckboxCellCls}\"><input type=\"checkbox\" id=\"{$id}_{$checkboxField}_{$checkboxValue}\" check-field=\"{$checkboxField}\" value=\"{$checkboxValue}\" /></td>";
                }
                foreach ($showColumns as $field) {
                    $value = isset($clone[$field]) ? $clone[$field] : '';
                    if (!empty($columns[$field]['timestamp'])) {
                        if (!empty($value))
                            $value = date(FORMAT_DATETIME, $value);
                        else
                            $value = '';
                    }
                    else {
                        $optionValue = $table->getModelColumnOptionValue($field, $value);
                        if ($optionValue !== false)
                            $value = $optionValue;
                    }
                    if (!isset($columnClasses[$field])) {
                        $class = $this->tableCellCls;
                        $class .= ' ' . $this->mkClass($prefix, $field) . '-cell';
                        $columnClasses[$field] = $class;
                    }
                    $html .= "<td class=\"{$columnClasses[$field]}\">{$value}</td>";
                }
                if ($hasRowTail)
                    $html .= '<td class="'.$this->tableCellCls. ' ' .$this->tableTailCellCls.'">'. $table->callRowTail($row) . '</td>';
                $html .= '</tr>';
                $allHtml .= $html;
            }
            echo $allHtml;
        }
        echo '</tbody>';
    }
}
