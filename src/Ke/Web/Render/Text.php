<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/23 0023
 * Time: 23:59
 */

namespace Ke\Web\Render;


class Text extends Renderer
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
		print $content;
		exit();
	}
}