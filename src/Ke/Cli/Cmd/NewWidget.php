<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/22 0022
 * Time: 20:14
 */

namespace Ke\Cli\Cmd;


use Ke\Cli\ReflectionCommand;
use Ke\Web\Web;

class NewWidget extends ReflectionCommand
{

	protected static $commandName = 'newWidget';

	protected static $commandDescription = '';

	/**
	 * @var string
	 * @type string
	 * @require true
	 * @field   1
	 */
	protected $name = '';

	/** @var Web */
	protected $web = null;

	protected $dir = null;

	protected $desc = 'widget';

	protected $template = 'Widget.tp';

	protected function onPrepare($argv = null)
	{
		$this->web = Web::getWeb();
		$dirs = $this->web->component->getScopeDirs('widget');
		if (!isset($dirs['appComponent']))
			throw new \Exception("Unknown layout folder!");
		$this->dir = $dirs['appComponent'];

		if (is_file($this->getPath()))
			throw  new \Exception("File {$this->getPath()} is existing!");
	}

	protected function onExecute($argv = null)
	{
		if (file_put_contents($this->getPath(true), $this->buildContent())) {
			$this->console->println("Add {$this->desc} '{$this->getPath()}' success!");
		}
		else {
			$this->console->println("Add {$this->desc} '{$this->getPath()}' lost, please try again.");
		}
	}

	public function getPath(bool $checkDir = false)
	{
		$path = $this->dir . DS . $this->name . '.phtml';
		if ($checkDir) {
			$dir = dirname($path);
			if (!is_dir($dir))
				mkdir($dir, 0755, true);
		}
		return $path;
	}

	public function getTemplatePath(): string
	{
		$tpl = '/Templates/' . $this->template;
		$scopes = $this->console->getAppCommandScopes();
		foreach ($scopes as $ns => $dir) {
			if (real_file($path = $dir . $tpl)) {
				return $path;
			}
		}
		return __DIR__ . $tpl;
	}

	public function buildContent(): string
	{
		$tpl = $this->getTemplatePath();
		$content = file_get_contents($tpl);
		return $content;
	}
}