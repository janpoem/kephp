<?php
/**
 * realpath not work in phar pack file
 */

$paths = [
	'phar://phar_test.phar/hello',
	'phar://phar_test.phar/hello/a.php',
];

function let_it_work(string $path)
{
	$realPath = realpath($path);
	if ($realPath !== false) {
		$path = $realPath;
	}
	return $path;
}

function show_false(string $path)
{
	return realpath($path);
}

foreach ($paths as $path) {
	var_dump(file_exists($path)); // return true
	var_dump(realpath($path)); // return false
	var_dump(let_it_work($path)); // it's work
	var_dump(show_false($path)); // return false
}

function entry(DirectoryIterator $dir = null)
{
	if ($dir === null)
		$dir = new DirectoryIterator(__DIR__);
	foreach ($dir as $item) {
		$path = $item->getPathname();
		var_dump(file_exists($path)); // true
		var_dump($item->getRealPath()); // false
	}
}

entry(new DirectoryIterator('phar://phar_test.phar'));