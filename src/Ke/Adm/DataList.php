<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm;

use ArrayObject;


class DataList extends ArrayObject
{

	protected $pagination = array();

	protected $model = null;

	public function __construct(array $data = null)
	{
		if (isset($data))
			parent::__construct($data, ArrayObject::STD_PROP_LIST);
	}

	public function isEmpty()
	{
		return $this->count() <= 0;
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

	public function setModel($model)
	{
		if (class_exists($model, false) && is_subclass_of($model, Model::class))
			$this->model = $model;
		return $this;
	}

	public function getModel()
	{
		return $this->model;
	}
}