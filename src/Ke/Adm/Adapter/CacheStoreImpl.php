<?php
/**
 * Created by PhpStorm.
 * User: Janpoem
 * Date: 2015/11/19 0019
 * Time: 10:22
 */

namespace Ke\Adm\Adapter;


interface CacheStoreImpl
{

	public function __construct($remote, array $config);

	public function has($key);

	public function set($key, $data, $expire = 0);

	public function get($key);

	public function delete($key);

	public function replace($key, $data, $expire = 0);

	public function increment($key, $value = 1);

	public function decrement($key, $value = 1);

	public function flush();
}