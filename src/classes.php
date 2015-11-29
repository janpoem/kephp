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
	'Ke\\App'                                  => __DIR__ . '/Ke/App.php',
	'Ke\\Bootstrap'                            => __DIR__ . '/Ke/Bootstrap.php',
	'Ke\\DataRegistry'                         => __DIR__ . '/Ke/DataRegistry.php',
	'Ke\\ClassLoader'                          => __DIR__ . '/Ke/ClassLoader.php',
	'Ke\\Exception'                            => __DIR__ . '/Ke/Exception.php',
	'Ke\\OutputBuffer'                         => __DIR__ . '/Ke/OutputBuffer.php',
	'Ke\\Uri'                                  => __DIR__ . '/Ke/Uri.php',
	// Cli命令环境
	'Ke\\Cli\\Console'                         => __DIR__ . '/Ke/Cli/Console.php',
	'Ke\\Cli\\Argv'                            => __DIR__ . '/Ke/Cli/Argv.php',
	'Ke\\Cli\\Command'                         => __DIR__ . '/Ke/Cli/Command.php',
	'Ke\\Cli\\ReflectionCommand'               => __DIR__ . '/Ke/Cli/ReflectionCommand.php',
	'Ke\\Cli\\Writer'                          => __DIR__ . '/Ke/Cli/Writer.php',
	// 日志
	'Ke\\Logging\\Log'                         => __DIR__ . '/Ke/Logging/Log.php',
	'Ke\\Logging\\LogLevel'                    => __DIR__ . '/Ke/Logging/LogLevel.php',
	'Ke\\Logging\\LogBuffer'                   => __DIR__ . '/Ke/Logging/LogBuffer.php',
	'Ke\\Logging\\LoggerImpl'                  => __DIR__ . '/Ke/Logging/Logger.php',
	'Ke\\Logging\\LoggerOps'                   => __DIR__ . '/Ke/Logging/Logger.php',
	'Ke\\Logging\\LoggerAward'                 => __DIR__ . '/Ke/Logging/Logger.php',
	'Ke\\Logging\\BaseLogger'                  => __DIR__ . '/Ke/Logging/Logger.php',
	// 调试
	'Ke\\Debug\\Benchmark'                     => __DIR__ . '/Ke/Debug/Benchmark.php',
	'Ke\\Debug\\Profiler'                      => __DIR__ . '/Ke/Debug/Profiler.php',
	//
	'Ke\\Adm\\Exception'                       => __DIR__ . '/Ke/Adm/Exception.php',
	'Ke\\Adm\\DbSource'                        => __DIR__ . '/Ke/Adm/DataSource.php',
	'Ke\\Adm\\CacheSource'                     => __DIR__ . '/Ke/Adm/DataSource.php',
	'Ke\\Adm\\Model'                           => __DIR__ . '/Ke/Adm/Model.php',
	'Ke\\Adm\\DataList'                        => __DIR__ . '/Ke/Adm/DataList.php',
	// Utils
	'Ke\\Adm\\Utils\\Filter'                   => __DIR__ . '/Ke/Adm/Utils/Filter.php',
	'Ke\\Adm\\Utils\\Validator'                => __DIR__ . '/Ke/Adm/Utils/Validator.php',
	'Ke\\Adm\\Utils\\SqlQuery'                 => __DIR__ . '/Ke/Adm/Utils/SqlQuery.php',
	// Adapter
	'Ke\\Adm\\Adapter\\DatabaseImpl'           => __DIR__ . '/Ke/Adm/Adapter/DatabaseImpl.php',
	'Ke\\Adm\\Adapter\\Database\\PdoAbs'       => __DIR__ . '/Ke/Adm/Adapter/Database/PdoAbs.php',
	'Ke\\Adm\\Adapter\\Database\\PdoMySQL'     => __DIR__ . '/Ke/Adm/Adapter/Database/PdoMySQL.php',
	'Ke\\Adm\\Adapter\\CacheStoreImpl'         => __DIR__ . '/Ke/Adm/Adapter/CacheStoreImpl.php',
	'Ke\\Adm\\Adapter\\CacheStore\\Memcache'   => __DIR__ . '/Ke/Adm/Adapter/CacheStore/Memcache.php',
	'Ke\\Adm\\Adapter\\CacheStore\\RedisCache' => __DIR__ . '/Ke/Adm/Adapter/CacheStore/RedisCache.php',
	'Ke\\Adm\\Adapter\\Forge\\MySQLForge'      => __DIR__ . '/Ke/Adm/Adapter/Forge/MySQLForge.php',

];