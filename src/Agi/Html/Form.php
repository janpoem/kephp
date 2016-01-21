<?php

namespace Agi\Html;

use App;
use Agi\Html\Builder\Html;

/**
 * Class Form
 *
 * @package Agi\Html
 * @author Janpoem created at 2014/10/6 7:36
 */
class Form
{

    const VERTICAL = 'vertical';

    const HORIZONTAL = 'horizontal';

    const MODEL_CLASS = '\\Adm\\Model';

    const TABLE_CLASS = '\\Adm\\Table';

    /** @var Component */
    protected $component;

    /** @var \Agi\Html\Builder\Html */
    protected $html;

    /** @var Reference */
    protected $reference;

    protected $mode = self::HORIZONTAL;

    protected $prefix = null;

    protected $verCode = null;

    protected $groups = array();

    protected $columns = array();

    protected $headWidthColumn = 2;

    protected $errors = array();

    protected $colon = null;

    protected $action = HTTP_URI;

    protected $method = 'POST';

    protected $isUpload = false;

    protected $submitText = null;

    protected $data = array();

    /** @var \Adm\Model 这个版本，Model不再作为标准变量，而仅仅是作为取得prefix, columns的辅助变量 */
    private $model = null;

    /** @var \Adm\Table 同model */
    private $table = null;

    protected $renderHandle = null;

    private $renderFields = array();

    private $renderIndex = 0;

    private $render = false;

    private $renderSubmit = false;

    private $jsValid = false;

    private $ajaxPost = false;

    private $id = null;

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

    public function setData($data)
    {
        $type = gettype($data);
        if ($type === PHP_ARY) {
            $this->data = $data;
        } elseif ($type === PHP_OBJ) {

            if (is_subclass_of($data, self::MODEL_CLASS)) {
                $this->data = $data;
                if (empty($this->model)) {
                    $this->model = get_class($data);
                    if (empty($this->errors))
                        $this->errors = $data->getErrors();
                    if (empty($this->columns))
                        $this->columns = $data->getColumns();
                }
            } else {
                $this->data = get_object_vars($data);
            }
        }
        return $this;
    }

    public function init(array $options = null, $data = null)
    {
        if (isset($options['model']) && is_subclass_of($options['model'], self::MODEL_CLASS))
            $this->model = $options['model'];
        if (isset($options['table']) && is_subclass_of($options['table'], self::TABLE_CLASS))
            $this->table = $options['table'];
        if (!empty($options['columns']) && is_array($options['columns']))
            $this->columns = $options['columns'];
        if (!empty($options['groups']) && is_array($options['groups']))
            $this->groups = $options['groups'];
        if (isset($options['errors']) && is_array($options['errors']))
            $this->errors = $options['errors'];
        if (!empty($options['action']))
            $this->action = $options['action'];
        $this->action = linkUri($this->action);
        if (isset($options['method']) && is_string($options['method']))
            $this->method = strtoupper($options['method']);
        if (isset($options['prefix']) && is_string($options['prefix']))
            $this->prefix = strtolower($options['prefix']);
        if (!empty($options['id']) && is_string($options['id']))
            $this->id = strtolower($options['id']);
        if (isset($options['mode']) && is_string($options['mode']))
            $this->mode = strtolower($options['mode']);
        if ($this->mode !== self::VERTICAL && $this->mode !== self::HORIZONTAL)
            $this->mode = self::HORIZONTAL;
        if (isset($options['labelWidth']) && is_numeric($options['labelWidth']))
            $this->labelWidth = $options['labelWidth'];
        $this->isUpload = !empty($options['isUpload']);
        $this->jsValid = !empty($options['jsValid']);
        $this->ajaxPost = !empty($options['ajaxPost']);
        if (isset($options['colon']) && is_string($options['colon']))
            $this->colon = $options['colon'];
        else
            $this->colon = App::getLang()->get('form.colon');
        if (isset($options['submitText']) && is_string($options['submitText']))
            $this->submitText = $options['submitText'];
        if (isset($options['onRender']))
            $this->renderHandle = $options['onRender'];
        if (isset($data))
            $this->setData($data);
        if (!empty($this->model)) {
            $class = $this->model;
            if (empty($this->table))
                $this->table = $class::getTable();
            if (empty($this->columns))
                $this->columns = $class::getStaticColumns();
            if (empty($this->prefix))
                $this->prefix = strtolower($this->model);
        }
        if (empty($this->prefix))
            $this->prefix = 'af_' . mt_rand(100000, 999999);
        return $this;
    }

    public function getMode()
    {
        return $this->mode;
    }


    public function getData()
    {
        return $this->data;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getVerCode()
    {
        if (empty($this->verCode))
            return $this->prefix;
        return $this->verCode;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function isUpload()
    {
        return $this->isUpload;
    }

    public function getColon()
    {
        return $this->colon;
    }

    public function getHeadWidthColumn()
    {
        return 1;
    }

    public function getError($field)
    {
        return isset($this->errors[$field]) ? $this->errors[$field] : false;
    }

    public function getTip($field)
    {
        return isset($this->columns[$field]['tip']) ? $this->columns[$field]['tip'] : false;
    }

    public function getValue($field)
    {
        if (array_key_exists($field, $this->data))
            return $this->data[$field];
        if (isset($this->columns[$field]['default']))
            return $this->columns[$field]['default'];
        return null;
    }

    public function getColumn($field)
    {
        if (isset($this->columns[$field]))
            return $this->columns[$field];
        return false;
    }

    public function getTitle($field)
    {
        if (isset($this->columns[$field]['title']))
            return $this->columns[$field]['title'];
        $lang = App::getLang();
        if (isset($lang["{$this->model}#{$field}"]))
            return $lang["{$this->model}#{$field}"];
        else if (isset($lang[$field]))
            return $lang[$field];
        else
            return ucwords(strtr($field, '_', ' '));
    }

    public function getSubmitText()
    {
        if (empty($this->submitText))
            return App::getLang()->get('form.submit');
        return $this->submitText;
    }

    public function getRenderIndex()
    {
        return $this->renderIndex;
    }

    public function addRenderIndex()
    {
        $this->renderIndex += 1;
        return $this;
    }

    public function getComponent()
    {
        return $this->component;
    }

    public function mkAttr(array $column, array & $attr = array())
    {
        $require = !empty($column['require']);
        $isInt = !empty($column['int']);
        $isFloat = !empty($column['float']);
        $isNumber = $isFloat || $isInt;
        if ($require)
            $attr['require'] = 1;
        if ($isNumber) {
            if ($isInt) {
                $attr['int'] = 1;
            } elseif ($isFloat) {
                $f = is_numeric($isFloat) && $isFloat > 0 ? $isFloat : 2;
                $attr['float'] = $f;
            }
            if (isset($column['numMin']) && is_numeric($column['numMin']) && $column['numMin'] > 0)
                $attr['min'] = $column['numMin'];
            if (isset($column['numMax']) && is_numeric($column['numMax']) && $column['numMax'] > 0)
                $attr['max'] = $column['numMax'];
        } else {
            if (!empty($column['email']))
                $attr['regex-email'] = 1;
            elseif (!empty($column['pattern']))
                $attr['regex'] = $column['pattern'];
            if (isset($column['max']) && is_numeric($column['max']) && $column['max'] > 0)
                $attr['maxlength'] = $column['max'];
            if (isset($column['min']) && is_numeric($column['min']) && $column['min'] > 0)
                $attr['minlength'] = $column['min'];
        }
        if (!empty($column['readonly']))
            $attr['readonly'] = 1;
        if (!empty($column['disabled']))
            $attr['disabled'] = 1;
        return $attr;
    }

    public function getId()
    {
        if (!empty($this->id))
            return $this->html->mkId($this->id);
        else
            return $this->html->mkId($this->prefix, 'form');
    }

    public function start()
    {
        $formId = $this->getId();
        $formAttr = array(
            'id'     => $formId,
            'class'  => $this->html->mkClass($this->prefix, 'form'),
            'action' => $this->action,
            'method' => $this->method,
        );
        if ($this->isUpload)
            $formAttr['enctype'] = 'multipart/form-data';
        elseif ($this->ajaxPost) // 上传就别玩ajax-post了
            $formAttr['ajax-post'] = 'ajax-post';
        $pair = $this->html->getFormTagPair($formAttr, $this->mode);
        echo $pair[0];
        return $this;
    }

    public function close()
    {
        $formId = $this->getId();
        $formAttr = array(
            'id'     => $formId,
            'class'  => $this->html->mkClass($this->prefix, 'form'),
            'action' => $this->action,
            'method' => $this->method,
        );
        if ($this->isUpload)
            $formAttr['enctype'] = 'multipart/form-data';
        elseif ($this->ajaxPost) // 上传就别玩ajax-post了
            $formAttr['ajax-post'] = 'ajax-post';
        $pair = $this->html->getFormTagPair($formAttr, $this->mode);
        $this->renderSubmit($this->getSubmitText());
        echo $pair[1];
        $this->html->formScript($this);
        return $this;
    }


    public function render($isWrap = true)
    {
        $formId = $this->getId();
        $formAttr = array(
            'id'     => $formId,
            'class'  => $this->html->mkClass($this->prefix, 'form'),
            'action' => $this->action,
            'method' => $this->method,
        );
        if ($this->isUpload)
            $formAttr['enctype'] = 'multipart/form-data';
        elseif ($this->ajaxPost) // 上传就别玩ajax-post了
            $formAttr['ajax-post'] = 'ajax-post';
        $pair = array();
        if ($isWrap) {
            $pair = $this->html->getFormTagPair($formAttr, $this->mode);
            echo $pair[0];
        }

        foreach ($this->columns as $field => $column) {
            $this->renderField($field, $column, false);
        }

        if ($isWrap) {
            $this->renderSubmit($this->getSubmitText());
            echo $pair[1];
            $this->html->formScript($this);
        }

        $this->onRender();
        return $this;
    }

    public function renderField($field, array $column = null, $onlyInput = false)
    {
        if (isset($this->renderFields[$field]))
            return $this;

        $this->html->formRow($this, $field, $column, $onlyInput);

        return $this;
    }

    public function renderSubmit($text)
    {
        if ($this->renderSubmit)
            return $this;
        $pair = $this->html->getFormRowTagPair($this, 'submit', 'submit');
        echo $pair[0];
        echo $this->html->mkSubmitButton($text);
        $this->component->httpVerCode($this->getVerCode(), 'hidden');
        echo $pair[1];
        $this->renderSubmit = true;
        return $this;
    }

    public function onRender()
    {
        if (isset($this->renderHandle) && is_callable($this->renderHandle))
            call_user_func($this->renderHandle, $this);
        return $this;
    }
}
