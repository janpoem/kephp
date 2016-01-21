<?php

namespace Agi\Html\Builder;

use Agi\Html\Form;
use Agi\Util\String;

/**
 * Class UIKit
 *
 * @package Agi\Html\Builder
 * @author Janpoem created at 2014/10/13 4:19
 */
class UIKit extends Html
{

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
        'classUl'       => 'pagination-list uk-pagination',
        'classItem'     => 'pagination-item',
        'classActive'   => 'uk-active',
        'classDisable'  => 'uk-disable',
        'classPrev'     => 'pagination-item pagination-prev pagination-forward',
        'classNext'     => 'pagination-item pagination-next pagination-forward',
        'classOmission' => 'pagination-item pagination-omission',
        'classSelect'   => 'pagination-item pagination-select-wrap uk-form',
    );

    public $tipTypes = array(
        self::MUTED   => 'uk-text-muted',
        self::PRIMARY => 'uk-text-primary',
        self::SUCCESS => 'uk-text-success',
//        self::INFO    => 'uk-text-info',
        self::WARNING => 'uk-text-warning',
        self::DANGER  => 'uk-text-danger',
    );

    public $highlightTypes = array(
        self::PRIMARY => 'uk-alert',
        self::SUCCESS => 'uk-alert uk-alert-success',
//        self::INFO    => 'bg-info',
        self::WARNING => 'uk-alert uk-alert-warning',
        self::DANGER  => 'uk-alert uk-alert-danger',
    );

    public $buttonTypes = array(
        self::MUTED   => '',
        self::PRIMARY => 'uk-button-primary',
        self::SUCCESS => 'uk-button-success',
        self::DANGER  => 'uk-button-danger',
    );

    public $buttonCls = 'uk-button';

    public $clearCls = 'uk-clearfix';

    public $inputBaseCls = '';

    public $inputReadOnlyCls = 'uk-form-blank';

    public $submitButtonCls = 'uk-button uk-button-primary';

    public $tableCls = 'uk-table uk-table-striped';

    public $tableWrapCls = '';

    public $tableBorderCls = 'table-bordered';

    public $tableHoverCls = 'uk-table-hover';

    public $formRowErrorCls = 'uk-form-danger';

    public $formTipCls = '';

    public $formErrCls = '';

    public $emptyDataCls = 'uk-aliert uk-alert-warning uk-alert-large';

    public $formCls = 'uk-form';

    public $formRowCls = 'uk-form-row uk-grid';

    public $formLabelCls = 'uk-form-label';

    public $formHeadCls = '';

    public $formBodyCls = '';

    public $formSingleRowCls = '';

    public $formHelperCls = 'uk-clearfix';

    public $radioLabelInlineCls = 'radio-inline';

    public $checkboxLabelInlineCls = 'checkbox-inline';

    public $tableSortAsc = '<span class="uk-icon-sort-asc"></span>';

    public $tableSortDesc = '<span class="uk-icon-sort-desc"></span>';

    public $tableSortText = '<span class="uk-icon-sort"></span>';

    public $iconBaseCls = 'fa';

    public $iconClsPrefix = 'fa';

//    public function mkInput($type, $value = null, $attr = null)
//    {
//        $html = parent::mkInput($type, $value, $attr);
//        if ($type === 'password') {
//            $html .= '<span class="uk-form-password-toggle" data-uk-form-password><i class="uk-icon-eye"></i></span>';
//            $html = "<div class=\"uk-form-password\">{$html}</div>";
//        }
//        return $html;
//    }


//    public function password() {
//        // <a href="" class="uk-form-password-toggle" data-uk-form-password>...</a>
//    }

//    public $formRequireCls = 'uk-icon-asterisk';


//    public $formCls = 'uk-form';
//
//    public $inputErrCls = 'uk-form-danger';
//
//    public $formRowCls = 'uk-form-row';
//
//    public $formRowErrorCls = 'has-error has-feedback';
//
//    public $formTipCls = 'uk-form-help-inline';
//
//    public $formErrorCls = 'uk-text-danger';
//
//    public $formLabelCls = 'uk-form-label';
//
//    public $submitButtonCls = 'uk-button uk-button-primary';
//
//    public $tableWrapCls = 'uk-overflow-container';
//
//    public $tableCls = 'uk-table uk-table-striped uk-table-condensed"';
//
//    public $tableBorderCls = '';
//
//    public $tableHoverCls = 'uk-table-hover';
//
//    public function getFormModeClass($mode = Form::HORIZONTAL)
//    {
//        return 'uk-form-' . $mode;
//    }
//
//    public function formHead(Form $form, $label, $forId, $colon, $isRequire = false)
//    {
//        $formMode = $form->getMode();
//        $headWidthColumn = $form->getHeadWidthColumn();
//        if (!empty($colon))
//            $label .= "<span class=\"af-colon\">{$colon}</span>";
//        if ($isRequire)
//            $label .= '<span class="af-require"></span>';
//        $cls = $this->formLabelCls;
////        if ($formMode === Form::HORIZONTAL) {
////            $cls .= ' col-sm-' . $headWidthColumn;
////        }
//        $label = "<label class=\"{$cls}\" for=\"{$forId}\">{$label}</label>";
//        echo $label;
//    }
//
//    public function formBodyStart(Form $form, $error = null)
//    {
//        echo '<div class="uk-form-controls">';
////        $formMode = $form->getMode();
////        $headWidthColumn = $form->getHeadWidthColumn();
////        if ($formMode === Form::HORIZONTAL) {
////            $class = ' col-sm-' . (12 - $headWidthColumn);
////            echo "<div class=\"{$class}\">";
////        }
//    }
//
//    public function formBodyClose(Form $form)
//    {
//        echo '</div>';
////        $formMode = $form->getMode();
////        if ($formMode === Form::HORIZONTAL) {
////            echo "</div>";
////        }
//    }
//
//    public function paginate(array $pagination, array $options = null, $isOutput = true)
//    {
//        if (empty($pagination) || !isset($pagination['pageCount']) || !is_numeric($pagination['pageCount']))
//            return null;
//        if ($options === true || $options === 1 || $options === false || $options === 0) {
//            $isOutput = intval($options);
//            $options = null;
//        }
//        $options = static::initPaginateOptions($options);
//
//        $baseUri = $options['baseUri'];
//        $pageCount = $pagination['pageCount'];
//        $pageNumber = $pagination['pageNumber'];
//        $recordCount = $pagination['recordCount'];
//        $linkCount = $options['count'];
//        $isPolar = $options['isPolar'];
//        $isForward = $options['isForward'];
//
//        if ($pageCount > $linkCount) {
//            $half = (int)($linkCount / 2) - 1;
//            $start = $pageNumber - $half;
//            if ($start < 1) $start = 1;
//            $over = $start + $linkCount - ($isPolar ? ($start == 1 ? 2 : 3) : 1);
//            if ($over > $pageCount) {
//                $over = $pageCount;
//                $start = $over - ($linkCount - ($isPolar ? ($over == $pageCount ? 2 : 3) : 1));
//                if ($start <= 1) $start = 1;
//            }
//        } else {
//            $start = 1;
//            $over = $pageCount;
//        }
//
//        $html = array();
//        $prev = 0;
//        $next = 0;
//        $pageField = 'page';
//        $class = '';
//        $text = '';
//        $uri = $baseUri;
//        if ($isForward) {
//            $prev = $pageNumber - 1;
//            $next = $pageNumber + 1;
//            if ($next > $pageCount) $next = $pageCount;
//            if ($prev < 1) $prev = 1;
//        }
//
//        $attr = $this->attr(array(
//            'class'        => $options['classWrap'],
//            'page-size'    => $pagination['pageSize'],
//            'page-number'  => $pageNumber,
//            'page-count'   => $pageCount,
//            'record-count' => $recordCount,
//        ), null, null);
//        $html[] = "<div{$attr}><ul class=\"uk-pagination\">";
//
//        if ($isForward) {
//            $class = $options['classPrev'];
//            $text = $options['prev'];
//            $liClass = '';
//            if ($pageNumber === $prev)
//                $liClass = 'uk-disabled';
//            $uri = linkUri($baseUri, array($pageField => $prev));
//            $html[] = "<li class=\"{$liClass}\"><a href=\"{$uri}\" class=\"{$class}\">{$text}</a></li>";
//
//        }
//
//        if ($start !== 1) {
//            $class = $options['classItem'];
//            $text = sprintf($options['item'], 1);
//            $uri = linkUri($baseUri, array($pageField => 1));
//            $html[] = "<li><a href=\"{$uri}\" class=\"{$class}\">{$text}</a></li>";
//            if ($start - 1 !== 1)
//                $html[] = "<li><span class=\"{$options['classOmission']}\">{$options['omission']}</span></li>";
//        }
//
//        $index = $start;
//        while ($index <= $over) {
//            $text = sprintf($options['item'], $index);
//            $class = $index === $pageNumber ? $options['classCurrent'] : $options['classItem'];
//            $liClass = '';
//            if ($index == $pageNumber)
//                $liClass .= ' uk-active';
//            $uri = linkUri($baseUri, array($pageField => $index));
//            $html[] = "<li class=\"{$liClass}\"><a href=\"{$uri}\" class=\"{$class}\">{$text}</a></li>";
//            $index++;
//        }
//
//        if ($over !== $pageCount) {
//            if ($over + 1 !== $pageCount)
//                $html[] = "<li><span class=\"{$options['classOmission']}\">{$options['omission']}</span></li>";
//
//            $class = $options['classItem'];
//            $text = sprintf($options['item'], $pageCount);
//            $uri = linkUri($baseUri, array($pageField => $pageCount));
//
//            $html[] = "<li><a href=\"{$uri}\" class=\"{$class}\">{$text}</a></li>";
//        }
//
//        if ($isForward) {
//            $text = $options['next'];
//            $class = $options['classNext'];
//            $liClass = '';
//            if ($pageNumber === $next)
//                $liClass = 'uk-disabled';
//            $uri = linkUri($baseUri, array($pageField => $next));
//            $html[] = "<li class=\"{$liClass}\"><a href=\"{$uri}\" class=\"{$class}\">{$text}</a></li>";
//        }
//
//        $html[] = '</ul></div>';
//
//        $html = implode('', $html);
//
//        if ($isOutput) {
//            echo $html;
//            return $this;
//        } else {
//            return $html;
//        }
//    }
}

