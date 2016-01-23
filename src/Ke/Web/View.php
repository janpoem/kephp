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

	private $content = '';

	public function __construct($view = null)
	{
		parent::__construct();
		$this->view = $view;
	}

	public function getContent()
	{
		if (!empty($this->view)) {
			$buffer = $this->web->ob->getOutput('webStart', true);
			$this->content = $this->context->import($this->web->getComponentPath($this->view));
			if (!empty($buffer) && $this->web->isDebug())
				$this->content .= "<pre>{$buffer}</pre>";

			if (!empty($this->context->layout)) {
				$this->content = $this->context->layout($this->content, $this->context->layout);
//				$layout = $this->web->getComponentPath($this->context->layout, Component::LAYOUT);
//				if ($layout === false)
//					$this->content = "<pre>Layout {$this->context->layout} not found!</pre>" . $this->content;
//				else
//					$this->content = $this->context->import($layout, ['content' => $this->content]);
			}
		}
		return $this->content;
	}

	public function setContent($content)
	{
		$this->content = $content;
		return $this;
	}

	protected function rendering()
	{
		$this->web->sendHeaders();
		$this->web->onRender($this);
		print $this->getContent();
		// $controller->view(false) => false => ''
//		if (!empty($this->view)) {
//			$buffer = $this->web->ob->getOutput('webStart', true);
//			$content = $this->context->import($this->web->getComponentPath($this->view));
//			if (!empty($buffer) && $this->web->isDebug())
//				$content .= "<pre>{$buffer}</pre>";
//
//			if (!empty($this->context->layout)) {
//				$layout = $this->web->getComponentPath($this->context->layout, Component::LAYOUT);
//				if ($layout === false)
//					$content = "<pre>Layout {$this->context->layout} not found!</pre>" . $content;
//				else
//					$content = $this->context->import($layout, ['content' => $content]);
//			}
//
//			print $content;
//		}
	}

}