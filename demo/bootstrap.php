<?php
/**
 * kephp bootstrap file.
 */


require __DIR__ . '/../src/Ke/App.php';
require 'src/Demo/App.php';

/** @var \Ke\App $APP */
global $app;

try {
	$app = new \Demo\App(__DIR__);
//	var_dump(\Demo\App::getApp());
}
catch (Throwable $throw) {
	print $throw->getMessage();
}
