<?php

namespace Demo;

use Ke\Loader;

class App extends \Ke\App
{

	/** @var string 项目的名称 */
	protected $name = null;

	/** @var string 项目的基础Hash */
	protected $salt = null;

	/** @var string 区域语言习惯 */
	protected $locale = 'en_US';

	/** @var string 默认时区 */
	protected $timezone = 'Asia/Shanghai';

	/** @var string 编码 */
	protected $encoding = 'UTF-8';

	/**
	 * 编码顺序，值类型应该数组格式，或者以逗号分隔的字符串类型
	 *
	 * 'GBK,GB2312,CP936'
	 * ['GBK', 'GB2312', 'CP936']
	 *
	 * @var string|array
	 */
	protected $encodingOrder = ['GBK', 'GB2312'];

	/** @var string http的路径前缀 */
	protected $httpBase = null;

	/** @var bool 是否开启了HTTP REWRITE */
	protected $httpRewrite = true;

	/** @var string http的验证字段 */
	protected $httpSecurityField = null;

	/** @var string http验证字段的内容加密的hash */
	protected $httpSecuritySalt = null;

	protected $httpSecuritySessionField = null;

	/** @var array 声明SERVER_NAME所对应的应用程序运行环境 */
	protected $servers = [];

	protected $helpers = [

	];

	/**
	 * 目录的别名，这里的目录生成，是以KE_APP_ROOT为基础展开的
	 *
	 * @var array 目录的别名
	 */
	protected $aliases = [];

	/** @var array 绝对路径的文件目录存放 */
	protected $dirs = [];

//	protected function onConstruct(Loader $loader)
//	{
//	}

//	protected function onDevelopment()
//	{
//	}

//	protected function onTest()
//	{
//	}

//	protected function onProduction()
//	{
//	}

//	protected function onExiting()
//	{
//	}
}