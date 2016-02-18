<?php

require 'bootstrap.php';

class Test_Base_Import extends PHPUnit_Framework_TestCase
{

	public function testOneFile()
	{
		$this->assertEquals('a', import('misc/a.php'));
		$this->assertEquals('b', import('misc/b.php'));
		$this->assertEquals([
			1    => 'array1',
			'ok' => 'ok',
		], import('misc/array1.php'));
		$this->assertEquals(false, import('misc/none.php'));

		// 单文件引用路径
		$this->assertEquals('misc/array2.php', import('misc/array2.php', null, KE_IMPORT_PATH));
		$this->assertEquals(false, import('misc/none.php', null, KE_IMPORT_PATH));

		$this->assertEquals([], import('misc/b.php', null, KE_IMPORT_ARRAY));
		$this->assertEquals([
			1    => 'array1',
			'ok' => 'ok',
		], import('misc/array1.php', null, KE_IMPORT_ARRAY));
	}

	public function testMultiFilesModeRaw()
	{
		$files = [
			'misc/a.php',
			'misc/b.php',
			'misc/none.php',
		];
		$result = ['a', 'b', false];
		$this->assertEquals($result, import($files));

		$files = [
			'misc/array1.php',
			'misc/array2.php',
		];
		$result = [
			[
				1    => 'array1',
				'ok' => 'ok',
			],
			[
				2    => 'array2',
				'ok' => 'no ok',
			],
		];
		$this->assertEquals($result, import($files));

		$files = [
			'misc/return_false.php',
			'misc/none.php',
		];
		$result = [
			false,
			false,
		];
		$this->assertEquals($result, import($files));
	}

	public function testMultiFilesModePath()
	{
		$files = [
			'misc/a.php',
			'misc/b.php',
			'misc/none.php',
		];
		$result = ['misc/a.php', 'misc/b.php'];
		$this->assertEquals($result, import($files, null, KE_IMPORT_PATH));
	}

	public function testMultiFilesModeArray()
	{
		$files = [
			'misc/a.php',
			'misc/b.php',
			'misc/none.php',
		];
		$result = [];
		$this->assertEquals($result, import($files, null, KE_IMPORT_ARRAY));

		$files = [
			'misc/array1.php',
			'misc/array2.php',
		];
		$result = [
			1    => 'array1',
			'ok' => 'ok',
			2    => 'array2',
		];
		$this->assertEquals($result, import($files, null, KE_IMPORT_ARRAY));

		$result = [
			1    => 'array1',
			'ok' => 'no ok',
			2    => 'array2',
		];
		$this->assertEquals($result, import($files, null, KE_IMPORT_MERGE));
	}

	public function testContext()
	{
		$vars = [
			'a' => 'none',
			'b' => 'none',
		];
		$result = [
			'a' => 'a1',
		    'b' => 'b1',
		];
		$return = import([
			'misc/context1.php', // 在这里，a和b，被修改了值，
			'misc/context2.php', // 进入到这里，a和b，已经是新的值
		], $vars, KE_IMPORT_MERGE | KE_IMPORT_CONTEXT);
		$this->assertEquals($result, $return);
		// 最终返回的时候，$vars并没有发生任何改变，只是在import的内部流程里发生了变化
		// Web\Context里面，并不使用这个机制，这种机制还是有局限性
	}

	public function testMultiArrayFiles()
	{
		$files = [
			'misc/a.php',
			'misc/b.php',
		    [
				'misc/array1.php',
				'misc/array2.php',
			],
		];
		$result = [
			'a',
		    'b',
		    [
				1    => 'array1',
				'ok' => 'ok',
			],
		    [
				2    => 'array2',
				'ok' => 'no ok',
			],
		];
		$this->assertEquals($result, import($files));

		$paths = [
			'misc/a.php',
			'misc/b.php',
			'misc/array1.php',
			'misc/array2.php',
		];
		$this->assertEquals($paths, import($files, null, KE_IMPORT_PATH));
	}
}
