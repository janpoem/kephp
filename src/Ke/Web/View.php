<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 16:40
 */

namespace Ke\Web;


class View extends Renderer
{

	private $view = null;

	public function __construct(string $view = null)
	{
		$this->view = $view;
	}

	protected function rendering(Web $web)
	{
		$context = $web->getContext();
		$layout = $context->layout;
		$web->sendHeaders();
		$context->render($this->view, $layout, (array)$context, true);
	}

}