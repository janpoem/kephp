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
	// core
	'Ke\\App'                             => __DIR__ . '/Ke/App.php',
	'Ke\\DirectoryRegistry'               => __DIR__ . '/Ke/DirectoryRegistry.php',
	'Ke\\DataStorage'                     => __DIR__ . '/Ke/DataStorage.php',
	'Ke\\Loader'                          => __DIR__ . '/Ke/Loader.php',
	'Ke\\OutputBuffer'                    => __DIR__ . '/Ke/OutputBuffer.php',
	'Ke\\Uri'                             => __DIR__ . '/Ke/Uri.php',
	// Adm
	'Ke\\Adm\\Cache'                      => __DIR__ . '/Ke/Adm/Cache.php',
	'Ke\\Adm\\CacheModel'                 => __DIR__ . '/Ke/Adm/CacheModel.php',
	'Ke\\Adm\\CacheModelTrait'            => __DIR__ . '/Ke/Adm/CacheModelTrait.php',
	'Ke\\Adm\\DataList'                   => __DIR__ . '/Ke/Adm/DataList.php',
	'Ke\\Adm\\Db'                         => __DIR__ . '/Ke/Adm/Db.php',
	'Ke\\Adm\\Filter'                     => __DIR__ . '/Ke/Adm/Filter.php',
	'Ke\\Adm\\Model'                      => __DIR__ . '/Ke/Adm/Model.php',
	'Ke\\Adm\\Query'                      => __DIR__ . '/Ke/Adm/Query.php',
	'Ke\\Adm\\Validator'                  => __DIR__ . '/Ke/Adm/Validator.php',
	'Ke\\Adm\\Sql\\ForgeImpl'             => __DIR__ . '/Ke/Adm/Sql/ForgeImpl.php',
	'Ke\\Adm\\Sql\\QueryBuilder'          => __DIR__ . '/Ke/Adm/Sql/QueryBuilder.php',
	'Ke\\Adm\\Sql\\QueryTables'           => __DIR__ . '/Ke/Adm/Sql/QueryTables.php',
	'Ke\\Adm\\Sql\\MySQL\\Forge'          => __DIR__ . '/Ke/Adm/Sql/MySQL/Forge.php',
	'Ke\\Adm\\Adapter\\CacheAdapter'      => __DIR__ . '/Ke/Adm/Adapter/CacheAdapter.php',
	'Ke\\Adm\\Adapter\\DbAdapter'         => __DIR__ . '/Ke/Adm/Adapter/DbAdapter.php',
	'Ke\\Adm\\Adapter\\Db\\PdoAbs'        => __DIR__ . '/Ke/Adm/Adapter/Db/PdoAbs.php',
	'Ke\\Adm\\Adapter\\Db\\PdoMySQL'      => __DIR__ . '/Ke/Adm/Adapter/Db/PdoMySQL.php',
	'Ke\\Adm\\Adapter\\Cache\\Memcache'   => __DIR__ . '/Ke/Adm/Adapter/Cache/Memcache.php',
	'Ke\\Adm\\Adapter\\Cache\\RedisCache' => __DIR__ . '/Ke/Adm/Adapter/Cache/RedisCache.php',
	// Cli
	'Ke\\Cli\\Argv'                       => __DIR__ . '/Ke/Cli/Argv.php',
	'Ke\\Cli\\Command'                    => __DIR__ . '/Ke/Cli/Command.php',
	'Ke\\Cli\\Console'                    => __DIR__ . '/Ke/Cli/Console.php',
	'Ke\\Cli\\ReflectionCommand'          => __DIR__ . '/Ke/Cli/ReflectionCommand.php',
	'Ke\\Cli\\Writer'                     => __DIR__ . '/Ke/Cli/Writer.php',
	'Ke\\Cli\\Cmd\\GitExport'             => __DIR__ . '/Ke/Cli/Cmd/GitExport.php',
	'Ke\\Cli\\Cmd\\NewCmd'                => __DIR__ . '/Ke/Cli/Cmd/NewCmd.php',
	'Ke\\Cli\\Cmd\\NewModel'              => __DIR__ . '/Ke/Cli/Cmd/NewModel.php',
	'Ke\\Cli\\Cmd\\ScanTables'            => __DIR__ . '/Ke/Cli/Cmd/ScanTables.php',
	'Ke\\Cli\\Cmd\\UpdateModel'           => __DIR__ . '/Ke/Cli/Cmd/UpdateModel.php',
	// Utils
	'Ke\\Utils\\Git'                      => __DIR__ . '/Ke/Utils/Git.php',
	'Ke\\Utils\\SortAsTree'               => __DIR__ . '/Ke/Utils/SortAsTree.php',
	'Ke\\Utils\\Status'                   => __DIR__ . '/Ke/Utils/Status.php',
	'Ke\\Utils\\Success'                  => __DIR__ . '/Ke/Utils/Status.php',
	'Ke\\Utils\\Failure'                  => __DIR__ . '/Ke/Utils/Status.php',
];