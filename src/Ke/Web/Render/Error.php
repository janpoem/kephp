<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/24 0024
 * Time: 0:00
 */

namespace Ke\Web\Render;


use Ke\Web\Component;

class Error extends Renderer
{

	private $error = null;

	private $layout = false;

	private $content = '';

	public function __construct($error = null)
	{
		parent::__construct();
		if (isset($error))
			$this->setError($error);
	}

	public function setError($error)
	{
		if ($error instanceof \Throwable || (is_array($error) && !empty($error)))
			$this->error = $error;
		else
			$this->error = null;
		return $this;
	}

	public function setLayout(string $layout = null)
	{
		$layout = trim($layout, KE_PATH_NOISE);
		if (empty($layout))
			$layout = false;
		$this->layout = $layout;
		return $this;
	}

	protected function rendering()
	{
		print $this->getContent();
	}

	public function getContent()
	{
		$layout = 'error';
		$tip = '';
		// 错误信息输出，分几个流程
		if (!$this->web->isDispatch()) {
			// 1. 未正确分发的状态下，没有加载到正确的controller，也就无法确保各种变量的正确加载
			//    这时候不能使用项目的布局文件，只能使用特定的error报错布局（当然，这种特点，也允许在项目针对性的添加error的布局）
			$layout = 'error';
			$tip = 'An error occurred while dispatching website!';
		}
		else {
			$renderer = $this->web->getRenderer();
			if ($renderer === $this) {
				// 2. 如果当前渲染器是Error渲染器，表示未进行渲染，在执行action时发生错误
				$controller = $this->web->getControllerObject();
				// 这里还是可能会出现controller为空的情况
				if (!empty($controller))
					$layout = $controller->layout;
				$tip = 'An error occurred while initializing the controller or perform actions!';
			}
			else {
				// 3. 如果当前渲染器不是Error渲染器，表示已经进入渲染阶段发生错误
				// view渲染器，如果取出有效布局为false，表示布局加载出错了，还是要使用error布局
				if ($renderer instanceof View) {
					$layout = $renderer->getValidLayout();
					if ($layout === false)
						$layout = 'error';
				}
				$tip = 'An error occurred during rendering!';
			}
		}

		$this->content = $this->context->loadComponent('error', [
			'error' => $this->error,
		    'tip' => $tip,
		]);

		if (!empty($layout)) {
			// 如果实在是加载布局出错了，就实在是没办法了
			try {
				$this->content = $this->context->layout($this->content, $layout);
			}
			catch (\Throwable $thrown) {
				$this->content = $this->context->layout($this->content, 'error');
			}
		}

		return $this->content;
	}

	public function setContent($content)
	{
		// TODO: Implement setContent() method.
	}
}