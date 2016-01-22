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

	public function __construct($view = null)
	{
		parent::__construct();
		$this->view = $view;
	}

	protected function onRender()
	{
		$this->web->sendHeaders();
		// $controller->view(false) => false => ''
		if (!empty($this->view)) {
			$buffer = $this->web->ob->getOutput('webStart', true);
			$content = $this->context->import($this->web->getComponentPath($this->view));
			if (!empty($buffer) && $this->web->isDebug())
				$content .= "<pre>{$buffer}</pre>";

			if (!empty($this->context->layout)) {
				$layout = $this->web->getComponentPath($this->context->layout, Component::LAYOUT);
				if ($layout === false)
					$content = "<pre>Layout {$this->context->layout} not found!</pre>" . $content;
				else
					$content = $this->context->import($layout, ['content' => $content]);
			}
			print $content;
		}
	}

}