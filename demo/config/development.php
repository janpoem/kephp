<?php
/**
 * kephp development env config file.
 */

use \Ke\Adm;
use \Ke\Utils\DocMen\DocMen;

//DocMen::getInstance('doc')->setShowFile(true)->setGenerable(true)->setWithWiki(true);

// Database config
Adm\Db::define([
	'default' => [
		'adapter' => 'mysql',
		'db'      => '',
		'user'    => '',
		'prefix'  => '',
	],
]);

// Cache config
Adm\Cache::define([
	'default' => [
		'adapter' => 'redis',
	],
]);