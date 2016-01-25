<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2016/1/25 0025
 * Time: 20:25
 */

namespace Ke\Cli\Cmd;


use Ke\App;
use Ke\Cli\ReflectionCommand;

class NewApp extends ReflectionCommand
{

	protected static $commandName = 'new_app';

	protected static $commandDescription = 'Create a new application!';

	protected $directories = [
		'type'     => 'dir',
		'children' => [
			'.gitignore'    => ['type' => 'file', 'tpl' => 'App/gitignore.tp'],
			'.htaccess'     => ['type' => 'file', 'tpl' => 'App/htaccess.tp'],
			'bootstrap.php' => ['type' => 'file', 'tpl' => 'App/bootstrap.tp'],
			'ke.php'        => ['type' => 'file', 'tpl' => 'App/kephp.tp'],
			'public'        => [
				'type'     => 'dir',
				'children' => [
					'.htaccess' => ['type' => 'file', 'tpl' => 'App/public_htaccess.tp'],
					'index.php' => ['type' => 'file', 'tpl' => 'App/public_index.tp'],
					'vendor'    => ['type' => 'dir'],
					'js'        => [
						'type'     => 'dir',
						'children' => [
							'app.js' => ['type' => 'file', 'tpl' => 'App/js_app.tp'],
							'page'   => ['type' => 'dir',],
						],
					],
					'css'       => [
						'type'     => 'dir',
//						'children' => [
//							'main.less' => ['type' => 'file', 'tpl' => 'App/less_main.tp'],
//							'less'      => [
//								'type'     => 'dir',
//								'children' => [
//									'common.less' => ['type' => 'file', 'tpl' => 'App/less_common.tp'],
//								],
//							],
//						],
					],
					'img'       => ['type' => 'dir'],
				],
			],
			'src'           => [
				'type'   => 'dir',
				'handle' => 'getNewAppSrcDirectories',
			],
			'config'        => [
				'type'     => 'dir',
				'children' => [
					'common.php'      => ['type' => 'file', 'tpl' => 'App/config_common.tp'],
					'development.php' => ['type' => 'file', 'tpl' => 'App/config_development.tp'],
					'test.php'        => ['type' => 'file', 'tpl' => 'App/config_test.tp'],
					'production.php'  => ['type' => 'file', 'tpl' => 'App/config_production.tp'],
					'references.php'  => ['type' => 'file', 'tpl' => 'App/config_references.tp'],
					'routes.php'      => ['type' => 'file', 'tpl' => 'App/config_routes.tp'],
				],
			],
		],
	];

	/**
	 * @var string
	 * @type string
	 * @require true
	 * @field   1
	 */
	protected $name = '';

	/**
	 * @var App
	 */
	protected $thisApp = null;

	protected $context = [
		'kephpLibEntry' => '',
		'appNamespace'  => '',
	];

	protected $appNamespace = '';

	protected function onPrepare($argv = null)
	{
		$this->thisApp = App::getApp();
		$root = $this->getNewAppDir();
		if (is_dir($root))
			throw new \Exception("Directory {$root} is existing!");
		$this->context['kephpLibEntry'] = realpath($this->thisApp->kephp('Ke/App.php'));
		$this->appNamespace = $this->context['appNamespace'] = path2class($this->name);
	}

	protected function onExecute($argv = null)
	{
		$this->entry($this->getNewAppDir(), $this->directories, null);
		$this->console->println("");
		$this->console->println("The app {$this->appNamespace} is created! Entry \"{$this->getNewAppDir()}\" ");

		chdir($this->getNewAppDir());
		passthru("php ke.php add controller index");
	}

	public function getAppParentDir()
	{
		return dirname($this->thisApp->root());
	}

	public function getNewAppDir()
	{
		return $this->getAppParentDir() . DS . $this->name;
	}

	public function entry(string $parent, array $data, $name = null)
	{
		if (!isset($data['type']))
			return $this;
		$path = $parent;
		if (!empty($name))
			$path .= DS . $name;
		if ($data['type'] === 'dir') {
			$this->createDir($path);
			if (!empty($data['children'])) {
				foreach ($data['children'] as $key => $item) {
					$this->entry($path, $item, $key);
				}
			}
		}
		elseif ($data['type'] === 'file') {
			$this->createFile($path, $data['tpl']);
		}
		if (isset($data['handle']) && is_callable([$this, $data['handle']])) {
			$handleData = call_user_func([$this, $data['handle']], $path);
			$this->entry($path, $handleData, null);
		}

		return $this;
	}

	public function createDir($dir)
	{
		if (!is_dir($dir) && mkdir($dir, 0777, true)) {
			$this->console->println("create dir  {$dir} success!");
		}
		else {
			$this->console->println("create dir  {$dir} lost!");
		}
	}

	public function createFile($file, string $tpl = null)
	{
		$this->console->println("create file {$file}");
		$tpl = __DIR__ . '/Templates/' . $tpl;
		if (is_file($tpl)) {
			$tplContent = file_get_contents($tpl);
			predir($file);
			if (file_put_contents($file, substitute($tplContent, $this->context))) {
				$this->console->println("create file {$file} success!");
			}
			else {
				$this->console->println("create file {$file} lost!");
			}
		}
	}

	public function getNewAppSrcDirectories(string $parent)
	{
		$data = [
			'type'     => 'dir',
			'children' => [
				$this->context['appNamespace'] => [
					'type'     => 'dir',
					'children' => [
						'App.php'    => ['type' => 'file', 'tpl' => 'App/App.tp'],
						'Model'      => ['type' => 'dir'],
						'Controller' => ['type' => 'dir'],
						'Component'  => ['type' => 'dir'],
						'View'       => ['type' => 'dir'],
					],
				],
			],
		];
		return $data;
	}
}