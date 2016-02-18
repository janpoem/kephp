<?php

require 'bootstrap.php';

class Test_Base_Depth_Query extends PHPUnit_Framework_TestCase
{

	public function testArray()
	{
		$data = [
			'a' => [
				'a1' => [1, 2, 3, 4],
				'a2' => ['a', 'b', 'c', 'd'],
			],
			'b' => [
				'bb'  => 'bb',
				'bbb' => 'bbb',
			],
			'c' => false,
		];

		$this->assertEquals($data['a']['a1'], depth_query($data, 'a->a1'));
		$this->assertEquals($data['a']['a1'][1], depth_query($data, 'a->a1->1'));
		$this->assertEquals($data['a']['a1'][6] ?? null, depth_query($data, 'a->a1->6', null));
		$this->assertEquals($data['a']['a2'][3] ?? null, depth_query($data, 'a->a2->3', null));
		$this->assertEquals($data['b']['bb'] ?? null, depth_query($data, 'b->bb', null));
		$this->assertEquals($data['b']['bc'] ?? 'bc', depth_query($data, 'b->bc', 'bc'));
		$this->assertEquals($data['c'] ?? false, depth_query($data, 'c', false));
	}

	public function testObject()
	{
		$obj = new stdClass();
		$obj->zero = 0;
		$obj->zero_float = 0.00;
		$obj->zero_str = '0';
		$obj->a = 'a';
		$obj->array = ['a', 'b', 'c'];

		$obj2 = new stdClass();
		$obj2->b = 'b';
		$obj2->c = 'c';

		$obj->obj = $obj2;

		$this->assertEquals($obj->a, depth_query($obj, 'a'));
		$this->assertEquals($obj->zero, depth_query($obj, 'zero'));
		$this->assertEquals($obj->array[0], depth_query($obj, 'array->0'));
		$this->assertEquals($obj->obj->b, depth_query($obj, 'obj->b'));
		$this->assertEquals($obj->zero_str, depth_query($obj, '->zero_str'));
		$this->assertEquals($obj->zero_float, depth_query($obj, '->zero_float->what'));
	}
}
