<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm\Adapter;


interface CacheStoreImpl
{

	const DEFAULT_COLON = '.';

	public function setName($name);

	public function configure(array $config);

	public function getConfig();

	public function exists($key);

	public function set($key, $data, $expire = 0);

	public function get($key);

	public function delete($key);

	public function replace($key, $data, $expire = 0);

	public function increment($key, $value = 1);

	public function decrement($key, $value = 1);

	public function flush();
}
