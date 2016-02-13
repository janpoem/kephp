<?php
/**
 * kephp development env config file.
 */

use \Ke\Adm;
use \Ke\Utils\DocMen\DocMen;

global $app;

DocMen::register(
	new DocMen($app->path('doc/kephp2'), $app->kephp(), 'docmen'),
	new DocMen($app->path('doc/demo'), $app->src(), 'demo_doc'));

// Database config
//Adm\Db::define([
//	'default' => [
//		'adapter' => 'mysql',
//		'db'      => '',
//		'user'    => '',
//		'prefix'  => '',
//	],
//]);

// Cache config
//Adm\Cache::define([
//	'default' => [
//		'adapter' => 'redis',
//	],
//]);