<?php

namespace Agi\Html\Builder;

use Agi\Html\Form;

/**
 * Class Bootstrap
 *
 * @package Agi\Html\Builder
 * @author Janpoem created at 2014/10/11 14:29
 */
class Bootstrap extends Html
{

    public $paginationOptions = array(
        'baseUri'       => null,
        'pageParam'     => 'page',
        'count'         => 9, # 定义分页的link的数量
        'isForward'     => true, # 是否有导向按钮 定义是否出现上一页、下一页
        'isPolar'       => true, # 是否有两极按钮 定义是否出现第一页、最后一页
        'jsJump'        => true,
        'item'          => '%s',
        'prev'          => null,
        'next'          => null,
        'omission'      => '...',
        'classWrap'     => 'pagination-box',
        'classUl'       => 'pagination-list pagination',
        'classItem'     => 'pagination-item',
        'classActive'   => 'active',
        'classDisable'  => 'disable',
        'classPrev'     => 'pagination-item pagination-prev pagination-forward',
        'classNext'     => 'pagination-item pagination-next pagination-forward',
        'classOmission' => 'pagination-item pagination-omission'
    );

    public $tipTypes = array(
        self::MUTED   => 'text-muted',
        self::PRIMARY => 'text-primary',
        self::SUCCESS => 'text-success',
        self::INFO    => 'text-info',
        self::WARNING => 'text-warning',
        self::DANGER  => 'text-danger',
    );

    public $highlightTypes = array(
        self::PRIMARY => 'bg-primary',
        self::SUCCESS => 'bg-success',
        self::INFO    => 'bg-info',
        self::WARNING => 'bg-warning',
        self::DANGER  => 'bg-danger',
    );

    public $clearCls = 'clearfix';

    public $inputBaseCls = 'form-control';

    public $submitButtonCls = 'btn btn-primary';

    public $tableCls = 'table table-condensed table-striped';

    public $tableWrapCls = 'table-responsive';

    public $tableBorderCls = 'table-bordered';

    public $tableHoverCls = 'table-hover';

    public $formRowErrorCls = 'has-error has-feedback';

    public $formTipCls = '';

    public $formErrCls = '';

    public $emptyDataCls = 'well well-lg';

    public $formCls = 'container-fluid';

    public $formRowCls = 'form-group';

    public $formLabelCls = 'control-label';

    public $formHeadCls = 'col-sm-1';

    public $formBodyCls = 'clearfix col-sm-11';

    public $formSingleRowCls = 'col-sm-11 col-sm-offset-1';

    public $formHelperCls = 'clearfix';

    public $radioLabelInlineCls = 'radio-inline';

    public $checkboxLabelInlineCls = 'checkbox-inline';


//    protected $paginateOptions = array(
//        'baseUri'       => null,
//        'count'         => 9, # 定义分页的link的数量
//        'isForward'     => true, # 是否有导向按钮 定义是否出现上一页、下一页
//        'isPolar'       => true, # 是否有两极按钮 定义是否出现第一页、最后一页
//        'item'          => '%s',
//        'prev'          => null,
//        'next'          => null,
//        'omission'      => '...',
//        'classWrap'     => 'paginate-box',
//        'classItem'     => 'paginate-item',
//        'classCurrent'  => 'paginate-item paginate-curr',
//        'classPrev'     => 'paginate-item paginate-prev paginate-forward',
//        'classNext'     => 'paginate-item paginate-next paginate-forward',
//        'classOmission' => 'paginate-item paginate-omission'
//    );
//
//    public $formAttr = array(
//        'role' => 'form',
//    );
//
//    public $formCls = 'container-fluid';
//
//    public $inputCls = 'form-control';
//
//    public $formRowCls = 'form-group';
//
//    public $formRowErrorCls = 'has-error has-feedback';
//
//    public $formTipCls = 'help-block';
//
//    public $formErrorCls = 'help-block';
//
//    public $formLabelCls = 'control-label';
//
//    public $submitButtonCls = 'btn btn-primary';
//
//    public $tableCls = 'table table-striped';
//
//    public $tableBorderCls = 'table-bordered';
//
//    public $tableHoverCls = 'table-hover';
//
//    public function getFormModeClass($mode = Form::HORIZONTAL)
//    {
//        return 'form-' . $mode;
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
//        if ($formMode === Form::HORIZONTAL) {
//            $cls .= ' col-sm-' . $headWidthColumn;
//        }
//        $label = "<label class=\"{$cls}\" for=\"{$forId}\">{$label}</label>";
//        echo $label;
//    }
//
//    public function formBodyStart(Form $form, $error = null)
//    {
//        $formMode = $form->getMode();
//        $headWidthColumn = $form->getHeadWidthColumn();
//        if ($formMode === Form::HORIZONTAL) {
//            $class = ' col-sm-' . (12 - $headWidthColumn);
//            echo "<div class=\"{$class}\">";
//        }
//    }
//
//    public function formBodyClose(Form $form)
//    {
//        $formMode = $form->getMode();
//        if ($formMode === Form::HORIZONTAL) {
//            echo "</div>";
//        }
//    }
//
//    public function formBodyErrorPlugs($field, $value, $error)
//    {
//        echo '<span class="glyphicon glyphicon-remove form-control-feedback"></span>';
//    }
//
//    public function submitButton(Form $form, $text)
//    {
//        $formMode = $form->getMode();
//        $headWidthColumn = $form->getHeadWidthColumn();
//        if ($formMode === Form::HORIZONTAL) {
//            $class = 'col-sm-offset-' . $headWidthColumn;
//            $class .= ' col-sm-' . (12 - $headWidthColumn);
//            echo "<div class=\"{$class}\">";
//        }
//        echo "<button class=\"{$this->submitButtonCls}\" type=\"submit\">{$text}</button>";
//        if ($formMode === Form::HORIZONTAL)
//            echo '</div>';
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
//        $html[] = "<div{$attr}><ul class=\"pagination\">";
//
//        if ($isForward) {
//            $class = $options['classPrev'];
//            $text = $options['prev'];
//            $liClass = '';
//            if ($pageNumber === $prev)
//                $liClass = 'disabled';
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
//                $liClass .= ' active';
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
//                $liClass = 'disabled';
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
 