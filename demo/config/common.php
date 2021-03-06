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

use \Ke\Utils\DocMen\DocMen;

global $app;

$docs = [
	new DocMen($app->path('doc/kephp2'), $app->kephp(), 'doc', function() {
		$this->setGenerable(false);
		$this->setTitle('kephp');
	}),
];

$phpExcelSrc = 'D:\xampp\htdocs\sk-917917\917917.cc\library\PHPExcel';
if (is_dir($phpExcelSrc)) {
	$docs[] = new DocMen($app->path('doc/php_excel'), $phpExcelSrc, 'php_excel', function () {
		$this->setScannerOptions([
			\Ke\Utils\DocMen\SourceScanner::OPS_AUTO_IMPORT => false,
			\Ke\Utils\DocMen\SourceScanner::OPS_NS_STYLE    => DocMen::NS_STYLE_OLD_PEAR,

		]);
		require $this->source . '.php';
	});
}

$agimvcSrc = 'D:\xampp\htdocs\sk-917917\917917.cc\library\agimvc';
if (is_dir($agimvcSrc)) {
	$docs[] = new DocMen($app->path('doc/agimvc'), $agimvcSrc, 'agimvc', function () {
		$this->setGenerable(false);
		$this->setScannerOptions([
			\Ke\Utils\DocMen\SourceScanner::OPS_AUTO_IMPORT     => false,
			\Ke\Utils\DocMen\SourceScanner::OPS_NS_STYLE        => DocMen::NS_STYLE_MIXED,
			\Ke\Utils\DocMen\SourceScanner::OPS_NOT_PARSE_FILES => [
				'#bootstrap(_.*)?.php$#',
				'#\\\\agimvc[34]\\\\(.*)#',
				'#\\\\languages\\\\(.*)#',
				'#MongoCursor.php#',
			],
		]);
		$this->setShowFile(false);
		$classes = import($this->source . '/classes_docmen.php');
		foreach ($classes as $class => $path) {
			$classes[$class] = $this->source . '/' . $path;
		}
		global $app;
		$app->getLoader()->setClassPaths($classes);
		import([
			$this->source . '/bootstrap_docmen.php',
			$this->source . '/MST/Core.php',
		]);
		define('HTTP_BASE', '/');
	});
}

DocMen::register(...$docs);
