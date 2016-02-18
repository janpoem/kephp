<?php
/**
 * KePHP, Keep PHP easy!
 */

namespace Demo\Controller;

use Ke\Utils\PinyinConverter;
use Ke\Web\Controller;

class Index extends Controller
{

	public function index()
	{
		$pinyin = PinyinConverter::loadCache('pinyin_without_tone');
	}
}