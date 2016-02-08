<?php
/**
 * kephp bootstrap file.
 */

$kephpApp = 'D:\htdocs\kephp\src\Ke\App.php';

require $kephpApp;
require 'src/Demo/App.php';

/** @var \Ke\App $APP */
global $app;

try {
	$app = new \Demo\App(__DIR__);
}
catch (Throwable $throw) {
	print $throw->getMessage();
}
