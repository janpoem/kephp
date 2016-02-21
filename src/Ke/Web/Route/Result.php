<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */


namespace Ke\Web\Route;

use Ke\Uri;
use Ke\Web\Http;

class Result
{

	public $input = null;

	public $method = Http::GET;

	public $mode = Router::MODE_TRADITION;

	public $path = '';

	public $head = null;

	public $actionMatch = '';

	public $tail = '';

	public $format = '';

	public $node = '';

	public $namespace = '';

	public $controller = '';

	public $class = '';

	public $action = '';

	public $matched = false;

	public $matches = [];

	public $data = [];

	/**
	 * 工厂方法，返回一个新的路由匹配结果
	 *
	 * @param Uri|Http|mixed $input
	 * @return Result
	 */
	public static function factory($input)
	{
		if ($input instanceof Result)
			return $input;
		return new static($input);
	}

	public function __construct($input)
	{
		if ($input instanceof Http) {
			$this->input = $input;
			$this->tail = $input->path;
			$this->method = $input->method;
		}
		else {
			if (!($input instanceof Uri)) {
				$input = new Uri($input);
			}
			$this->input = $input;
			$this->tail = $input->path;
		}
		$this->tail = $this->removeHttpBase($this->tail, KE_HTTP_BASE);
		list($this->tail, $this->format) = $this->splitFormat($this->tail);
	}

	public function removeHttpBase(string $path, string $base): string
	{
		$path = ltrim($path, KE_PATH_NOISE);
		if (empty($path))
			return $path;
		$path = '/' . $path;
		// 先过滤base
		if (!empty($base)) {
			if ($base === KE_DS_UNIX || $base === KE_DS_WIN)
				return $path;
			if (defined('KE_HTTP_BASE') && $base !== KE_HTTP_BASE) {
				$base = purge_path($base, KE_PATH_DOT_REMOVE ^ KE_PATH_LEFT_TRIM, KE_DS_UNIX);
				if (!empty($base))
					$base = '/' . $base . '/';
			}
		}
		if (empty($base) || $base === KE_DS_UNIX || $base === KE_DS_WIN)
			return $path;
		list($dir, $file) = parse_path($base);
		$prefixes = [];
		if (empty($file))
			$prefixes[] = $dir . '/' . KE_SCRIPT_FILE . '/';
		else
			$prefixes[] = $base . '/';
		$prefixes[] = $dir . '/';

		foreach ($prefixes as $prefix) {
			if ($path === $prefix) {
				return '';
			}
			if (stripos($path, $prefix) === 0) {
				return substr($path, strlen($prefix));
			}
		}
		return $path;
	}

	public function splitFormat(string $path): array
	{
		$path = rtrim($path, KE_PATH_NOISE);
		$format = '';
		if ($path !== '') {
			$parse = parse_path($path);
			$path = '';
			if ($parse[0] !== '')
				$path = $parse[0];
			if ($parse[1] !== '')
				$path .= ($path === '' ? '' : '/') . $parse[1];
			if (isset($parse[2]) && $parse[2] !== '')
				$format = $parse[2];
		}
		return [$path, $format];
	}

	public function assign(array $data)
	{
		if (!empty($data['class'])) {
			$this->class = $data['class'];
			$this->mode = Router::MODE_CLASS;
		}
		else {
//			if (isset($data['namespace']))
//				$this->namespace = empty($data['namespace']) ? '' : $data['namespace'];
			if (!empty($data['controller'])) {
				$this->mode = Router::MODE_CONTROLLER;
				$this->controller = $data['controller'];
				if (!empty($data['namespace']))
					$this->namespace = $data['namespace'];
			}
			else {
				$this->mode = Router::MODE_TRADITION;
				if ($this->node !== Router::ROOT) {
					if (!isset($data['namespace']))
						$this->namespace = $this->node;
					else
						$this->namespace = $data['namespace'];
				}
			}
		}
		if (!empty($data['action']))
			$this->action = $data['action'];
		return $this;
	}

	public function getData()
	{
		return [
			'controller' => $this->controller,
			'action'     => $this->action,
			'tail'       => $this->tail,
			'format'     => $this->format,
			'vars'       => $this->data,
		];
	}
}