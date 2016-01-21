<?php

namespace Agi\Route;


/**
 * Class Result
 *
 * @package Agi\Route
 * @author Janpoem created at 2014/9/22 19:08
 */
class Result
{
    /** @var Router */
    public $router;

    /** @var \Agi\Http\Request */
    public $request;

    /** @var string 用于进行匹配的url path */
    public $path = SPR_HTTP_DIR;

    /** @var null|string 匹配的module的name */
    public $module = null;

//	/** @var null|string 匹配的module的namespace, 用于生成controllerName */
//	public $namespace = null;

    /** @var bool 是否命中，一般情况下，是不可能存在不命中的情况的 */
    public $matched = false;

    /** @var bool 是否baseMapping */
    public $baseMapping = false;

    /** @var null|array path匹配到的参数 */
    public $matches = null;

    /** @var array 构成ActionParams的最终参数 */
    public $params = array();

}

 