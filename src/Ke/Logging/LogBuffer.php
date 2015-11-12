<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Logging;


class LogBuffer
{

	private static $files = [];

	private static $buffers = null;

	private static $isRegister = false;

	public static function getFileId($file)
	{
		if (empty(self::$files)) {
			self::$files[0] = $file;
			return 0;
		} else {
			$id = array_search($file, self::$files);
			if ($id === false) {
				$id = count(self::$files);
				self::$files[$id] = $file;
			}
			return $id;
		}
	}

	public static function getFile($id)
	{
		return isset(self::$files[$id]) ? self::$files[$id] : '';
	}

	public static function push($file, array $row)
	{
		if (self::$isRegister === false) {
			register_shutdown_function(function () {
				static::flush();
			});
			self::$isRegister = true;
		}
		self::$buffers[static::getFileId($file)][] = $row;
	}

	public static function flush()
	{
		foreach (self::$buffers as $fileId => $logs) {
			$file = static::getFile($fileId);
			$buffer = '';
			if (empty($file))
				continue;
			foreach ($logs as $i => $row) {
				$buffer .= Log::prepareLog($row, true) . PHP_EOL;
			}
			if (!empty($buffer)) {
				if ($file === 'php://stderr' || $file === 'php://stdout') {
					$file = false;
				}
				else {
					$dir = dirname($file);
					if (!is_dir($dir))
						mkdir($dir, 0755, true);
					file_put_contents($file, $buffer, FILE_APPEND);
				}
			}
		}
		self::$buffers = null;
	}
}