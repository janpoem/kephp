<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 20:48
 */

namespace Ke\Web;

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
		$this->web->onRender($this);
		header("Location: {$this->uri->toUri()}", true, 301);
		exit();
	}
}