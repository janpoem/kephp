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
use Ke\InputImpl;
use Ke\Logging\Log;
use Ke\Logging\LoggerAward;
use Ke\OutputBuffer;
use Ke\Logging\LoggerOps;
use Ke\OutputImpl;

/**
 * CLI命令行的上下文环境
 *
 *
 * @package Ke\Cli
 */
class Cli implements ContextImpl
{

	use LoggerOps;

	/** @var Cli */
	private static $context = null;

	/** @var \Ke\App */
	public $app = null;

	/** @var \Ke\OutputBuffer */
	public $ob = null;

	/** @var Args */
	public $args = null;

	/** @var Writer */
	public $writer = null;

	public static function context()
	{
		if (!isset(self::$context))
			self::$context = new static();
		return self::$context;
	}

	public function __construct(Args $args = null)
	{
		if (!isset($args))
			$args = Args::current();
		$this->app = App::getApp();
		$this->ob = OutputBuffer::getInstance()->start('cli');
		$this->setInput($args);
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
		$this->args = $input;
		return $this;
	}

	public function getInput()
	{
		return $this->args;
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
		$args = func_get_args();
		$args[] = PHP_EOL;
		call_user_func_array([$this->writer, 'output'], $args);
		return $this;
	}

	public function findCommand(Args $args = null)
	{
		if (!isset($args))
			$args = $this->args;
		$path = null;
		foreach ($args->getCommands() as $command) {
			$temp = __DIR__ . DS . 'Command' . DS . $command . '.php';
			$this->writeln($temp);
			if (is_file($temp)) {
				$path = $temp;
				break;
			}
			$temp = KE_APP_NS_PATH . DS . 'Cli' . DS . $command . '.php';
			$this->writeln($temp);
		}
	}

	public function execute($command)
	{
	}
}