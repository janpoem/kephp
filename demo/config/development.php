<?php
/**
 * kephp development env config file.
 */

use \Ke\Adm;

// Database config
Adm\Db::define([
	'default' => [
		'adapter' => 'mysql',
		'db'      => 'ke_demo',
		'user'    => 'root',
		'prefix'  => 'ke',
	],
]);

// Cache config
Adm\Cache::define([
	'default' => [
		'adapter'    => 'redis',
		'prefix'     => 'ke_demo',
		'host'       => '127.0.0.1',
		'port'       => 6379,
		'pconnect'   => true,
		'db'         => 2,
//		'serializer' => Redis::SERIALIZER_PHP,
	],
]);