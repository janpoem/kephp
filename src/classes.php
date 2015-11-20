<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

return [
	// 基础类库
	'Ke\\App'                              => __DIR__ . '/Ke/App.php',
	'Ke\\Bootstrap'                        => __DIR__ . '/Ke/Bootstrap.php',
	'Ke\\DataRegistry'                     => __DIR__ . '/Ke/DataRegistry.php',
	'Ke\\ClassLoader'                      => __DIR__ . '/Ke/ClassLoader.php',
	'Ke\\Exception'                        => __DIR__ . '/Ke/Exception.php',
	'Ke\\OutputBuffer'                     => __DIR__ . '/Ke/OutputBuffer.php',
	'Ke\\Uri'                              => __DIR__ . '/Ke/Uri.php',
	// Cli命令环境
	'Ke\\Cli\\Console'                     => __DIR__ . '/Ke/Cli/Console.php',
	'Ke\\Cli\\Argv'                        => __DIR__ . '/Ke/Cli/Argv.php',
	'Ke\\Cli\\Command'                     => __DIR__ . '/Ke/Cli/Command.php',
	'Ke\\Cli\\ReflectionCommand'           => __DIR__ . '/Ke/Cli/ReflectionCommand.php',
	'Ke\\Cli\\Writer'                      => __DIR__ . '/Ke/Cli/Writer.php',
	// 日志
	'Ke\\Logging\\Log'                     => __DIR__ . '/Ke/Logging/Log.php',
	'Ke\\Logging\\LogLevel'                => __DIR__ . '/Ke/Logging/LogLevel.php',
	'Ke\\Logging\\LogBuffer'               => __DIR__ . '/Ke/Logging/LogBuffer.php',
	'Ke\\Logging\\LoggerImpl'              => __DIR__ . '/Ke/Logging/Logger.php',
	'Ke\\Logging\\LoggerOps'               => __DIR__ . '/Ke/Logging/Logger.php',
	'Ke\\Logging\\LoggerAward'             => __DIR__ . '/Ke/Logging/Logger.php',
	'Ke\\Logging\\BaseLogger'              => __DIR__ . '/Ke/Logging/Logger.php',
	// 调试
	'Ke\\Debug\\Benchmark'                 => __DIR__ . '/Ke/Debug/Benchmark.php',
	'Ke\\Debug\\Profiler'                  => __DIR__ . '/Ke/Debug/Profiler.php',
	//
	'Ke\\Adm\\Exception'                   => __DIR__ . '/Ke/Adm/Exception.php',
	'Ke\\Adm\\Source'                      => __DIR__ . '/Ke/Adm/Source.php',
	'Ke\\Adm\\Db'                          => __DIR__ . '/Ke/Adm/Source.php',
	'Ke\\Adm\\Cache'                       => __DIR__ . '/Ke/Adm/Source.php',
	'Ke\\Adm\\SqlQuery'                    => __DIR__ . '/Ke/Adm/SqlQuery.php',
	'Ke\\Adm\\Adapter\\CacheStoreImpl'     => __DIR__ . '/Ke/Adm/Adapter/CacheStoreImpl.php',
	'Ke\\Adm\\Adapter\\DatabaseImpl'       => __DIR__ . '/Ke/Adm/Adapter/DatabaseImpl.php',
	'Ke\\Adm\\Adapter\\PdoAbs'             => __DIR__ . '/Ke/Adm/Adapter/PdoAbs.php',
	'Ke\\Adm\\Adapter\\PdoMySQL'           => __DIR__ . '/Ke/Adm/Adapter/PdoMySQL.php',

	//
	'Ke\\Adm\\Query\\Query'                => __DIR__ . '/Ke/Adm/Query/Query.php',
	'Ke\\Adm\\Query\\Select'               => __DIR__ . '/Ke/Adm/Query/Select.php',
	'Ke\\Adm\\Query\\Update'               => __DIR__ . '/Ke/Adm/Query/Update.php',
	'Ke\\Adm\\Query\\Delete'               => __DIR__ . '/Ke/Adm/Query/Delete.php',
	'Ke\\Adm\\Query\\Insert'               => __DIR__ . '/Ke/Adm/Query/Insert.php',
	'Ke\\Adm\\Query\\Builder\\BuilderImpl' => __DIR__ . '/Ke/Adm/Query/Builder/BuilderImpl.php',
	'Ke\\Adm\\Query\\Builder\\MySQL'       => __DIR__ . '/Ke/Adm/Query/Builder/MySQL.php',
];