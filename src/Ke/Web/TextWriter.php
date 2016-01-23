<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 17:40
 */

namespace Ke\Web;


class TextWriter extends Renderer
{

	private $content = '';

	public function __construct($content)
	{
		parent::__construct();
		$this->content = $content;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function setContent($content)
	{
		$this->content = (string)$content;
		return $this;
	}

	protected function rendering()
	{
		$content = $this->getContent();
		$length = strlen($content);
		$this->web->sendHeaders([
			'Content-Length' => $length,
		]);
		$this->web->onRender($this);
		print $content;
		exit();
	}
}