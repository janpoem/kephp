<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/9 0009
 * Time: 9:13
 */

namespace Ke\Cli;

use Ke\App;
use Ke\ContextImpl;
use Ke\Exception;
use Ke\InputImpl;
use Ke\Logging\Log;
use Ke\Logging\LogBuffer;
use Ke\Logging\LogLevel;
use Ke\OutputBuffer;
use Ke\Logging\LoggerOps;
use Ke\OutputImpl;

/**
 * CLI命令行的上下文环境
 *
 *
 * @package Ke\Cli
 */
class Console implements ContextImpl
{

	use LoggerOps;

	/** @var Console */
	private static $context = null;

	/** @var \Ke\App */
	public $app = null;

	/** @var \Ke\OutputBuffer */
	public $ob = null;

	/** @var Argv */
	public $argv = null;

	/** @var Writer */
	public $writer = null;

	public static function getContext()
	{
		if (!isset(self::$context))
			self::$context = new static();
		return self::$context;
	}

	public function __construct(Argv $argv = null)
	{
		if (!isset($argv))
			$argv = Argv::current();
		$this->app = App::getApp();
		$this->ob = OutputBuffer::getInstance()->start('cli');
		$this->setInput($argv);
		if (!isset(self::$context))
			self::$context = $this;
		$this->initLogger('cli');
		// 将错误和异常处理，从App中接管过来。
		set_error_handler([$this, 'errorHandle']);
		set_exception_handler([$this, 'exceptionHandle']);
		$this->setOutput(new Writer());
	}

	/**
	 * PHP错误的处理的接管函数
	 */
	public function errorHandle($errno, $msg, $errfile, $errline, $errcontext)
	{
		$msg = getPhpErrorStr($errno) . ' - ' . $msg;
		$this->error($msg);
	}

	/**
	 * PHP异常处理的接管函数
	 *
	 * @param \Exception $ex
	 */
	public function exceptionHandle(\Exception $ex)
	{
		$this->error($ex);
	}

	public function onLogging(array &$raw)
	{
		$this->writeln(Log::prepareLog($raw, true));
	}

	public function setInput(InputImpl $input)
	{
		$this->argv = $input;
		return $this;
	}

	public function getInput()
	{
		return $this->argv;
	}

	public function setOutput(OutputImpl $output)
	{
		$this->writer = $output;
		return $this;
	}

	public function getOutput()
	{
		return $this->writer;
	}

	public function write()
	{
		call_user_func_array([$this->writer, 'output'], func_get_args());
		return $this;
	}

	public function writeln()
	{
		call_user_func_array([$this->writer, 'output'], func_get_args());
		echo PHP_EOL;
		return $this;
	}

	/**
	 * @param Argv|null $argv
	 * @return Command
	 * @throws Exception
	 */
	public function detectCommand(Argv $argv = null)
	{
		if (!isset($argv))
			$argv = $this->argv;
		$dirs = [
			'Ke\\Cli\\Command'  => __DIR__ . DS . 'Command',
			KE_APP_NS . '\\Cli' => KE_APP_NS_PATH . DS . 'Cli',
		];
		$class = '';
		$path = '';
		foreach ($dirs as $ns => $dir) {
			foreach ($argv->mkCommands() as $command) {
				$path = $dir . DS . $command . '.php';
				if (is_file($path)) {
					$class = $ns . '\\' . $command;
					break;
				}
			}
			if (!empty($class))
				break;
		}
		$command = $argv->getCommand();
		if (empty($class)) {
			throw new Exception('No command detected about "{cmd}"!', ['cmd' => $command]);
		}
		$this->info('Detecting "' . $command . '" to class "' . $class . '"!');
		require $path;
		if (!class_exists($class, false))
			throw new Exception('Undefined command class {class}!', ['class' => $class]);
		if (!is_subclass_of($class, Command::class))
			throw new Exception('Command class {class} doest not extend with {parent}!', ['class'  => $class,
			                                                                              'parent' => Command::class,
			]);
		/** @var Command $cmd */
		return new $class($argv);
	}


}