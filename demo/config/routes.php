<?php
/**
 * @var \Ke\Web\Route\Router $router
 * App routes config file.
 */

//$router->routes = [
//	'*'      => [
//		'controller' => 'index',
//	],
//	'docmen' => [
//		'controller' => 'mydoc',
//		'namespace'  => '',
//	],
//];

//$router->routes['docmen']['class']

// 拦截route，并重载
$router->setNode('docmen', [
	'class'      => null,
	'controller' => 'mydoc',
	'namespace'  => '',
]);

