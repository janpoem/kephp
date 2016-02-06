<?php
/**
 * KePHP, Keep PHP easy!
 */

namespace Demo\Controller;

use Demo\Model\User\User;
use Ke\Web\Controller;

class Index extends Controller
{

	public $title = 'Kephp 演示站点';

	public $layout = 'default';

	public function index()
	{
		$this->user = User::loadCache(1);
		$this->words = ['abc', 'def', 'ghi'];
	}

	public function post_index()
	{
		$post = $this->http->post();
		var_dump($post);
	}

	public function test()
	{
		throw new \Exception('抛出一个错误看看！');
	}
}