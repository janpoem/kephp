<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
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
		header("Cache-Control: no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header("Location: {$this->uri->toUri()}", true, 301);
		exit();
	}
}