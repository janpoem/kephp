<?php
/**
 * kephp common config file.
 */

use Ke\Web\Asset;

Asset::getInstance()->setLibraries([
	'all-js'          => [
//		['js/app.js', 'js',],
	],
	'header'          => [
	],
	'docmen'          => [
		['vendor/semantic/semantic', 'css'],
		['vendor/jquery-1.11.1', 'js'],
		['vendor/semantic/semantic', 'js'],
		['vendor/marked', 'js'],
	    'prism',
	],
	'prism'       => [
		['vendor/prism/prism', 'css', ['id' => 'prism_theme_css']],
		['vendor/prism/prism', 'js'],
	],
	'footer'          => [
//		'all-js',
	],
]);

