<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/23 0023
 * Time: 23:58
 */

namespace Ke\Web\Render;

use Ke\Uri;

class Redirection extends Renderer
{

	private $uri;

	public function __construct(Uri $uri)
	{
		parent::__construct();
		$this->uri = $uri;
	}

	public function getContent()
	{
		return $this->uri->toUri();
	}

	public function setContent($uri, $query = null)
	{
		$this->uri = $this->web->linkTo($uri, $query);
		return $this;
	}

	protected function rendering()
	{
		header_remove();
		header("Location: {$this->uri->toUri()}", true, 301);
		exit();
	}
}