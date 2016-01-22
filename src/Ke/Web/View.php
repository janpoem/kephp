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

	private $isLoad = false;

	private $content = '';

	public function __construct(string $view = null)
	{
		parent::__construct();
		$this->view = $view;
	}

	public function getLayout()
	{
		if (empty($this->context->layout))
			return false;
		return $this->web->getComponentPath($this->context->layout, Component::LAYOUT);
	}

	public function loadView()
	{
		$this->content = $this->context->import($this->web->getComponentPath($this->view));
		return $this;
	}

	protected function onRender()
	{
		$this->web->sendHeaders();
		$this->loadView();
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