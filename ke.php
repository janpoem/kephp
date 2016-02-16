<?php
/**
 * kephp global cli entry file.
 */

if (PHP_SAPI !== 'cli')
	exit('This file can only be used in CLI mode!');

$cwd = getcwd();
$found = false;
if (dirname($_SERVER['SCRIPT_FILENAME']) !== $cwd) {
	foreach (['ke.php', 'kephp.phar'] as $file) {
		$file = $cwd . '/' . $file;
		if (is_file(($file))) {
			try {
				require $file;
				$found = $file;
			}
			catch (Throwable $thrown) {
				// something wrong in here.
				$found = false;
			}
			break;
		}
	}
}

if ($found === false) {
	require __DIR__ . '/src/Ke/App.php';

	$app = new \Ke\App(__DIR__);

	\Ke\Cli\Console::getConsole()->seekCommand()->execute();
}
