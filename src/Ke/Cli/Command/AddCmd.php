<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/13
 * Time: 2:05
 */

namespace Ke\Cli\Command;

use Ke\Cli\Command;
use Ke\Cli\Console;
use Ke\Cli\Argv;

class AddCmd extends Command
{

	protected $name = 'add_cmd';

	protected $description = 'Add cli command!';

	protected $guide = [
		'command' => [
			'field' => 0,
			'type'  => KE_STR,
		],
	];

	private $template = '';

	private $namespace = [];

	private $classes = [];

	private $paths = [];

	private $selected = -1;

	public function prepare(Console $console, Argv $argv)
	{
		if ($argv->command == null)
			return $this->showHelp();
		$this->template = file_get_contents(__DIR__ . './templates/Command.tp');
		$this->namespace = empty(KE_APP_NS) ? 'Cli' : KE_APP_NS . '\\Cli';
		$commands = $console->mkCommands($argv->command);
		$values = array_values($commands);
		foreach ($values as $index => $command) {
			if (empty($command))
				continue;
			$this->classes[] = $class = $this->namespace . '\\' . str_replace('/', '\\', $command);
			$path = KE_SRC . DS . $class . '.php';
			if (!KE_IS_WIN)
				$path = str_replace('\\', '/', $path);
			$this->paths[] = $path;
			$console->writeln("[{$index}]", $class);
		}

		while (true) {
			$console->write("Please choice the class name(input the number):");
			$this->selected = intval(trim(fgets(STDIN)));
			if (isset($this->classes[$this->selected])) {
				break 1;
			}
		}
		$console->write("Creating command class {$this->classes[$this->selected]} ($argv->command) ...");
		return $this->classes[$this->selected];
	}

	protected function onExecute(Console $console, Argv $argv)
	{
		$class = $this->prepare($console, $argv);
		$path  = $this->paths[$this->selected];
		if (is_file($path)) {
			$console->writeln(PHP_EOL, 'The file "' . $path . '" is exists!');
		} else {
			$dir = dirname($path);
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
			$pos = strrpos($class, '\\');
			$pureClass = $class;
			$pureNamespace = $this->namespace;
			if ($pos !== false) {
				$pureClass = substr($class, $pos + 1);
				$pureNamespace = substr($class, 0, $pos);
			}
			$vars = [
				'class'     => $pureClass,
				'command'   => $argv->command,
				'path'      => remainAppRoot($path),
				'namespace' => $pureNamespace,
			    'datetime'  => date('Y-m-d H:i'),
			];
			file_put_contents($path, substitute($this->template, $vars));
			$console->writeln(PHP_EOL, "Create command class {$class} ($path) success!");
			$console->writeln(PHP_EOL, "Please try : php " . KE_SCRIPT_FILE . " {$argv->command}");
		}

	}
}