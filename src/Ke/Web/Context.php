<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 16:35
 */

namespace Ke\Web;


use Exception;

class Context
{

	public $title = '';

	public $layout = '';

	public $web;

	public function __construct()
	{
		$this->web = Web::getWeb();
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
			$layoutPath = $this->web->getComponentPath($layout, Component::LAYOUT);
			if ($layoutPath === false) {
				$message = "Layout {$layout} not found!";
				if ($isStrict)
					throw new Exception($message);
				$content = "<pre>{$message}</pre>" . $content;
			}
			else {
				$vars['content'] = $content;
				$content = $this->import($layoutPath, $vars);
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
			$content = $this->layout($content, $layout, $vars, $isStrict);
		return $content;
	}

	public function component($file, $layout = null, array $vars = null, bool $isStrict = false)
	{
		print $this->loadComponent($file, $layout, $vars, $isStrict);
		return $this;
	}

}