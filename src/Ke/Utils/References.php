<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/25 0025
 * Time: 16:27
 */

namespace Ke\Utils;

use Ke\App;
use Exception;
use Ke\Web\Web;

/**
 * 远程引用的资源本地获取
 *
 * 该类未落实，暂时不不要使用
 *
 * 目前只实现到单文件的获取
 *
 * @package Ke\Utils
 */
class References
{

	protected $uncompressHandles = [
		'zip' => 'static::unZip',
	];

	protected $references = [
//		'jquery'       => [
//			'type'    => 'js',
//			'source'  => 'http://code.jquery.com/jquery-{version}.js',
//			'version' => '1.12.0',
//		],
//		'underscore'   => [
//			'type'    => 'js',
//			'source'  => 'http://underscorejs.org/underscore.js',
//			'version' => '1.8.3',
//		],
//		'requirejs'    => [
//			'type'    => 'js',
//			'source'  => 'http://requirejs.org/docs/release/{version}/comments/require.js',
//			'version' => '2.1.22',
//		],
//		'font-awesome' => [
//			'type'    => 'zip',
//			'source'  => 'https://fortawesome.github.io/Font-Awesome/assets/font-awesome-{version}.zip',
//			'version' => '4.5.0',
//		],
	];

	public function loadFile($file)
	{
		$data = import($file);
		if (!empty($data) && is_array($data))
			$this->mergeReferences($data);
		return $this;
	}

	public function getReferences(): array
	{
		return $this->references;
	}

	public function mergeReferences(array $references)
	{
		foreach ($references as $name => $library) {
			if (empty($name) || !is_string($name))
				continue;
			if (empty($library))
				continue;
			if (is_string($library))
				$library = ['version' => $library];
			$this->mergeLibrary($name, $library);
		}
		return $this;
	}

	public function mergeLibrary(string $name, array $library)
	{
		if (!isset($this->references[$name]))
			$this->references[$name] = $library;
		else
			$this->references[$name] = array_merge($this->references[$name], $library);
		return $this;
	}

	public function getLibrary(string $name)
	{
		if (!isset($this->references[$name]))
			throw new Exception("Undefined reference {$name}");
		return $this->references[$name];
	}

	public function getLibraries()
	{
		return $this->references;
	}

	public function getSource(string $name)
	{
		$library = $this->getLibrary($name);
		if (empty($library['source']))
			throw new Exception("Reference {$name} source is empty!");
		return substitute($library['source'], $library);
	}

	public function getName(string $name)
	{
		$library = $this->getLibrary($name);
		if (empty($library['version']))
			$name = ext("vendor/{$name}", $library['type']);
		else
			$name = ext("vendor/{$name}-{$library['version']}", $library['type']);
		return $name;
	}

	public function getAssetData(string $name)
	{
		$library = $this->getLibrary($name);
		if (empty($library['version']))
			$path = ext("vendor/{$name}", $library['type']);
		else
			$path = ext("vendor/{$name}-{$library['version']}", $library['type']);
		return [$path, $library['type']];
	}

	public function getDownloadPath(string $name, bool $isPreDir = false)
	{
		$library = $this->getLibrary($name);
		$app = App::getApp();
		if (empty($library['version']))
			$path = $app->web("vendor/{$name}", $library['type']);
		else
			$path = $app->web("vendor/{$name}-{$library['version']}", $library['type']);
		if ($isPreDir) {
			$dir = dirname($path);
			if (!is_dir($dir))
				mkdir($dir, 0755, true);
		}
		return $path;
	}

	public function download(string $name, bool $isRecover = false): bool
	{
		$file = $this->getDownloadPath($name, true);
		if (is_file($file) && !$isRecover)
			return false;
		$source = $this->getSource($name);
		$content = $this->httpGet($source);
		if ($content === false)
			throw new \Exception("Download {$source} failure!");
		if (file_put_contents($file, $content) === false)
			throw new \Exception("Save file {$file} error! Please try again!");
		return true;
	}

	public function httpGet(string $source)
	{
		$ch = curl_init($source);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.04');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    // https请求 不验证证书和hosts
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		return @curl_exec($ch);
	}
}