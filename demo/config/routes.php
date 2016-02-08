<?php
/**
 * @var \Ke\Web\Route\Router $router
 * App routes config file.
 */

$router->routes = [
	'*'              => [
//		'controller' => 'index',
	],
	'docmen' => [
		'class' => Ke\Utils\DocMen\DocController::class,
	],
//	'path_class'     => [
//		'class'  => 'classFullName',
//		'action' => 'ok',
//	],
];
