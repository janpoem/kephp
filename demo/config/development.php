<?php
/**
 * kephp development env config file.
 */

use \Ke\Adm;
use \Ke\Utils\DocMen\DocMen;

DocMen::getInstance('kephp')->setShowFile(true)->setGenerable(true);

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