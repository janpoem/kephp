<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */


namespace Ke\Web;


use Exception;

class Context
{

	public $title = '';

	public $layout = '';

	public $web;

	public $html;

	public function __construct()
	{
		$this->web = Web::getWeb();
		$this->html = $this->web->getHtml();
	}

	public function assign($key, $value = null)
	{
		if (is_array($key) || is_object($key)) {
			foreach ($key as $k => $v) {
				if (!empty($k) && is_string($k))
					$this->{$k} = $v;
			}
		}
		elseif (!empty($key) && is_string($key)) {
			$this->{$key} = $value;
		}
		return $this;
	}

	public function selectLayout($layout = null)
	{
		return empty($this->layout) ? $layout : $this->layout;
	}

	public function import($_FILE, array $_VARS = null, bool $isStrict = false)
	{
		$web = $this->web;
		$content = '';
		if (is_file($_FILE) && is_readable($_FILE)) {
			$content = $web->ob->getFunctionBuffer(null, function () use ($web, $_FILE, $_VARS) {
				if (!empty($_VARS))
					extract($_VARS);
				unset($_VARS);
				require $_FILE;
			});
		}
		return $content;
	}

	public function layout($content, $layout = null, array $vars = null, bool $isStrict = false): string
	{
		if ($content instanceof Widget)
			$content = $content->getRenderContent();
		if (!empty($layout)) {
			try {
				$layoutPath = $this->web->getComponentPath($layout, Component::LAYOUT);
				if ($layoutPath === false) {
					$message = "Layout {$layout} not found!";
					if ($isStrict) {
						throw new \Error($message);
					}
					else {
						$content = $content . "<pre>{$message}</pre>";
					}
				}
				else {
					$vars['content'] = $content;
					$content = $this->import($layoutPath, $vars);
				}
			}
			catch (\Throwable $thrown) {
				throw $thrown;
			}
		}
		return $content;
	}

	/**
	 *
	 * <code>
	 * import('file', ['user' => new User()]);
	 * import('file');
	 * import('file', 'wrapper', ['name' => 'Jack']);
	 * import('file', 'wrapper');
	 * </code>
	 *
	 * @param            $file
	 * @param null       $layout
	 * @param array|null $vars
	 * @param bool       $isStrict
	 * @return bool|string
	 * @throws Exception
	 */
	public function loadComponent($file, $layout = null, array $vars = null, bool $isStrict = false)
	{
		if (!isset($layout) || is_array($layout) || is_object($layout)) {
			$vars = (array)$layout;
			$layout = false;
		}
		$content = '';
		$filePath = $this->web->getComponentPath($file);
		if ($filePath === false) {
			$message = "Component {$file} not found!";
			if ($isStrict)
				throw new Exception($message);
			$content = "<pre>{$message}</pre>";
		}
		else {
			$content = $this->import($filePath, $vars);
		}
		if (!empty($layout))
			$content = $this->layout($content, $layout, $vars);
		return $content;
	}

	public function component($file, $layout = null, array $vars = null, bool $isStrict = false)
	{
		print $this->loadComponent($file, $layout, $vars, $isStrict);
		return $this;
	}

}