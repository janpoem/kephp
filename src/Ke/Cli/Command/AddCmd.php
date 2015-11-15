<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/13
 * Time: 2:05
 */

namespace Ke\Cli\Command;


use Ke\Cli\Command;
use Ke\Cli\ReflectionCommand;

class AddCmd extends ReflectionCommand
{

	protected static $commandName = 'add_cmd';

	protected static $commandDescription = 'Add a cli command!';

	/**
	 * @var string
	 * @type string
	 * @require true
	 * @field   1
	 */
	protected $name = '';

	/**
	 * @var bool
	 * @type single
	 * @default true
	 */
	protected $isRef = true;

	private $selected = -1;

	private $prepareClasses = [];

	private $preparePaths = [];

	private $appNamespace = 'Cli';

	protected function prepareCommands(\Ke\Cli\Console $console)
	{
		if (!empty(KE_APP_NS))
			$this->appNamespace = KE_APP_NS . '\\' . trim($this->appNamespace, KE_PATH_NOISE);
		$index = 0;
		foreach ($console->mkCommands($this->name) as $command) {
			if (empty($command))
				continue;
			$this->prepareClasses[] = $class = $this->appNamespace . '\\' . str_replace('/', '\\', $command);
			$path = KE_SRC . DS . $class . '.php';
			if (!KE_IS_WIN)
				$path = str_replace('\\', '/', $path);
			$this->preparePaths[] = $path;
			$console->writeln("[{$index}]", $class);
			$index++;
		}
	}

	protected function onExecute(\Ke\Cli\Console $console, $argv = null)
	{
		$this->prepareCommands($console);
		while (true) {
			$console->write("Please choice the class name(input the number):", '');
			$this->selected = intval(trim(fgets(STDIN)));
			if (isset($this->prepareClasses[$this->selected])) {
				break 1;
			}
		}
		$console->write(
			"Creating class {$this->prepareClasses[$this->selected]} ({$this->preparePaths[$this->selected]}) ...",
			implode(PHP_EOL, $this->createClass()));
	}

	protected function createClass()
	{
		$class = $this->prepareClasses[$this->selected];
		$path = $this->preparePaths[$this->selected];
		$templatePath = __DIR__ . '/Templates/' . ($this->isRef ? 'ReflectionCommand.tp' : 'Command.tp');
		if (is_file($path)) {
			return ['Lost!', substitute('The file "{path}" is exists!', ['path' => $path])];
		}
		if (!is_file($templatePath))
			return ['Lost!', 'The template file "Command.tp" cannot found!'];

		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		$pos = strrpos($class, '\\');
		$pureClass = $class;
		$pureNamespace = $this->appNamespace;
		if ($pos !== false) {
			$pureClass = substr($class, $pos + 1);
			$pureNamespace = substr($class, 0, $pos);
		}
		$template = file_get_contents($templatePath);
		$vars = [
			'class'     => $pureClass,
			'command'   => $this->name,
			'path'      => remainAppRoot($path),
			'namespace' => $pureNamespace,
			'datetime'  => date('Y-m-d H:i'),
		];
		file_put_contents($path, substitute($template, $vars));
		return ['Success!', 'Please type: "php ' . KE_SCRIPT_FILE . ' ' . $this->name . ' --help"'];
	}
//
//	protected $name = 'add_cmd';
//
//	protected $description = 'Add cli command!';
//
//	protected $guide = [
//		'command' => [
//			'field' => 0,
//			'type'  => KE_STR,
//		],
//	];
//
//	private $template = '';
//
//	private $namespace = [];
//
//	private $classes = [];
//
//	private $paths = [];
//
//	private $selected = -1;
//
//	public function prepare(Console $console, Argv $argv)
//	{
//		if ($argv->command == null)
//			return $this->showHelp();
//		$this->template = file_get_contents(__DIR__ . './templates/Command.tp');
//		$this->namespace = empty(KE_APP_NS) ? 'Cli' : KE_APP_NS . '\\Cli';
//		$commands = $console->mkCommands($argv->command);
//		$values = array_values($commands);
//		foreach ($values as $index => $command) {
//			if (empty($command))
//				continue;
//			$this->classes[] = $class = $this->namespace . '\\' . str_replace('/', '\\', $command);
//			$path = KE_SRC . DS . $class . '.php';
//			if (!KE_IS_WIN)
//				$path = str_replace('\\', '/', $path);
//			$this->paths[] = $path;
//			$console->writeln("[{$index}]", $class);
//		}
//
//		while (true) {
//			$console->write("Please choice the class name(input the number):");
//			$this->selected = intval(trim(fgets(STDIN)));
//			if (isset($this->classes[$this->selected])) {
//				break 1;
//			}
//		}
//		$console->write("Creating command class {$this->classes[$this->selected]} ($argv->command) ...");
//		return $this->classes[$this->selected];
//	}
//
//	protected function onExecute(Console $console, Argv $argv)
//	{
//		$class = $this->prepare($console, $argv);
//		$path  = $this->paths[$this->selected];
//		if (is_file($path)) {
//			$console->writeln(PHP_EOL, 'The file "' . $path . '" is exists!');
//		} else {
//			$dir = dirname($path);
//			if (!is_dir($dir)) {
//				mkdir($dir, 0755, true);
//			}
//			$pos = strrpos($class, '\\');
//			$pureClass = $class;
//			$pureNamespace = $this->namespace;
//			if ($pos !== false) {
//				$pureClass = substr($class, $pos + 1);
//				$pureNamespace = substr($class, 0, $pos);
//			}
//			$vars = [
//				'class'     => $pureClass,
//				'command'   => $argv->command,
//				'path'      => remainAppRoot($path),
//				'namespace' => $pureNamespace,
//			    'datetime'  => date('Y-m-d H:i'),
//			];
//			file_put_contents($path, substitute($this->template, $vars));
//			$console->writeln(PHP_EOL, "Create command class {$class} ($path) success!");
//			$console->writeln(PHP_EOL, "Please try : php " . KE_SCRIPT_FILE . " {$argv->command}");
//		}
//
//	}
}