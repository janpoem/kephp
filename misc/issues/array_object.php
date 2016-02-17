<?php
/**
 * 这个问题，是关于继承了ArrayObject的类，在序列化-反序列化以后，private属性无法访问到，但是能看到他有效的赋值了。
 * 居然发现这个bug php 7.0.3还是没修复了，算了。
 */

class Test extends ArrayObject
{

	private $name = null;

	public function __construct(array $input)
	{
		parent::__construct($input, ArrayObject::ARRAY_AS_PROPS);
//		parent::__construct($input, ArrayObject::STD_PROP_LIST);
	}

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function getName()
	{
		return $this->name;
	}
}

$test = new Test(['a' => 'a', 'b' => 'b']);
$test->setName('ok');

$ser = serialize($test);
$unSer = unserialize($ser);

var_dump($unSer->getName()); // null
var_dump($unSer);
// php 7.0.3
/**
D:\htdocs\array_object.php:36:null

D:\htdocs\array_object.php:37:
object(Test)[2]
	private 'name' => string 'ok' (length=2)
		private 'storage' (ArrayObject) =>
			array (size=2)
				'a' => string 'a' (length=1)
				'b' => string 'b' (length=1)

 */
// php 5.6.x
/**
string 'ok' (length=2)

object(Test)[2]
	private 'name' => string 'ok' (length=2)
	private 'storage' (ArrayObject) =>
		array (size=2)
			'a' => string 'a' (length=1)
			'b' => string 'b' (length=1)
 */