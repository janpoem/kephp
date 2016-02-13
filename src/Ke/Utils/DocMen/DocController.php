<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Utils\DocMen;

use Ke\App;
use Ke\Cli\Cmd\ScanSource;
use Ke\Web\Controller;

class DocController extends Controller
{

	/** @var DocMen */
	public $doc;

	/** @var DocHtml */
	public $html;

	public $layout = 'widget/docmen/layout';

	public $scope = '';

	public $name = '';

	protected function onConstruct()
	{
		if (!isset($this->doc) && !($this->doc instanceof DocMen))
			$this->doc = DocMen::getInstance($this->web->getController());
		$this->doc->prepare();
		list($this->scope, $this->name) = $this->doc->filterParams($this->web->params());
		if (!isset($this->html) && !($this->html instanceof DocHtml))
			$this->html = new DocHtml();
		$this->html->setDoc($this->doc);
		$this->web->setHtml($this->html);
	}

	public function index()
	{
		$this->view('widget/docmen/show');
	}

	public function show()
	{
		$this->view('widget/docmen/show');
	}

	public function generate()
	{
		try {
			$scanner = new SourceScanner($this->doc->getSourceDir(), $this->doc->getExportDir());
			$startParse = microtime();
			$scanner->start();
			$startWrite = microtime();
			$scanner->export();
			$this->status(true, implode(' ', [
				"Parse", count($scanner->getFiles()), 'files used', round(diff_milli($startParse), 4), 'ms,',
				'total parsed', count($scanner->getClasses()), 'classes,',
				count($scanner->getNamespaces()), 'namespaces,',
				count($scanner->getFunctions()), 'functions,',
				"write all data", 'used', round(diff_milli($startWrite), 4), 'ms'
			]));
		}
		catch (\Throwable $thrown) {
			$this->status(false, $thrown->getMessage());
		}
	}
}