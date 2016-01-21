<?php

namespace Agi\Html\Builder;

use Agi\Util\SortAsTree;
use App;
use Agi\Http\Request;
use Agi\Util\String;
use Agi\Html\Form;
use Agi\Html\Table;
use Agi\Html\Options;


/**
 * Class Html
 *
 * @package Agi\Html
 * @author Janpoem created at 2014/10/13 12:19
 */
class Html
{

    const MUTED = 'muted';

    const PRIMARY = 'primary';

    const SUCCESS = 'success';

    const INFO = 'info';

    const WARNING = 'warning';

    const DANGER = 'danger';

    protected static $docTypes = array(
        'html5'             => '<!DOCTYPE html>',
        'html4'             => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
        'html4_strict'      => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
        'html4_frameset'    => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
        'xhtml1.0'          => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'xhtml1.0_strict'   => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        'xhtml1.0_frameset' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        'xhtml1.1'          => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
    );

    protected static $boolAttributes = array(
        'readonly' => 1,
        'disabled' => 1,
        'checked'  => 1,
        'selected' => 1,
        'required' => 1,
        'multiple' => 1,
    );

    protected static $inlineTags = array(
        'input' => 1,
        'img'   => 1,
        'link'  => 1,
        'meta'  => 1,
        'hr'    => 1,
        'br'    => 1,
    );

    public $paginationOptions = array(
        'baseUri'       => null,
        'pageParam'     => 'page',
        'count'         => 7, # 定义分页的link的数量
        'isForward'     => true, # 是否有导向按钮 定义是否出现上一页、下一页
        'isPolar'       => true, # 是否有两极按钮 定义是否出现第一页、最后一页
        'jsJump'        => true,
        'item'          => '%s',
        'prev'          => null,
        'next'          => null,
        'omission'      => '...',
        'classWrap'     => 'pagination-box',
        'classUl'       => 'pagination-list',
        'classItem'     => 'pagination-item',
        'classActive'   => 'pagination-active',
        'classDisable'  => 'pagination-disable',
        'classPrev'     => 'pagination-item pagination-prev pagination-forward',
        'classNext'     => 'pagination-item pagination-next pagination-forward',
        'classOmission' => 'pagination-item pagination-omission',
        'classSelect'   => 'pagination-item pagination-select-wrap uk-form',
    );

    public $defaultDocType = 'html5';

    public $inputBaseCls = '';

    public $inputCls = 'af-input';

    public $inputErrCls = '';

    public $inputWrapCls = '';

    public $inputReadOnlyCls = '';

    public $selectCls = 'af-select';

    public $textareaCls = 'af-textarea';

    public $groupItemTag = 'div';

    public $groupItemCls = 'grouped-items';

    public $radioCls = 'radio-item';

    public $checkboxCls = 'checkbox-item';

    public $radioLabelInlineCls = 'inline-label radio-inline-label';

    public $checkboxLabelInlineCls = 'inline-label checkbox-inline-label';

    public $tipTypes = array(
        self::MUTED   => 'tip-muted',
        self::PRIMARY => 'tip-primary',
        self::SUCCESS => 'tip-success',
        self::INFO    => 'tip-info',
        self::WARNING => 'tip-warning',
        self::DANGER  => 'tip-danger',
    );

    public $highlightTypes = array(
        self::PRIMARY => 'hl-primary',
        self::SUCCESS => 'hl-success',
        self::INFO    => 'hl-info',
        self::WARNING => 'hl-warning',
        self::DANGER  => 'hl-danger',
    );

    public $buttonTypes = array(
        self::MUTED   => 'button-normal',
        self::PRIMARY => 'button-primary',
        self::SUCCESS => 'button-success',
        self::DANGER  => 'button-danger',
    );

    public $buttonCls = 'button';

    public $clearCls = 'box';

    public $formCls = '';

    public $formWrapCls = 'af-form-wrap';

    public $formRowCls = '';

    public $formRowErrorCls = 'af-row-error';

    public $formSingleRowCls = '';

    public $formLabelCls = '';

    public $formColonCls = '';

    public $formHeadCls = '';

    public $formBodyCls = '';

    public $formHelperCls = '';

    public $formRequireCls = '';

    public $formAttr = array();

    public $submitButtonCls = 'af-btn af-submit';


    public $formTipCls = 'af-tip';

    public $formErrCls = 'af-error';

    public $emptyDataCls = '';

    public $tableAttr = array(
        'data-tablesaw-sortable' => '',
        'data-tablesaw-sortable-switch' => '',
    );

    public $tableCls = '';

    public $tableWrapCls = '';

    public $tableBorderCls = '';

    public $tableHoverCls = '';

    public $tableCellCls = '';

    public $tableCheckboxCellCls = '';

    public $tableTailCellCls = '';

    public $tableRowCls = '';

    public $tableSortCls = 'tablesaw-sortable-head';

    public $tableSortAsc = '';

    public $tableSortDesc = '';

    public $tableSortText = '';

    public $iconTag = 'i';

    public $iconBaseCls = 'fa';

    public $iconClsPrefix = 'fa';

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
            // 这个属性是优先处理的，如果有这个属性，自动构建id name，并追加class
            // mkAll是组合特效，也可以独立mkName\mkId
            if (isset($attr['mkAll'])) {
                $attr['id'] = call_user_func_array(array($this, 'mkId'), $attr['mkAll']);
                $attr['name'] = call_user_func_array(array($this, 'mkName'), $attr['mkAll']);
                $addClass = call_user_func_array(array($this, 'mkClass'), $attr['mkAll']);
                if (empty($attr['class']))
                    $attr['class'] = $addClass;
                else
                    $attr['class'] = $this->pushClass($attr['class'], $addClass);
                unset($attr['mkAll']);
            } else {
                if (isset($attr['mkName'])) {
                    // 自动name会覆盖id
                    $attr['name'] = call_user_func_array(array($this, 'mkName'), $attr['mkName']);
                    $attr['id'] = call_user_func_array(array($this, 'mkId'), $attr['mkName']);
                    unset($attr['mkName']);
                } elseif (isset($attr['mkId'])) {
                    $attr['id'] = call_user_func_array(array($this, 'mkId'), $attr['mkId']);
                    unset($attr['mkId']);
                }
            }
            // 优先把class打扁一下
            if (!empty($merges['class'])) {
                if (empty($attr['class']))
                    $attr['class'] = '';
                if (!is_string($attr['class']))
                    $attr['class'] = $this->pushClass($attr['class'], !empty($merges['class']) ? $merges['class'] : null);
                elseif (!empty($merges['class']))
                    $attr['class'] = $this->pushClass($attr['class'], $merges['class']);
                // 合并数据中的class就丢弃掉了
                unset($merges['class']);
            } elseif (!empty($attr['class'])) {
                $attr['class'] = $this->pushClass($attr['class']);
            }
            if (!empty($merges)) {
                if (empty($attr))
                    $attr = $merges;
                else {
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
            if (isset(self::$boolAttributes[$key])) {
                // 是布尔类型的属性
                if (!empty($value))
                    $builder[] = "{$key}=\"{$key}\"";
            } else {
                $strValue = String::from($value);
                if (empty($strValue) && $strValue !== '0')
                    $builder[] = $key;
                else
                    $builder[] = "{$key}=\"{$strValue}\"";
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

    /**
     * 构造ID的方法
     *
     * 实现不算优雅，但经过运行时间的测试，比较节省运行时间，底层还是务实一点好
     *
     * @param string $prefix
     * @param array|string|null $suffix
     * @return mixed|string
     */
    public function mkId($prefix, $suffix = null)
    {
        $result = null;
        if (!empty($suffix)) {
            $type = gettype($suffix);
            if ($type === PHP_ARY) {
                $temp = null;
                foreach ($suffix as $item) {
                    if (empty($item)) continue;
                    $temp .= (empty($temp) ? null : '_') . $item;
                }
                $suffix = $temp;
                $type = PHP_STR;
            }
            if ($type === PHP_STR) {
                $result = empty($prefix) ? $suffix : $prefix . '_' . $suffix;
            }
        } elseif (!empty($prefix)) {
            $result = $prefix;
        }
        if (empty($result))
            return '';
        return str_replace(array('-', '[', ']'), array('_', '_', ''), $result);
    }

    public function mkClass($prefix, $suffix = null)
    {
        $result = null;
        if (!empty($suffix)) {
            $type = gettype($suffix);
            if ($type === PHP_ARY) {
                $temp = null;
                foreach ($suffix as $item) {
                    if (empty($item)) continue;
                    $temp .= (empty($temp) ? null : '-') . $item;
                }
                $suffix = $temp;
                $type = PHP_STR;
            }
            if ($type === PHP_STR) {
                $result = empty($prefix) ? $suffix : $prefix . '-' . $suffix;
            }
        } elseif (!empty($prefix)) {
            $result = $prefix;
        }
        if (empty($result))
            return '';
        return str_replace(array('_', '[', ']'), array('-', '-', ''), $result);
    }

    public function mkName($prefix, $suffix = null, $multi = false)
    {
        $result = null;
        if (!empty($suffix)) {
            $type = gettype($suffix);
            if ($type === PHP_STR) {
                if (empty($prefix))
                    $result = $suffix;
                else
                    $result = $prefix . "[{$suffix}]";
            } elseif ($type === PHP_ARY) {
                $temp = null;
                foreach ($suffix as $item) {
                    if (empty($item)) continue;
                    if (empty($prefix))
                        $prefix = $item;
                    $temp .= "[{$item}]";
                }
                $result = $prefix . $temp;
            }
        } elseif (!empty($prefix)) {
            $result = $prefix;
        }
        if ($multi)
            $result .= '[]';
        return str_replace('-', '_', $result);
    }

    public function mkOptions($options, $firstOption = null, $firstText = null)
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

    public function mkOptionsByColumn(array $column)
    {
        $options = null;
        if (isset($column['options'])) {
            $options = $column['options'];
        }
        if (!($options instanceof Options)) {
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
        if (!empty($column['attrFields']) && is_array($column['attrFields']))
            call_user_func_array(array($options, 'setAttrFields'), $column['attrFields']);
        return $options;
    }

    public function getDocType($mode)
    {
        if (isset(static::$docTypes[$mode]))
            return static::$docTypes[$mode];
        return static::$docTypes[$this->defaultDocType];
    }

    public function docType($mode)
    {
        echo $this->getDocType($mode, $this->defaultDocType);
        return $this;
    }

    public function mkTag($tag, $content = null, $attr = null)
    {
        $contentType = gettype($content);
        if ($contentType === PHP_ARY) {
            $content = $this->mkTags($content);
        }
        else {
            $content = String::from($content);
        }
        // 空标签，直接返回值内容
        if (empty($tag)) return $content;
        $tag = strtolower($tag);
        $isInline = isset(self::$inlineTags[$tag]);
        if (!empty($attr)) {
            $attr = $this->filterAttr($attr);
        }
        else {
            $attr = array();
        }
        if ($isInline) {
            if ($tag === 'input') {
                if (empty($attr['type']))
                    $attr['type'] = 'text';
                $attr['value'] = $content;
            } elseif ($tag === 'img')
                $attr['src'] = $content;
            elseif ($tag === 'link')
                $attr['href'] = $content;
        }
        if (!empty($attr))
            $attr = $this->mkAttr($attr);
        else
            $attr = '';

        if ($isInline) {
            $html = "<{$tag}{$attr} />";
        } else {
            $html = "<{$tag}{$attr}>{$content}</{$tag}>";
        }
        return $html;
    }

    public function tag($tag, $content = null, $attr = null)
    {
        echo $this->mkTag($tag, $content, $attr);
        return $this;
    }

    public function mkTags($tags)
    {
        $tagsType = gettype($tags);
        if ($tagsType === PHP_ARY || $tagsType === PHP_OBJ) {
            if ($tagsType === PHP_OBJ)
                $tags = get_object_vars($tags);
            $content = array();
            foreach ($tags as $tag) {
                $type = gettype($tag);
                if ($type === PHP_STR)
                    $content[] = $tag;
                elseif ($type === PHP_ARY) {
                    $content[] = call_user_func_array(array($this, 'mkTag'), $tag);
                }
            }
            return implode('', $content);
        }
        return $tags;
    }

    public function tags($tags)
    {
        echo $this->mkTags($tags);
        return $this;
    }

    public function mkIcon($class, $text = null, $addClass = null)
    {
        $class = "{$this->iconBaseCls} {$this->iconClsPrefix}-{$class}";
        if (!empty($addClass))
            $class = $this->pushClass($class, $addClass);
        if (!empty($text))
            $text = trim($text);
        $html = "<i class=\"{$class}\"></i>{$text}";
        return $html;
    }

    public function icon($class, $text = null, $addClass = null)
    {
        echo $this->mkIcon($class, $text, $addClass);
        return $this;
    }

    public function mkHyperlink($text, $href = null, $attr = null)
    {
        $html = '';
        // 空连接，不显示
        if (empty($text)) return $html;
        $text = String::from($text); // 转换一下文字类型
        if (empty($href))
            $href = '#'; // 空连接，设定为#
        if ($href !== '#') {
            $type = gettype($href);
            if ($type === PHP_STR) {
                // #hello，就不做处理了
                if ($href[0] !== '#')
                    $href = linkUri($href);
            } elseif ($type === PHP_ARY) {
                $href = call_user_func_array('linkUri', $href);
            } else {
                $href = '#'; // 其它可能性，全部当作#
            }
        }
        $attr = $this->attr($attr, array('href' => $href));
        $html = "<a{$attr}>{$text}</a>";
        return $html;
    }

    public function hyperlink($text, $href = null, $attr = null)
    {
        echo $this->mkHyperlink($text, $href, $attr);
        return $this;
    }

    public function a($text, $href, $attr = null)
    {
        echo $this->mkHyperlink($text, $href, $attr);
        return $this;
    }

    public function mkButton($text, $type = 'button', $uiType = self::MUTED, $addClass = null, $attr = null)
    {
        $class = isset($this->buttonTypes[$uiType]) ? $this->buttonTypes[$uiType] : '';
        $class = "{$this->buttonCls} {$class}";
        if (!empty($addClass))
            $class = $this->pushClass($class, $addClass);
        $attr = $this->attr($attr, array('type' => $type, 'class' => $class));
        return "<button{$attr}>{$text}</button>";
    }

    public function button($text, $type = 'button', $addClass = null, $attr = null)
    {
        echo $this->mkButton($text, $type, self::MUTED, $addClass, $attr);
        return $this;
    }

    public function buttonPrimary($text, $type = 'button', $addClass = null, $attr = null)
    {
        echo $this->mkButton($text, $type, self::PRIMARY, $addClass, $attr);
        return $this;
    }

    public function buttonSuccess($text, $type = 'button', $addClass = null, $attr = null)
    {
        echo $this->mkButton($text, $type, self::SUCCESS, $addClass, $attr);
        return $this;
    }

    public function buttonDanger($text, $type = 'button', $addClass = null, $attr = null)
    {
        echo $this->mkButton($text, $type, self::DANGER, $addClass, $attr);
        return $this;
    }

    public function mkButtonLink($text, $href = null, $type = self::MUTED, $addClass = null, $attr = null)
    {
        $class = isset($this->buttonTypes[$type]) ? $this->buttonTypes[$type] : '';
        $class = "{$this->buttonCls} {$class}";
        if (!empty($addClass))
            $class = $this->pushClass($class, $addClass);
        $attr = $this->filterAttr($attr, array('class' => $class));
        return $this->mkHyperlink($text, $href, $attr);
    }

    public function btnLink($text, $href = null, $addClass = null, $attr = null)
    {
        echo $this->mkButtonLink($text, $href, self::MUTED, $addClass, $attr);
        return $this;
    }

    public function btnLinkPrimary($text, $href = null, $addClass = null, $attr = null)
    {
        echo $this->mkButtonLink($text, $href, self::PRIMARY, $addClass, $attr);
        return $this;
    }

    public function btnLinkSuccess($text, $href = null, $addClass = null, $attr = null)
    {
        echo $this->mkButtonLink($text, $href, self::SUCCESS, $addClass, $attr);
        return $this;
    }

    public function btnLinkDanger($text, $href = null, $addClass = null, $attr = null)
    {
        echo $this->mkButtonLink($text, $href, self::DANGER, $addClass, $attr);
        return $this;
    }

    public function filterHttpRef($url, $ext = null)
    {
        if (empty($url) || !is_string($url))
            return false;
        if (!preg_match('#^((?:([a-z]+)\:)?\/\/)#i', $url)) {
            $url = httpUri($url);
        }
        return $url;
    }

    public function mkLink($href, $rel, $type = null, $attr = null)
    {
        $html = '';
        if (empty($href)) return $html;
        $type = gettype($href);
        if ($type === PHP_STR) {
            $merges = array('rel' => $rel, 'href' => $this->filterHttpRef($href));
            if (!empty($type) && is_string($type))
                $merges['type'] = $type;
            $attr = $this->attr($attr, $merges);
            $html = "<link{$attr} />";
            return $html;
        } else {
            foreach ($href as $item) {
                if (empty($item)) continue;
                $html .= $this->mkLink($item, $rel, $type) . "\r\n";
            }
            return $html;
        }
    }

    public function link($href, $rel, $type = null, $attr = null)
    {
        echo $this->mkLink($href, $rel, $type, $attr);
        return $this;
    }

    public function mkInput($type, $value = null, $attr = null)
    {
        $merge = array('type' => $type, 'value' => String::from($value),);
        $merge['class'] = array($this->inputCls, $this->inputBaseCls);
        $attr = $this->attr($attr, $merge);
        return "<input{$attr} />";
    }

    public function input($type, $value = null, $attr = null)
    {
        echo $this->mkInput($type, $value, $attr);
        return $this;
    }

    public function text($value, $attr = null)
    {
        echo $this->mkInput('text', $value, $attr);
        return $this;
    }

    public function password($value, $attr = null)
    {
        echo $this->mkInput('password', $value, $attr);
        return $this;
    }

    public function hidden($value, $attr = null)
    {
        echo $this->mkInput('hidden', $value, $attr);
        return $this;
    }

    public function mkTextarea($value, $attr = null)
    {
        $value = String::from($value);
        $merge = array(
            'class' => array($this->textareaCls, $this->inputBaseCls),
        );
        $attr = $this->attr($attr, $merge);
        return "<textarea{$attr}>{$value}</textarea>";
    }

    public function textarea($value, $attr = null)
    {
        echo $this->mkTextarea($value, $attr);
        return $this;
    }

    public function mkGroupItems($type = 'radio', $options, $checked = null, $attr = null)
    {
        $checkedField = 'selected';
        if ($type === 'radio' || $type === 'checkbox')
            $checkedField = 'checked';
        // more ...
        $isMultiCheck = false;
        if ($type === 'checkbox' || $type === 'multi-option')
            $isMultiCheck = true;
        $merge = array();
        if ($type === 'checkbox')
            $merge['class'] = array($this->checkboxCls);
        elseif ($type === 'radio')
            $merge['class'] = array($this->radioCls);
        $attr = $this->filterAttr($attr, $merge);
        unset($attr[$checkedField]);
        $html = '';
        $rand = mt_rand(100000, 999999);
        $options = $this->mkOptions($options);
        // 如果是radio模式，为了方式出现没选中的状态出现，默认给一个隐藏域
        if ($type === 'radio') {
            $hiddenAttr = array();
            if (isset($attr['name']))
                $hiddenAttr['name'] = $attr['name'];
            $html .= $this->mkInput('hidden', $checked, $hiddenAttr, false);
        }
        if (!$options->isEmpty()) {
            $isChecked = false;
            $options->each(function ($index, $value, $text, $addData) use (
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
                if (!empty($addData)) {
                    foreach ($addData as $key => $value) {
                        $attr["data[{$key}]"] = $value;
                    }
                }
                $attr = $this->attr($attr);
                if ($checkedField === 'selected')
                    $html .= "<option{$attr}>{$text}</option>";
                else {
                    $labelInlineCls = $type === 'radio' ? $this->radioLabelInlineCls : $this->checkboxLabelInlineCls;
                    $html .= "<label class=\"{$labelInlineCls}\"><input{$attr}/>{$text}</label>";
                }
            });
        }
        if ($type === 'radio' || $type === 'checkbox') {
            if (!empty($this->groupItemTag))
                $html = "<{$this->groupItemTag} class=\"{$this->groupItemCls}\">{$html}</{$this->groupItemTag}>";
        }
        return $html;
    }

    public function radio($options, $checked = null, $attr = null)
    {
        echo $this->mkGroupItems('radio', $options, $checked, $attr);
        return $this;
    }

    public function checkbox($options, $checked = null, $attr = null)
    {
        echo $this->mkGroupItems('checkbox', $options, $checked, $attr);
        return $this;
    }

    public function mkSelect($options, $selected = null, $attr = null, $isMulti = false)
    {
        $merge = array('class' => array($this->selectCls, $this->inputBaseCls));
        $attr = $this->filterAttr($attr, $merge);
        if ($isMulti) {
            $attr['multiple'] = 'multiple';
            if (empty($attr['name']))
                $attr['name'] = 'agi_ui_multi_select_' . mt_rand(100000, 999999) . '[]';
            else
                $attr['name'] .= '[]';
        }
        $attr = $this->mkAttr($attr);
        $options = $this->mkGroupItems($isMulti ? 'multi-option' : 'option', $options, $selected);
        return "<select{$attr}>{$options}</select>";
    }

    public function select($options, $selected = null, $attr = null)
    {
        echo $this->mkSelect($options, $selected, $attr, false);
        return $this;
    }

    public function multiSelect($options, $selected = null, $attr = null)
    {
        echo $this->mkSelect($options, $selected, $attr, true);
        return $this;
    }

    protected function initPaginateOptions(array $options = null)
    {
        if (empty($options))
            $options = $this->paginationOptions;
        else
            $options = array_merge($this->paginationOptions, $options);
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

    public function mkPagination(array $pagination, array $options = null)
    {
        $html = '';
        if (empty($pagination) || !isset($pagination['pageCount']) || !is_numeric($pagination['pageCount']))
            return $html;
        $options = $this->initPaginateOptions($options);

        $baseUri = $options['baseUri'];
        $pageCount = $pagination['pageCount'];
        $pageField = isset($pagination['pageParam']) ? $pagination['pageParam'] : $options['pageParam'];
        $pageNumber = isset($pagination['pageNumber']) ? $pagination['pageNumber'] : (isset($_GET[$pageField]) ? $_GET[$pageField] : 1);
        $recordCount = isset($pagination['recordCount']) ? $pagination['recordCount'] : 1;
        $linkCount = $options['count'];
        $isPolar = $options['isPolar'];
        $isForward = $options['isForward'];
        $jsJump = $options['jsJump'];

        if ($pageNumber <= 0)
            $pageNumber = 1;

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

        $prev = 0;
        $next = 0;
        $class = '';
        $text = '';
        $uri = $baseUri;

        if ($isForward) {
            $prev = $pageNumber - 1;
            $next = $pageNumber + 1;
            if ($next > $pageCount) {
                if ($pageCount === 0)
                    $next = 1;
                else
                    $next = $pageCount;
            }
            if ($prev < 1) $prev = 1;
        }

        $attr = $this->attr(array(
            'class'        => $options['classWrap'],
            'page-params'  => $pageField,
            'page-size'    => isset($pagination['pageSize']) ? $pagination['pageSize'] : 0,
            'page-number'  => $pageNumber,
            'page-count'   => $pageCount,
            'record-count' => $recordCount,
        ), null, null);

        if ($isForward) {
            $class = $options['classPrev'];
            $text = $options['prev'];
            if ($pageNumber === $prev)
                $html .= "<li class=\"{$class} {$options['classDisable']}\"><span>{$text}</span></li>";
            else {
                $uri = linkUri($baseUri, array($pageField => $prev));
                $html .= "<li class=\"{$class}\"><a href=\"{$uri}\">{$text}</a></li>";
            }
        }

        if ($start !== 1) {
            $class = $options['classItem'];
            $text = sprintf($options['item'], 1);
            $uri = linkUri($baseUri, array($pageField => 1));
            $html .= "<li class=\"{$class}\"><a href=\"{$uri}\">{$text}</a></li>";
            if ($start - 1 !== 1)
                $html .= "<li class=\"{$class} {$options['classOmission']}\"><span>{$options['omission']}</span></li>";
        }

        $index = $start;
        while ($index <= $over) {
            $text = sprintf($options['item'], $index);
            $class = $options['classItem'];
            if ($index == $pageNumber) {
                $html .= "<li class=\"{$class} {$options['classActive']}\"><span>{$text}</span></li>";
            } else {
                $uri = linkUri($baseUri, array($pageField => $index));
                $html .= "<li class=\"{$class}\"><a href=\"{$uri}\">{$text}</a></li>";
            }
            $index++;
        }

        if ($over !== $pageCount) {
            $class = $options['classItem'];
            $text = sprintf($options['item'], $pageCount);
            $uri = linkUri($baseUri, array($pageField => $pageCount));

            if ($over + 1 !== $pageCount)
                $html .= "<li class=\"{$class} {$options['classOmission']}\"><span>{$options['omission']}</span></li>";

            $html .= "<li class=\"{$class}\"><a href=\"{$uri}\">{$text}</a></li>";
        }

        if ($isForward) {
            $text = $options['next'];
            $class = $options['classNext'];
            if ($pageNumber === $next)
                $html .= "<li class=\"{$class} {$options['classDisable']}\"><span class=\"{$class}\">{$text}</span></li>";
            else {
                $uri = linkUri($baseUri, array($pageField => $next));
                $html .= "<li class=\"{$class}\"><a href=\"{$uri}\">{$text}</a></li>";
            }
        }

        if ($pageCount > $options['count']) {
            $class = $options['classSelect'];
            $id = 'pagination_select_' . mt_rand(100000, 999999);
            $selectAttr = [
                'id' => $id,
                'class' => 'pagination-select',
                'page-count' => $pageCount,
                'page-number'  => $pageNumber,
                'data-text' => '第{page}页',
                'data-url' => linkUri($baseUri, array($pageField => 0)),
            ];
            $selectAttr = $this->mkAttr($selectAttr);
            $html .= "<li class=\"{$class} hd\"><label><select{$selectAttr}></select></label></li>";
        }

        $html = "<div{$attr}><ul class=\"{$options['classUl']}\">{$html}</ul></div>";
        return $html;
    }

    public function paginate(array $pagination, array $options = null)
    {
        echo $this->mkPagination($pagination, $options);
        return $this;
    }

    public function mkTip($message, $type = self::MUTED, $addClass = null)
    {
        $class = isset($this->tipTypes[$type]) ? $this->tipTypes[$type] : '';
        if (!empty($addClass))
            $class = $this->pushClass($class, $addClass);
        $message = String::from($message);
        return "<div class=\"tip-item {$class}\">{$message}</div>";
    }

    public function tip($message, $type = self::MUTED, $addClass = null)
    {
        echo $this->mkTip($message, $type, $addClass);
        return $this;
    }

    public function muted($message, $addClass = null)
    {
        echo $this->mkTip($message, self::MUTED, $addClass);
        return $this;
    }

    public function primary($message, $addClass = null)
    {
        echo $this->mkTip($message, self::PRIMARY, $addClass);
        return $this;
    }

    public function success($message, $addClass = null)
    {
        echo $this->mkTip($message, self::SUCCESS, $addClass);
        return $this;
    }

    public function info($message, $addClass = null)
    {
        echo $this->mkTip($message, self::INFO, $addClass);
        return $this;
    }

    public function warning($message, $addClass = null)
    {
        echo $this->mkTip($message, self::WARNING, $addClass);
        return $this;
    }

    public function danger($message, $addClass = null)
    {
        echo $this->mkTip($message, self::DANGER, $addClass);
        return $this;
    }

    public function mkHighlight($message, $type = self::PRIMARY, $addClass = null)
    {
        $class = isset($this->highlightTypes[$type]) ? $this->highlightTypes[$type] : '';
        if (!empty($addClass))
            $class = $this->pushClass($class, $addClass);
        $message = String::from($message);
        return "<div class=\"{$class}\">{$message}</div>";
    }

    public function hl($message, $type = self::PRIMARY, $addClass = null)
    {
        echo $this->mkHighlight($message, $type, $addClass);
        return $this;
    }

    public function hlPrimary($message, $addClass = null)
    {
        echo $this->mkHighlight($message, self::PRIMARY, $addClass);
        return $this;
    }

    public function hlSuccess($message, $addClass = null)
    {
        echo $this->mkHighlight($message, self::SUCCESS, $addClass);
        return $this;
    }

    public function hlInfo($message, $addClass = null)
    {
        echo $this->mkHighlight($message, self::INFO, $addClass);
        return $this;
    }

    public function hlWarning($message, $addClass = null)
    {
        echo $this->mkHighlight($message, self::WARNING, $addClass);
        return $this;
    }

    public function hlDanger($message, $addClass = null)
    {
        echo $this->mkHighlight($message, self::DANGER, $addClass);
        return $this;
    }

    public function getFormModeCls($mode = null)
    {
        return $this->mkClass('af-form', $mode);
    }

    public function getFormTagPair($attr, $mode = null)
    {
        $formAttr = $this->formAttr;
        $formAttr['class'] = array('af-form', $this->formCls, $this->getFormModeCls($mode));
        $attr = $this->attr($attr, $formAttr);
        $html = array("<div class=\"{$this->formWrapCls} {$this->clearCls}\"><form{$attr}>", "</form></div>");
        return $html;
    }

    public function getFormRowModeCls($mode)
    {
        return $this->mkClass('af-row', strtr($mode, '/', '-'));
    }

    public function getFormRowTagPair(Form $form, $field, $mode = null)
    {
        $index = $form->getRenderIndex();
        $error = $form->getError($field);
        $rowClass = "{$this->clearCls} af-row {$this->formRowCls}";
        if ($mode !== 'hidden') {
            $rowClass .= ' af-row-' . ($index + 1);
            $rowClass .= ' af-row' . ($index % 2 === 0 ? '-odd' : '-even');
        }
        $rowClass .= ' ' . $this->getFormRowModeCls($mode);
        if ($error !== false)
            $rowClass .= ' ' . $this->formRowErrorCls;
        if ($mode === 'submit') {
            return array(
                "<div class=\"{$rowClass}\" field=\"{$field}\"><div class=\"af-body {$this->formSingleRowCls}\">",
                '</div></div>'
            );
        }
        return array("<div class=\"{$rowClass}\" field=\"{$field}\">", '</div>');
    }

    public function mkFormHead(Form $form, $mode, $field, $attr, $isRequire = false)
    {
        $colon = $form->getColon();
        $label = $form->getTitle($field);
        $forId = $attr['id'];
        if ($mode === 'radio' || $mode === 'checkbox')
            $forId = '';
        $reqCls = '';
        if ($isRequire)
            $reqCls .= "af-require {$this->formRequireCls}";
        if (!empty($colon))
            $label .= "<span class=\"af-colon {$this->formColonCls}\">{$colon}</span>";
        $label = "<div class=\"af-head {$this->formHeadCls}\"><label class=\"af-label {$this->formLabelCls} {$reqCls}\" for=\"{$forId}\">{$label}</label></div>";
        return $label;
    }

    public function getFormBodyTagPair(Form $form, $mode, $field, array $attr, array $column)
    {
        return array("<div class=\"af-body {$this->formBodyCls}\">", '</div>');
    }

    public function getFormHelperPair()
    {
        return array("<div class=\"af-helper {$this->formHelperCls}\">", '</div>');
    }

    public function formColumnHelper(Form $form, $field)
    {
        $pair = $this->getFormHelperPair();
        echo $pair[0];
        $tip = $form->getTip($field);
        $error = $form->getError($field);
        if (!empty($error))
            $this->danger($error, $this->formErrCls);
        elseif (!empty($tip))
            $this->muted($tip, $this->formTipCls);

        echo $pair[1];
        return $this;
    }

    public function getInputWrapPair()
    {
        return array("<div class=\"af-input-wrap {$this->inputWrapCls} {$this->clearCls}\">", '</div>');
    }

    public function formRow(Form $form, $field, array $column = null, $onlyInput = false)
    {
        if (!isset($column))
            $column = $form->getColumn($field);
        if (empty($column) || empty($column[0])) return $this;
        $mode = $column[0];
        $prefix = $form->getPrefix();
        $attr = $this->filterAttr(array(
            'mkAll' => array($prefix, $field),
            //            'class'  => $this->mkClass('af', strtr($mode, '/', '-')),
            'title' => $form->getTitle($field),
        ));
        $value = $form->getValue($field);
        $error = $form->getError($field);
//        if ($mode === 'hidden')
//            $onlyInput = true;
        if (!empty($column['tip']))
            $attr['tip'] = $column['tip'];
        if ($error !== false)
            $attr['error'] = $error;

        $this->filterFormColumnAttr($mode, $column, $attr);
        $isShowInputWrap = true;
        $isAfter = !empty($column['after']);
//        if ($mode === 'hidden')
//            $isShowInputWrap = false;

        ###################################################
        $rowPair = array();
        $bodyPair = array();
        $inputWrapPair = array();

        if (!$onlyInput) {
            $rowPair = $this->getFormRowTagPair($form, $field, $mode);
            $bodyPair = $this->getFormBodyTagPair($form, $field, $mode, $attr, $column);
            echo $rowPair[0];
            echo $this->mkFormHead($form, $mode, $field, $attr, !empty($column['require']));
            echo $bodyPair[0];
        }

        if ($isShowInputWrap) {
            $inputWrapPair = $this->getInputWrapPair();
            echo $inputWrapPair[0];
        }

        $isShowHelper = true;

        if ($mode === 'hidden') {
            $isShowHelper = false;
            $isAfter = false;
            $this->hidden($value, $attr);
        } elseif ($mode === 'static') {
            $isShowHelper = false;
            $this->tag('span', $value, array(
                'class' => "af-static {$this->inputReadOnlyCls}",
            ));
//            $attr['readonly'] = true;
//            $attr['class'] = $this->pushClass($attr['class'], $this->inputReadOnlyCls);
//            $this->input('text', $value, $attr);
        } elseif ($mode === 'text' || $mode === 'password' || $mode === 'file' || $mode === 'textarea') {
            if (!isset($attr['placeholder']))
                $attr['placeholder'] = $attr['title'];
            if ($mode === 'textarea')
                $this->textarea($value, $attr);
            else
                $this->input($mode, $value, $attr);
        } elseif ($mode === 'select' || $mode === 'multi-select') {
            $options = $this->mkOptionsByColumn($column);
            if ($mode === 'select')
                $this->select($options, $value, $attr);
            elseif ($mode === 'multi-select')
                $this->multiSelect($options, $value, $attr);
        } elseif ($mode === 'radio' || $mode === 'checkbox') {
            $options = $this->mkOptionsByColumn($column);
            if ($mode === 'radio')
                $this->radio($options, $value, $attr);
            else
                $this->checkbox($options, $value, $attr);
        } else {
            $isAfter = false;
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

        if ($isAfter) {
            echo "<span class=\"af-after\">{$column['after']}</span>";
        }

        if ($isShowInputWrap) {
            echo $inputWrapPair[1];
        }

        if ($isShowHelper)
            $this->formColumnHelper($form, $field);

        if (!$onlyInput) {
            echo $bodyPair[1];
            echo $rowPair[1];

        }

        if ($mode !== 'hidden')
            $form->addRenderIndex();
        ###################################################

        return $this;
    }

    public function formScript(Form $form)
    {
        ?>
        <script
            type="text/javascript">typeof EasyForm === 'function' && EasyForm('<?php echo $form->getId() ?>');</script><?php
    }

    public function filterFormColumnAttr($mode, array $column, array & $attr)
    {
        if (!empty($column['require']))
            $attr['require'] = 1;
        if (!empty($column['pattern']))
            $attr['regex'] = $column['pattern'];
        if (!empty($column['max']) && is_numeric($column['max'])) {
            $attr['maxlength'] = $column['max'];
            $attr['class'] = $this->pushClass($attr['class'], 'af-max-w-' . $column['max']);
        }
        if (!empty($column['min']) && is_numeric($column['min'])) {
            $attr['minlength'] = $column['min'];
            $attr['class'] = $this->pushClass($attr['class'], 'af-min-w-' . $column['min']);
        }
        if (!empty($column['int']))
            $attr['class'] = $this->pushClass($attr['class'], 'af-int');
        if (!empty($column['float']))
            $attr['class'] = $this->pushClass($attr['class'], 'af-float');
        if (!empty($column['readonly']))
            $attr['readonly'] = 'readonly';
        if (!empty($column['disabled']))
            $attr['disabled'] = 'disabled';
        if (isset($column['placeholder']))
            $attr['placeholder'] = $column['placeholder'];
    }

    public function mkSubmitButton($text)
    {
        return "<button class=\"{$this->submitButtonCls}\" type=\"submit\">{$text}</button>";
    }

    public function emptyData($text = null)
    {
        if (empty($text))
            $text = App::getLang()->get('table.empty_data');
        echo "<div class=\"empty-data {$this->emptyDataCls}\">{$text}</div>";
        return $this;
    }

    public function getTablePair(Table $table, $addClass = null)
    {
        $deleteButtonId = $table->getDeleteButtonId();
        $class = 'at-table ' . $this->tableCls;
        if (!empty($addClass))
            $class = $this->pushClass($class, $addClass);
        $id = $table->getId();
        if ($table->hasBorder())
            $class .= " {$this->tableBorderCls}";
        if ($table->hasHoverEffect())
            $class .= " {$this->tableHoverCls}";
        $tableAttr = $this->mkAttr($this->tableAttr);
        return array(
            "<div class=\"at-table-wrap {$this->tableWrapCls}\"><table id=\"{$id}\" delete-button=\"{$deleteButtonId}\" class=\"{$class}\" {$tableAttr}>",
            '</table></div>',
        );
    }

    public function tableHead(Table $table, array $showColumns)
    {
        echo '<thead>';
        if (empty($showColumns)) {
            echo '<tr><th>', App::getLang()->get('table.empty_columns'), '</th></tr>';
        } else {
            $sortFields = $table->getSortFields();
            $hideColumns = $table->getHideColumns();
            $hasCheckbox = $table->hasCheckbox();
            $hasRowTail = $table->hasRowTail();
            $prefix = $table->getPrefix();
            $html = null;
            $id = $table->getId();
            foreach ($showColumns as $field) {
                if (isset($hideColumns[$field]))
                    continue;
                $sort = '';
                $sortClass = '';
                $sortArrow = '';
                if (isset($sortFields[$field])) {
//                    $sort = ' data-sort="' . $sortFields[$field] . '" data-tablesaw-sortable-col';
                    $sort = ' data-tablesaw-sortable-col';
                    $sortClass = 'at-sort-cell ' . $this->tableSortCls;
//                    $sortArrow = "<span class=\"at-sort-asc\">{$this->tableSortAsc}</span><span class=\"at-sort-desc\">{$this->tableSortDesc}</span><span class=\"at-sort-placeholder\">{$this->tableSortText}</span>";
                    $sortArrow = "<span class=\"at-sort-placeholder\">{$this->tableSortText}</span>";
                }
                $title = $table->getColumnTitle($field);
                $class = "at-cell {$this->tableCellCls} " . $this->mkClass($prefix, $field) . '-cell';
                $html .= "<th class=\"{$class} {$sortClass}\"{$sort}>{$title}{$sortArrow}</th>";
            }
            if ($hasCheckbox) {
                $html = "<th class=\"{$this->tableCellCls} at-checkbox-cell {$this->tableCheckboxCellCls}\"><input type=\"checkbox\" id=\"{$id}_check_all\" class=\"{$this->checkboxCls}\" data-target=\"#{$id}\" /></th>{$html}";
            }
            if ($hasRowTail)
                $html .= "<th class=\"{$this->tableCellCls} at-tail-cell {$this->tableTailCellCls}\">" . App::getLang()->get('table.row_tail_title') . "</th>";
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
            $hideColumns = $table->getHideColumns();
            $sortFields = $table->getSortFields();
            $checkboxField = $table->getCheckboxField();
            $hasRowTail = $table->hasRowTail();
            $allHtml = null;
            $prefix = $table->getPrefix();
            $id = $table->getId();
            $columnClasses = array();
            $columns = $table->getColumns();
            // 这里暂时先这么处理，以后再优化吧
            if ($data instanceof SortAsTree) {
                $data->sort(function ($tree, $index, $row, $depth) use (
                    $table,
                    $checkboxField,
                    $hasRowTail,
                    & $allHtml,
                    $prefix,
                    $id,
                    & $columnClasses,
                    $columns,
                    $showColumns,
                    $hideColumns,
                    $sortFields
                ) {
                    $rowClass = 'at-row ' . $this->tableRowCls;
                    $rowClass .= ' at-row-' . ($index + 1);
                    $rowClass .= ' at-row' . (($index % 2 === 0) ? '-odd' : '-even');
                    $html = "<tr class=\"{$rowClass}\">";
                    $row['sort_tree_depth'] = $depth;
                    $row['sort_tree_index'] = $index;
                    $clone = $table->callOnRow($row);
                    if ($checkboxField !== false) {
                        if (isset($row[$checkboxField])) {
                            $checkboxValue = $row[$checkboxField];
                            $html .= "<td class=\"{$this->tableCellCls} at-checkbox-cell {$this->tableCheckboxCellCls}\"><input type=\"checkbox\" id=\"{$id}_{$checkboxField}_{$checkboxValue}\" class=\"{$this->checkboxCls}\" check-field=\"{$checkboxField}\" value=\"{$checkboxValue}\" /></td>";
                        } else {
                            $html .= "<td class=\"{$this->tableCellCls} at-checkbox-cell {$this->tableCheckboxCellCls}\"></td>";
                        }
                    }
                    foreach ($showColumns as $field) {
                        if (isset($hideColumns[$field]))
                            continue;
                        $value = isset($clone[$field]) ? $clone[$field] : '';
                        $sort = '';
                        $sortClass = '';
                        if (isset($sortFields[$field])) {
                            $sortValue = isset($row[$field]) ? $row[$field] : $value;
                            $sortField = "{$field}_sort_value";
                            if (isset($clone[$sortField]))
                                $sortValue = $clone[$sortField];
                            $sortClass = 'at-sort-cell';
                            $sort = ' data-sort-value="' . $sortValue . '"';
                        }
                        if (!empty($columns[$field]['timestamp'])) {
                            if (!empty($value))
                                $value = date(FORMAT_DATETIME, $value);
                            else
                                $value = '';
                        } else {
                            $optionValue = $table->getModelColumnOptionValue($field, $value);
                            if ($optionValue !== false)
                                $value = $optionValue;
                        }
                        if (!isset($columnClasses[$field])) {
                            $class = $this->tableCellCls;
                            $class .= ' ' . $this->mkClass($prefix, $field) . '-cell';
                            $columnClasses[$field] = $class;
                        }
                        $html .= "<td class=\"{$columnClasses[$field]} {$sortClass}\"{$sort}>{$value}</td>";
                    }
                    if ($hasRowTail) {
                        $tail = $table->callRowTail($row);
                        $type = gettype($tail);
                        if ($type === PHP_ARY) {
                            $tail = implode('', $tail);
                        } elseif ($type === PHP_OBJ) {
                            $tail = '';
                        }
                        $html .= '<td class="af-cell at-tail-cell' . $this->tableCellCls . ' ' . $this->tableTailCellCls . '">' . $tail . '</td>';
                    }

                    $html .= '</tr>';
                    $allHtml .= $html;
                });
            } else {
                foreach ($data as $index => $row) {
                    $rowClass = 'at-row ' . $this->tableRowCls;
                    $rowClass .= ' at-row-' . ($index + 1);
                    $rowClass .= ' at-row' . (($index % 2 === 0) ? '-odd' : '-even');
                    $html = "<tr class=\"{$rowClass}\">";
                    $clone = $table->callOnRow($row);
                    if ($checkboxField !== false) {
                        if (isset($row[$checkboxField])) {
                            $checkboxValue = $row[$checkboxField];
                            $html .= "<td class=\"{$this->tableCellCls} at-checkbox-cell {$this->tableCheckboxCellCls}\"><input type=\"checkbox\" id=\"{$id}_{$checkboxField}_{$checkboxValue}\" class=\"{$this->checkboxCls}\" check-field=\"{$checkboxField}\" value=\"{$checkboxValue}\" /></td>";
                        } else {
                            $html .= "<td class=\"{$this->tableCellCls} at-checkbox-cell {$this->tableCheckboxCellCls}\"></td>";
                        }
                    }
                    foreach ($showColumns as $field) {
                        if (isset($hideColumns[$field]))
                            continue;
                        $value = isset($clone[$field]) ? $clone[$field] : '';
                        $sort = '';
                        $sortClass = '';
                        if (isset($sortFields[$field])) {
                            $sortValue = isset($row[$field]) ? $row[$field] : $value;
                            $sortField = "{$field}_sort_value";
                            if (isset($clone[$sortField]))
                                $sortValue = $clone[$sortField];
                            $sortClass = 'at-sort-cell';
                            $sort = ' data-sort-value="' . $sortValue . '"';
                        }
                        if (!empty($columns[$field]['timestamp'])) {
                            if (!empty($value))
                                $value = date(FORMAT_DATETIME, $value);
                            else
                                $value = '';
                        } else {
                            $optionValue = $table->getModelColumnOptionValue($field, $value);
                            if ($optionValue !== false)
                                $value = $optionValue;
                        }
                        if (!isset($columnClasses[$field])) {
                            $class = $this->tableCellCls;
                            $class .= ' ' . $this->mkClass($prefix, $field) . '-cell';
                            $columnClasses[$field] = $class;
                        }
                        $html .= "<td class=\"{$columnClasses[$field]} {$sortClass}\"{$sort}>{$value}</td>";
                    }
                    if ($hasRowTail) {
                        $tail = $table->callRowTail($row);
                        $type = gettype($tail);
                        if ($type === PHP_ARY) {
                            $tail = implode('', $tail);
                        } elseif ($type === PHP_OBJ) {
                            $tail = '';
                        }
                        $html .= '<td class="af-cell at-tail-cell' . $this->tableCellCls . ' ' . $this->tableTailCellCls . '">' . $tail . '</td>';
                    }

                    $html .= '</tr>';
                    $allHtml .= $html;
                }
            }
            echo $allHtml;
        }
        echo '</tbody>';
    }

    public function tableScript(Table $table)
    {
        ?>
        <script
            type="text/javascript">typeof EasyTable === 'function' && EasyTable('<?php echo $table->getId() ?>');</script><?php
    }
}

