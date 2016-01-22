<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 13:02
 */

namespace Ke\Web\Route;

use Ke\Uri;
use Agi\Http\Http;


class Result
{

	public $input = null;

	public $method = Http::GET;

	public $head = null;

	public $tail = '';

	public $format = null;

	public $node = '';

	public $namespace = '';

	public $controller = '';

	public $class = '';

	public $action = '';

	public $matched = false;

	public $matches = [];

	public $data = [
		'controller' => '',
		'action'     => '',
	];

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
		$rs = new static();
		if ($input instanceof Http) {
			$rs->input = $input;
			$rs->tail = $input->path;
			$rs->method = $input->method;
		}
		else {
			if (!($input instanceof Uri)) {
				$input = new Uri($input);
			}
			$rs->input = $input;
			$rs->tail = $input->path;
		}
		// uri或者http取出的path，是/path/或者/path/file.html的格式的，所以不需要做特别的过滤
		// 过滤HTTP_BASE
		$rs->tail = $rs->removeHttpBase($rs->tail, KE_HTTP_BASE);
		list($rs->tail, $rs->format) = $rs->filterTail($rs->tail);
		return $rs;
	}

	public function removeHttpBase(string $path, string $base): string
	{
		if (empty($path))
			return $path;
		$path = '/' . ltrim($path, KE_PATH_NOISE);
		if (empty($base) || $base === KE_DS_UNIX || $base === KE_DS_WIN)
			return $path;
		if ($base !== KE_HTTP_BASE) {
			$base = purge_path($base, KE_PATH_DOT_REMOVE ^ KE_PATH_LEFT_REMAIN, KE_DS_UNIX);
		}
		list($dir, $file, $format) = parse_path($base);
		$prefix = null;
		$secondPrefix = null;
		if (!empty($dir))
			$prefix = $dir;
		if (!empty($file)) {
			$suffix = '';
			if (!empty($format)) {
				$secondPrefix = $prefix;
				$suffix = '.' . $format;
			}
			$prefix .= (empty($prefix) ? '' : '/') . $file . $suffix;
		}
		if ($path === $prefix)
			return '';
		if (stripos($path, $prefix) === 0) {
			return substr($path, strlen($prefix));
		}
		elseif (!empty($secondPrefix) && stripos($path, $secondPrefix) === 0) {
			return substr($path, strlen($secondPrefix));
		}
		return $path;
	}

	public function filterTail(string $path): array
	{
		$path = trim($path, KE_PATH_NOISE);
		$format = '';
		if (!empty($path)) {
			$parse = parse_path($path);
			$path = '';
			if (!empty($parse[0]))
				$path = $parse[0];
			if (!empty($parse[1]))
				$path .= (empty($path) ? '' : '/') . $parse[1];
			if (!empty($parse[2]))
				$format = $parse[2];
		}
		return [$path, $format];
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