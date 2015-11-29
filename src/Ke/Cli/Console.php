<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/9 0009
 * Time: 9:13
 */

namespace Ke\Cli;

use Ke\App;
use Ke\Exception;

use Ke\ContextImpl;
use Ke\InputImpl;
use Ke\OutputImpl;

use Ke\Logging\Log;
use Ke\Logging\LoggerOps;


/**
 * CLI命令行的上下文环境
 *
 *
 * @package Ke\Cli
 * @property \Ke\App          $app
 * @property Writer|InputImpl $output
 * @property Argv|OutputImpl  $input
 */
class Console implements ContextImpl
{

	use LoggerOps;

	/** @var Console */
	private static $context = null;

	private static $defaultScopes = null;

	/** @var \Ke\App */
	private $app = null;

	/** @var Argv */
	private $argv = null;

	/** @var Writer */
	private $writer = null;

	private $scopes = null;

	public static function getContext($argv = null)
	{
		if (!isset(self::$context)) {
			self::$context = new static($argv);
		}
		return self::$context;
	}

	public static function getDefaultScopes()
	{
		if (!isset(self::$defaultScopes)) {
			$appNs = empty(KE_APP_NS) ? 'Cli' : KE_APP_NS . '\\Cli';
			self::$defaultScopes = [
				$appNs             => KE_APP_NS_PATH . DS . 'Cli',
				'Ke\\Cli\\Command' => __DIR__ . DS . 'Command',
			];
		}
		return self::$defaultScopes;
	}

	final public function __construct(Argv $argv = null)
	{
		// 绑定当前的默认的上下文环境实例
		if (!isset(self::$context))
			self::$context = $this;
		// 取出默认的命令行的有效范围
		$this->scopes = static::getDefaultScopes();
		// 初始化日志配置
		$this->initLogger('cli');
		// 将错误和异常处理，从App中接管过来。
		set_error_handler([$this, 'errorHandle']);
		set_exception_handler([$this, 'exceptionHandle']);
		if (isset($argv)) {
			$this->setInput($argv);
		}
		$this->onConstruct();
	}

	protected function onConstruct()
	{
	}

	public function __get($field)
	{
		if ($field === 'app') {
			if (!isset($this->app))
				$this->app = App::getApp();
			return $this->app;
		} elseif ($field === 'output') {
			if (!isset($this->writer))
				$this->getOutput();
			return $this->writer;
		} elseif ($field === 'input') {
			if (!isset($this->argv))
				$this->getInput();
			return $this->argv;
		} else {
			return isset($this->{$field}) ? $this->{$field} : false;
		}
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
		if (!isset($this->argv))
			$this->argv = Argv::current();
		return $this->argv;
	}

	public function setOutput(OutputImpl $output)
	{
		$this->writer = $output;
		return $this;
	}

	public function getOutput()
	{
		if (!isset($this->writer))
			$this->writer = new Writer($this);
		return $this->writer;
	}

	public function write()
	{
		if (!isset($this->writer))
			$this->getOutput();
		$args = func_get_args();
		if (empty($args))
			$args = ' ';
		else
			foreach ($args as &$item) {
				$item = print_r($item, true);
			}
		$this->writer->output(implode(' ', $args));
		return $this;
	}

	public function writeln()
	{
		if (!isset($this->writer))
			$this->getOutput();
		$args = func_get_args();
		if (empty($args))
			$args = ' ';
		else
			foreach ($args as &$item) {
				$item = print_r($item, true);
			}
		$this->writer->output(implode(' ', $args), true);
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
			$argv = $this->getInput();
		if (empty($argv[0]))
			throw new Exception('No command found in argv.');
		$class = '';
		$path = '';
		foreach ($this->scopes as $ns => $dir) {
			foreach ($this->mkCommands($argv[0]) as $command) {
				$path = $dir . DS . $command . '.php';
				if (is_file($path)) {
					$class = $ns . '\\' . str_replace('/', '\\', $command);
					break;
				}
			}
			if (!empty($class))
				break;
		}
		if (empty($class)) {
			throw new Exception('No command detected about "{command}"!', ['command' => $argv[0]]);
		}
		$this->info('Detecting "' . $argv[0] . '" to class "' . $class . '"!');
		require $path;
		if (!class_exists($class, false))
			throw new Exception('Undefined command class {class}!', ['class' => $class]);
		if (!is_subclass_of($class, Command::class))
			throw new Exception('Command class {class} doest not extend with {parent}!', [
				'class'  => $class,
				'parent' => Command::class,
			]);
		/** @var Command $cmd */
		return new $class($argv);
	}

	public function mkCommands($command)
	{
		$result = [];
		if (empty($command))
			return $result;
		$base = str_replace([
			'\\',
			'-',
			'.',
		], [
			'/',
			'_',
			'_',
		], trim($command, KE_PATH_NOISE));
		$result[$base] = $base;
		$lower = strtolower($command);
		if (!isset($result[$lower]))
			$result[$lower] = $lower;
		$lowerNoUnder = str_replace('_', '', $lower);
		if (!isset($result[$lowerNoUnder]))
			$result[$lowerNoUnder] = $lowerNoUnder;
		// 这里暂时这么处理
		$camelCase = preg_replace_callback('#([\-\_\/\.\\\\])([a-z])#', function ($matches) {
			if ($matches[1] === '/' || $matches[1] === '\\')
				return strtoupper($matches[0]);
			else
				return '_' . strtoupper($matches[2]);
		}, ucfirst($lower));
		if (!isset($result[$camelCase]))
			$result[$camelCase] = $camelCase;
		$camelCaseNoUnder = str_replace('_', '', $camelCase);
		if (!isset($result[$camelCaseNoUnder]))
			$result[$camelCaseNoUnder] = $camelCaseNoUnder;
		return $result;
	}
}