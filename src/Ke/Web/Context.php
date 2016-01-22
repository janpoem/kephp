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

	public $layout = 'default';


	public $web;

	public function __construct(Web $web)
	{
		$this->web = $web;
	}

	public function import($_FILE, array $_VARS = null)
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
	public function getRenderContent($file, $layout = null, array $vars = null, bool $isStrict = false)
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
			$content = "<div>{$message}</div>";
		}
		else {
			$content = $this->import($filePath, $vars);
		}
		if (!empty($layout)) {
			$layoutPath = $this->web->getComponentPath($layout, Component::LAYOUT);
			if ($layoutPath === false) {
				$message = "Layout {$layout} not found!";
				if ($isStrict)
					throw new Exception($message);
				$content = "<div>{$message}</div>" . $content;
			}
			else {
				$vars['content'] = $content;
				$content = $this->import($layoutPath, $vars);
			}
		}
		return $content;
	}

	public function render($file, $layout = null, array $vars = null, bool $isStrict = false)
	{
		print $this->getRenderContent($file, $layout, $vars, $isStrict);
		print PHP_EOL;
		return $this;
	}
}