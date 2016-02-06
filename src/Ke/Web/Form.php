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


use Ke\Adm\Model;
use Ke\Utils\Status;

class Form extends Widget
{

	const GROUP_AUTO_WIDTH = 0;

	const GROUP_SPEC_WIDTH = 1;

	protected $publicOptions = [
		'prefix'     => true,
		'data'       => 'setData',
		'columns'    => 'setColumns',
		'groups'     => 'setColumnsGroups',
		'method'     => 'setMethod',
		'action'     => true,
		'submit'     => true,
		'reset'      => true,
		'return'     => true,
		'ref'        => true,
		'errors'     => 'setErrors',
		'errorTitle' => true,
	];

	public $prefix = '';

	public $data = [];

	private $object = null;

	private $dataClass = '';

	private $isPostBack = false;

	private $postData = [];

	public $columns = [];

	private $groups = [];

	private $groupColumnIndex = [];

	public $method = 'post';

	public $action = '';

	public $submit = '提交';

	public $reset = '重置';

	public $errors = [];

	public $errorTitle = '';

	protected $web = null;

	protected $http = null;


	public function __construct($data = null, array $columns = null, array $options = null)
	{
		if (isset($data))
			$this->setData($data);
		if (isset($columns))
			$this->setColumns($columns);
		if (isset($options))
			$this->setOptions($options);
		$this->web = Web::getWeb();
		$this->http = $this->web->http;
	}

	public function setData($data)
	{
		if (is_object($data)) {
			if ($data instanceof Model) {
				$this->setColumns($data->getStaticColumns());
				$this->errors = $data->getErrors();
			}
			$this->object = $data;
			$this->mergeData((array)$data);
			$this->dataClass = get_class($data);
		}
		elseif (is_array($data)) {
			$this->mergeData((array)$data);
		}
		return $this;
	}

	public function mergeData(array $data)
	{
		if (empty($this->data))
			$this->data = $data;
		else
			$this->data = array_merge($this->data, $data);
		return $this;
	}

	public function getData()
	{
		return $this->data;
	}

	public function setColumns(array $columns)
	{
		foreach ($columns as $field => $column) {
			if (is_string($column))
				$column = ['label' => $column];
			elseif (!is_array($column))
				$column = [];
			if (!isset($this->columns[$field]))
				$this->columns[$field] = $column;
			else
				$this->columns[$field] = array_merge($this->columns[$field], $column);
		}
		return $this;
	}

	public function getColumns(): array
	{
		return $this->columns;
	}

	public function getColumn($field): array
	{
		$column = $this->columns[$field] ?? [];
		if (!empty($this->prefix))
			$column['prefix'] = $this->prefix;
		$column['error'] = $this->getError($field);
		return $column;
	}

	public function getColumnType($field): string
	{
		return $this->columns[$field]['edit'] ?? 'text';
	}

	public function getColumnLabel($field)
	{
		return $this->columns[$field]['label'] ?? $field;
	}

	public function getColumnData($field)
	{
		if ($this->method === 'get') {
			return $this->http->query($field, $this->data[$field] ?? null);
		}
		elseif ($this->method === 'post') {
			if ($this->isPostBack === false) {
				$this->isPostBack = $this->http->isPost($this->prefix);
				if ($this->isPostBack) {
					$this->postData = $this->http->getSecurityData();
				}
			}
		}
		if ($this->isPostBack)
			return $this->postData[$field] ?? $this->data[$field] ?? null;
		return $this->data[$field] ?? null;
	}

	public function setColumnsGroups(array $groups)
	{
		foreach ($groups as $groupIndex => $group) {
			if (empty($group) || !is_array($group))
				continue;
			$total = 0; // 0 - 10;
			$groupType = self::GROUP_AUTO_WIDTH;
			$groupColumns = [];
			// keyValue ['c1' => 0.5, 'c2' => 0.3, 'c3' => 0]
			// list     ['c1', 'c2', 'c3']
			// mixed    ['c1' => 0.3, 'c2', 'c3']
			// overflow ['c1' => 0.8, 'c2' => 0.5] total: 1.3
			foreach ($group as $key => $val) {
				$column = $val;
				$width = 0;
				if (is_string($key) && !empty($key)) {
					$column = $key;
					$width = intval($val);
					if ($width === false)
						$width = 0;
				}
				if (empty($column) || !isset($this->columns[$column]))
					continue;
				// 已经分组的，不能再和其他一起分组
				if (isset($this->groupColumnIndex[$column]))
					continue;
				// 放入已经分组的索引中，字段 => 分组index
				$this->groupColumnIndex[$column] = $groupIndex;
				if ($width > 0) {
					$groupType = self::GROUP_SPEC_WIDTH;
					$total += $width;
				}
				$groupColumns[$column] = $width;
			}
			if ($total > 1 || $total < 0) { // 溢出的，重新当平均分计算
				$groupType = self::GROUP_AUTO_WIDTH;
			}
			$this->groups[$groupIndex] = [$groupType, $groupColumns];
		}
		return $this;
	}

	public function getColumnsGroups(): array
	{
		return $this->groups;
	}

	public function getColumnsGroup(int $index)
	{
		return $this->groups[$index] ?? false;
	}

	public function indexOfColumnsGroup(string $column): int
	{
		return $this->groupColumnIndex[$column] ?? -1;
	}

	public function setMethod(string $method)
	{
		$this->method = strtolower($method);
		return $this;
	}

	public function getMethod(): string
	{
		return $this->method;
	}

	public function getButtons()
	{
		$buttons = [
			['submit', empty($this->submit) || !is_string($this->submit) ? 'Submit' : $this->submit],
		];
		if (!empty($this->reset))
			$buttons[] = ['reset', !is_string($this->reset) ? 'Reset' : $this->reset];
		if (!empty($this->return))
			$buttons[] = ['button', '返回', $this->return];
		return $buttons;
	}

	public function getError($field)
	{
		return $this->errors[$field] ?? false;
	}

	public function setErrors($errors)
	{
		if ($errors instanceof Status) {
			$this->errorTitle = $errors->message;
			if (!empty($errors->data))
				$this->errors = array_merge($this->errors, $errors->data);
		}
		elseif (is_array($errors)) {
			$this->errors = array_merge($this->errors, $errors);
		}
		return $this;
	}

	public function render(array $fields = null): string
	{
		$html = $this->web->getHtml();
		$rows = [];
		if (empty($fields))
			$fields = array_keys($this->columns);
		$completeGroups = [];
		$errors = [];
		foreach ($fields as $field) {
			if (!isset($this->columns[$field]))
				continue;
			$index = $this->indexOfColumnsGroup($field);
//			if ($index > -1 && !isset($completeGroups[$index])) {
//				$group = $this->getColumnsGroup($index);
//				$completeGroups[$index] = true;
//				continue;
//			}
			$column = $this->getColumn($field);
			if (!empty($column['error']))
				$errors[] = $html->mkTag('li', $column['error']);
			$rows[] = $html->mkFormRow($html->mkFormColumn($field, $this->getColumnData($field),
				$column));

		}
		$securityInput = '';
		if ($this->method !== 'get') {
			$securityInput = $html->mkInput('hidden', $this->http->mkSecurityCode($this->prefix), [
				'name' => KE_HTTP_SECURITY_FIELD,
			]);
		}
		$rows[] = $html->mkFormButtons($this->getButtons());
		$rows[] = $securityInput;
//		$buttons = [];
//		foreach ($this->getButtons() as $type => $button) {
//			$buttons[] = $html->mkButton($button[0], $button[1]);
//		}
//		$rows[] = $html->mkFormRow(implode('', $buttons) . $securityInput, true);

		if (!empty($errors)) {
			$error = $html->mkTag('div', empty($this->errorTitle) ? '表单填写有误' : $this->errorTitle, ['class' => 'header']) .
			         $html->mkTag('ul', implode('', $errors), ['class' => 'list']);
			$rows[] = $html->mkTag('div', $error, ['class' => 'ui error message']);
		}
		if (empty($this->action))
			$this->action = $this->web->http;
		$form = $html->mkTag('form', implode('', $rows), [
			'method' => $this->method,
			'action' => $html->filterHref($this->action),
			'class'  => !empty($errors) ? 'error' : null,
		]);
		print $form;
		return '';
	}
}