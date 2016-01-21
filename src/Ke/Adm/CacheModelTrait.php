<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @link      http://kephp.com ( https://git.oschina.net/kephp/kephp-core )
 * @author    曾建凯 <janpoem@163.com>
 */

namespace Ke\Adm;


trait CacheModelTrait
{

	protected static $source = null;

	protected static $colon = '.';

	protected static $prefix = '';

	protected static $TTL = 60 * 60 * 12;

	protected static $version = 0;

	private $args = [];

	private $key = '';

	private $status = false;

	private $updatedAt = 0;

	protected $data = null;

	public static function getAdapter()
	{
		return Cache::getAdapter();
	}

	public static function makeCacheKey(...$args)
	{
		if (empty($args))
			return false;
		if (!empty(static::$prefix))
			array_unshift($args, static::$prefix);
		$args[] = static::$version;
		return implode(static::$colon, $args);
	}

	/**
	 * @param array ...$args
	 * @return static
	 */
	public static function &load(...$args)
	{
		global $KE_CACHES;
		$key = static::makeCacheKey(...$args);
		$cache = $KE_CACHES[$key] ?? static::getAdapter()->get($key);
		if ($cache === false) {
			$cache = new static(...$args);
			$cache->key = $key;
			$cache->args = $args;
			$cache->data = $cache->prepareData(...$args);
			$cache->save();
		}
		if (!isset($KE_CACHES[$key]))
			$KE_CACHES[$key] = $cache;
		return $KE_CACHES[$key];
	}

	abstract protected function prepareData();

	public function getCacheTTL()
	{
		return static::$TTL;
	}

	public function getExpireDate()
	{
		if ($this->isCache())
			return $this->updatedAt + $this->getCacheTTL();
		return -1;
	}

	public function args(int $index = null, $default = null)
	{
		if (!isset($index))
			return $this->args;
		return $this->args[$index] ?? $default;
	}

	public function save()
	{
		$status = $this->status;
		$update = $this->updatedAt;
		$event = 'onCreate';
		if (empty($this->status)) {
			$this->status = Model::ON_CREATE;
		}
		else {
			$this->status = Model::ON_UPDATE;
			$event = 'onUpdate';
		}
		$this->updatedAt = time();

		if ($this->$event() !== false &&
		    $this->onSave() !== false &&
		    static::getAdapter()->set($this->key, $this, $this->getCacheTTL())
		) {
			return Model::SAVE_SUCCESS;
		}
		$this->status = $status;
		$this->updatedAt = $update;
		return Model::SAVE_FAILURE;
	}

	protected function onCreate()
	{
	}

	protected function onUpdate()
	{
	}

	protected function onSave()
	{
	}

	public function isCache()
	{
		return $this->status !== false;
	}

	public function destroy()
	{
		if (!$this->isCache())
			return Model::SAVE_FAILURE;
		$status = $this->status;
		$this->status = Model::ON_DELETE;
		if ($this->onDestroy() !== false && static::getAdapter()->delete($this->key)) {
			$this->status = false;
			return Model::SAVE_SUCCESS;
		}
		$this->status = $status;
		return Model::SAVE_FAILURE;
	}

	protected function onDestroy()
	{
	}
}