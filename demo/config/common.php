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
	'footer'          => [
//		'all-js',
	],
]);

