<?php

require 'bootstrap.php';

class Test_Base_Parse_Class extends PHPUnit_Framework_TestCase
{

	public function testFullName()
	{
		$classes = [
			'Ke\\Web\\Web'              => ['Ke\\Web', 'Web'],
			'Hello\\World'              => ['Hello', 'World'],
			'Ke\\Utils\\DocMen\\DocMen' => ['Ke\\Utils\\DocMen', 'DocMen'],
		];

		foreach ($classes as $class => $result) {
			$this->assertEquals($result, parse_class($class));
		}
	}

	public function testOnlyClass()
	{
		$classes = [
			'User'        => [null, 'User'],
			'Post'        => [null, 'Post'],
			'Hello_World' => [null, 'Hello_World'],
		];

		foreach ($classes as $class => $result) {
			$this->assertEquals($result, parse_class($class));
		}
	}
}
