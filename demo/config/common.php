<?php
/**
 * kephp common config file.
 */

use Ke\Web\Asset;

Asset::getInstance()->setLibraries([
	'all-js'          => [
		['//cdn.bootcss.com/jquery/3.0.0-beta1/jquery.js', 'js'],
		['//cdn.bootcss.com/semantic-ui/2.1.8/semantic.min.js', 'js'],
		['js/app.js', 'js',],
	],
	'header'          => [
		['//cdn.bootcss.com/semantic-ui/2.1.8/semantic.min.css', 'css'],
	],
	'footer'          => [
		'all-js',
	],
]);

