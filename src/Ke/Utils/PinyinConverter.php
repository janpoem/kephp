<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Utils;

use Ke\Adm\CacheModelTrait;
use Ke\App;

/**
 * 简易的拼音处理转换器
 *
 * 这个拼音转换器，只实现了简单的声母韵母匹配和转换，并且结合了`CacheModelTrait`，可以视作`CacheModelTrait`的一种展示。
 *
 * 实际使用时：
 *
 * ```php
 * $converter = PinyinConverter::loadCache('pinyin_without_tone');
 * $converter->convert('你好吗？'); // "ni-hao-ma"
 *
 * $converter->fetch('你好 hello world', [
 *     'allowChars' => '[A-Za-z0-9_-]+',
 * ]); // ["ni", "hao", "hello", "world"]
 * ```
 *
 * Just a toy, but very practical
 *
 * 本字符集参考自：
 * @link http://www.oschina.net/code/snippet_862384_25415
 *
 *
 * 更精准的拼音转换器应该使用这个：
 * @link https://github.com/overtrue/pinyin
 *
 * @package Demo\Helper
 */
class PinyinConverter
{

	use CacheModelTrait;

	const TRANS_RETURN      = 'return';
	const TRANS_DELIMITER   = 'delimiter';
	const TRANS_ALLOW_CHARS = 'allowChars';
	const TRANS_FIRST_CHAR  = 'firstChar';
	const TRANS_UCFIRST     = 'ucfirst';

	const RETURN_ARRAY  = 0;
	const RETURN_STRING = 1;

	const UNI_CN_CHAR_START = 19968;
	const UNI_CN_CHAR_END   = 40869;

	protected $isPrepare = false;

	protected $regexPinyin = false;

	/** @var string 声母表 */
	protected $initials = 'b|p|m|f|d|t|n|l|g|k|h|j|q|x|zh|ch|sh|r|z|c|s|y|w';

	/** @var string 韵母表 */
	protected $finals = 'a|o|e|i|u|v|ai|ei|ui|ao|ou|iu|ie|ue|er|an|en|in|un|ang|eng|ing|ong';

	protected $charSet = [];

	protected $transOptions = [
		self::TRANS_ALLOW_CHARS => '',
		self::TRANS_RETURN      => self::RETURN_ARRAY,
		self::TRANS_DELIMITER   => '-', // 这个参数只在返回字符类型有效
		self::TRANS_FIRST_CHAR  => false,
		self::TRANS_UCFIRST     => false,
	];

	public static function unicodeEncode(string $c): int
	{
		$ord0 = ord($c{0});
		if ($ord0 >= 0 && $ord0 <= 127)
			return $ord0;
		$ord1 = ord($c{1});
		if ($ord0 >= 192 && $ord0 <= 223)
			return ($ord0 - 192) * 64 + ($ord1 - 128);
		$ord2 = ord($c{2});
		if ($ord0 >= 224 && $ord0 <= 239)
			return ($ord0 - 224) * 4096 + ($ord1 - 128) * 64 + ($ord2 - 128);
		$ord3 = ord($c{3});
		if ($ord0 >= 240 && $ord0 <= 247)
			return ($ord0 - 240) * 262144 + ($ord1 - 128) * 4096 + ($ord2 - 128) * 64 + ($ord3 - 128);
		return 0;
	}

	public static function unicodeDecode(int $num): string
	{
		if ($num <= 0x7F)
			return chr($num);
		if ($num <= 0x7FF)
			return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
		if ($num <= 0xFFFF)
			return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
		if ($num <= 0x1FFFFF)
			return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) .
			       chr(($num & 63) + 128);
		return '';
	}

	protected function onPrepareCache(string $key, array $args)
	{
		$file = App::getApp()->config($args[0], 'php');
		$this->loadCharSet($file);
	}

	public function isValidCache()
	{
		return !empty($this->charSet);
	}

	public function prepare()
	{
		if ($this->isPrepare === false) {
			$this->isPrepare   = true;
			$this->regexPinyin = "#^({$this->initials})?(.*)?({$this->finals})$#i";
			$this->initials    = explode('|', $this->initials);
			$this->finals      = explode('|', $this->finals);
		}
		return $this;
	}

	public function setCharSet(array $charSet)
	{
		if (empty($this->charSet))
			$this->charSet = $charSet;
		else
			$this->charSet = array_merge($this->charSet, $charSet);
		return $this;
	}

	public function loadCharSet($file)
	{
		$charSet = import($file, null, KE_IMPORT_MERGE);
		if (!empty($charSet))
			$this->setCharSet($charSet);
		return $this;
	}

	public function findInitial(string $initial): int
	{
		if (!$this->isPrepare)
			$this->prepare();
		$index = array_search($initial, $this->initials);
		if ($index === false)
			return -1;
		return $index;
	}

	public function findFinal(string $final): int
	{
		if (!$this->isPrepare)
			$this->prepare();
		$index = array_search($final, $this->finals);
		if ($index === false)
			return -1;
		return $index;
	}

	public function pinyinToCode(string $pinyin): int
	{
		$code = 0;
		if (preg_match($this->regexPinyin, $pinyin, $matches)) {
			$code = 1;
			if (!empty($matches[3]))
				$code = $code * 100 + $this->findFinal($matches[3]);
			if (!empty($matches[2]))
				$code = $code * 100 + $this->findFinal($matches[2]);
			if (!empty($matches[1]))
				$code = $code * 100 + $this->findInitial($matches[1]);
		}
		return $code;
	}

	public function codeToPinyin(int $code)
	{
		if (!$this->isPrepare)
			$this->prepare();
		$pinyin = '';
		$last   = $code;
		$index  = 0;
		while ($last >= 100) {
			$node     = $last % 100;
			$syllable = '';
			if ($code >= 10000 && $index === 0) {
				$syllable = $this->initials[$node] ?? '';
			}
			else {
				$syllable = $this->finals[$node] ?? '';
			}
			$pinyin .= $syllable;
			$last = intval($last / 100);
			++$index;
		}
		return $pinyin;
	}

	public function filterTransOptions(array $options = null)
	{
		if (empty($options))
			return $this->transOptions;
		$options = array_merge($this->transOptions, $options);
		if (!empty($options[self::TRANS_ALLOW_CHARS])) {
			$options[self::TRANS_ALLOW_CHARS] = '#' . $options[self::TRANS_ALLOW_CHARS] . '#';
		}
		else {
			$options[self::TRANS_ALLOW_CHARS] = false;
		}
		return $options;
	}

	public function fetch(string $chars, array $options = null)
	{
		$options     = $this->filterTransOptions($options);
		$isUcfirst   = $options[self::TRANS_UCFIRST];
		$isFirstChar = $options[self::TRANS_FIRST_CHAR];
		$result      = [];
		$split       = preg_split('//u', $chars, null, PREG_SPLIT_NO_EMPTY);
		$lastIndex   = 0;
		$lastAllow   = 0;
		foreach ($split as $index => $item) {
			$code = $this->charSet[$item] ?? 0;
			if ($code > 0) {
				$pinyin   = $this->codeToPinyin($code);
				$pick     = $isFirstChar ? $pinyin{0} : $pinyin;
				$pick     = $isUcfirst ? ucfirst($pick) : strtolower($pick);
				$result[] = $pick;
			}
			elseif ($options[self::TRANS_ALLOW_CHARS] !== false) {
				if (preg_match($options[self::TRANS_ALLOW_CHARS], $item)) {
					$lastAllow = count($result) - 1;
					if ($lastIndex + 1 === $index) {
						if (!$options[self::TRANS_FIRST_CHAR]) { // 如果设定只取首字母，则这个规则对一般的允许字符也生效
							$ord = ord($item);
							if ($ord >= KE_ASCII_UPPER_A && $ord <= KE_ASCII_UPPER_Z)
								$item = strtolower($item);
							$result[$lastAllow] .= $item; // 连续的英文单词组成
						}
					}
					else {
						$result[] = $isUcfirst ? strtoupper($item) : strtolower($item);
					}
					$lastIndex = $index;
				}
			}
		}
		if ($options[self::TRANS_RETURN] === self::RETURN_ARRAY)
			return $result;
		return implode($options[self::TRANS_DELIMITER], $result);
	}

	public function convert(string $chars, array $options = null): string
	{
		$options = $options ?? [];
		//
		$options[self::TRANS_RETURN] = self::RETURN_STRING;
		return $this->fetch($chars, $options);
	}
}