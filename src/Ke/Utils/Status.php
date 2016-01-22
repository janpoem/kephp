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


class Status
{

	protected $status;

	public $message;

	public $data = [];

	public function __construct($status, string $message = null, array $data = null)
	{
		$this->setStatus($status);
		if (!empty($message))
			$this->setMessage($message);
		if (!empty($data))
			$this->setData($data);
	}

	public function setStatus($status)
	{
		$this->status = (bool)$status;
		return $this;
	}

	public function setMessage(string $message, $isPlus = false)
	{
		if ($isPlus)
			$this->message .= $message;
		else
			$this->message = $message;
		return $this;
	}

	public function plusMessage(string $message)
	{
		$this->message .= $message;
		return $this;
	}

	public function setData(array $data)
	{
		if (empty($this->data))
			$this->data = $data;
		else
			$this->data = array_merge($this->data, $data);
		return $this;
	}

	public function isSuccess()
	{
		return $this->status === true;
	}

	public function isFailure()
	{
		return $this->status !== true;
	}

	public function export()
	{
		$data = [];
		$data['status'] = $this->status;
		$data['message'] = $this->message;
		$data['data'] = $this->data;
		return $data;
	}

	public function toJSON()
	{
		return json_encode($this->export());
	}
}

class Success extends Status
{

	public function __construct(string $message, array $data = null)
	{
		parent::__construct(true, $message, $data);
	}
}

class Failure extends Status
{

	public function __construct(string $message, array $data = null)
	{
		parent::__construct(false, $message, $data);
	}
}

class CodeStatus extends Status
{

	public function setStatus($status)
	{
		$this->status = (int)$status;
		return $this;
	}

	public function isSuccess()
	{
		return $this->status > 0;
	}

	public function isFailure()
	{
		return $this->status <= 0;
	}
}