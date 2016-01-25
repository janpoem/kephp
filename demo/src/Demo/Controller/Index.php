<?php
/**
 * KePHP, Keep PHP easy!
 */

namespace Demo\Controller;

use Ke\Web\Controller;

class Index extends Controller
{

	public $title = 'Kephp 演示站点';

	public $layout = 'default';

	public function index()
	{
		$this->words = ['abc', 'def', 'ghi'];
	}

	public function test()
	{
		throw new \Exception('抛出一个错误看看！');
	}
}