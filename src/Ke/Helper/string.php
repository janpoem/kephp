<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

const KE_ASCII_0 = 48; // 1 => 49
const KE_ASCII_9 = 57;
const KE_ASCII_UPPER_A = 65;
const KE_ASCII_UPPER_Z = 90;
const KE_ASCII_LOWER_A = 97;
const KE_ASCII_LOWER_Z = 122;

if (!function_exists('camelcase')) {
	function camelcase($str, $tokens = ['-', '_', '.'], $first = false)
	{
		$result = ucwords(str_replace($tokens, ' ', strtolower($str)));
		$result = str_replace(' ', '', $result);
		if (isset($result[0]) && !$first) {
			$code = ord($result[0]);
			if ($code >= KE_ASCII_UPPER_A && $code <= KE_ASCII_UPPER_Z)
				$result[0] = strtolower($result[0]);
		}
		return $result;
	}
}

if (!function_exists('hyphenate')) {
	function hyphenate($str, $replace = '-', $first = false)
	{
		$str = preg_replace_callback('#([A-Z])#', function ($matches) use ($replace) {
			return $replace . strtolower($matches[1]);
		}, (string)$str);
		if (!$first)
			$str = ltrim($str, $replace);
		return $str;
	}
}
