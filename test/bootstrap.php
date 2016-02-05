<?php

require __DIR__ . '/../src/Ke/App.php';

/** @var \Ke\App $APP */
global $app;

try {
	$app = new \Test\App(__DIR__);
	$app->init();
}
catch (Throwable $throw) {
	echo $throw->getMessage();
}
