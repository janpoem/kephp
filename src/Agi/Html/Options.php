<?php

namespace Agi\Html;


/**
 * Class Options
 *
 * 这个不是专指select的option组合，而是指Options[key,value]数据组合
 *
 * Options装载多个[key,value]，用于生成select的options，radio或checkbox组合
 *
 * @package Agi\Html
 * @author Janpoem created at 2014/10/2 11:42
 */
class Options
{
    protected $type = 'map';

    protected $options = array();

    protected $firstOption = '';

    protected $firstValue = '';

    protected $valueField;

    protected $textField;

    protected $attrFields = array();

    /**
     * @param $options
     * @param $valueField
     * @param $textField
     * @param null $firstOption
     * @param null $firstValue
     * @return Options
     */
    public static function createByList($options, $valueField, $textField, $firstOption = null, $firstValue = null)
    {
        $class = get_called_class();
        /** @var Options $options */
        $options = new $class($options, $firstOption, $firstValue);
        return $options->asList($valueField, $textField);
    }

    /**
     * @param $options
     * @param null $firstOption
     * @param null $firstValue
     * @return Options
     */
    public static function create($options, $firstOption = null, $firstValue = null)
    {
        $class = get_called_class();
        /** @var Options $options */
        return new $class($options, $firstOption, $firstValue);
    }

    protected function __construct($options, $firstOption = null, $firstValue = null)
    {
        if (is_object($options)) {
            $this->options = $options;
        } else if (is_array($options) && !empty($options))
            $this->options = $options;
        if (!empty($firstOption) && is_string($firstOption))
            $this->firstOption = $firstOption;
        if (!empty($firstValue) && is_string($firstValue))
            $this->firstValue = $firstValue;
    }

    public function asList($valueField, $textField)
    {
        if (!empty($valueField) && !empty($textField) &&
            isset($this->options[0][$valueField]) && isset($this->options[0][$textField])
        ) {
            $this->type = 'list';
            $this->valueField = $valueField;
            $this->textField = $textField;
        }
        return $this;
    }

    public function each(callable $fn = null)
    {
        if (!$fn)
            return $this;
        $addData = array();
        if (!empty($this->firstOption))
            call_user_func($fn, 0, $this->firstValue, $this->firstOption, $addData);
        if (!empty($this->options)) {
            $i = 1;
            foreach ($this->options as $index => $option) {
                if ($this->type === 'list') {
                    if (is_object($option))
                        $data = get_object_vars($option);
                    elseif (is_array($option))
                        $data = $option;
                    if (!empty($this->attrFields))
                        $addData = array_intersect_key($data, $this->attrFields);
                    call_user_func($fn, $i, $data[$this->valueField], $data[$this->textField], $addData);
                }
                else
                    call_user_func($fn, $i, $index, $option, $addData);
                ++$i;
            }
        }
        return $this;
    }

    public function isEmpty()
    {
        return count($this->options) <= 0 && empty($this->firstOption);
    }

    public function setAttrFields()
    {
        if ($this->type === 'list') {
            $addFields = array();
            foreach (func_get_args() as $field) {
                if (isset($this->options[0][$field]))
                    $addFields[$field] = $field;
            }
            $this->attrFields = $addFields;
        }
        return $this;
    }
}
 