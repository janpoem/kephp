<?php
/**
 * 这个问题，是关于继承了ArrayObject的类，在序列化-反序列化以后，private属性无法访问到，但是能看到他有效的赋值了。
 * 此bug php 7.0.3已经修复，请更新php的版本。
 * @link http://php.net/ChangeLog-7.php#7.0.3
 *       https://bugs.php.net/bug.php?id=71311
 */

class Test extends ArrayObject
{

	private $name = null;

	public function __construct(array $input)
	{
		parent::__construct($input, ArrayObject::ARRAY_AS_PROPS);
//		parent::__construct($input, ArrayObject::STD_PROP_LIST);
	}

	public function setName(string $name)
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

var_dump($unSer->getName()); // null 为什么
var_dump($unSer);            // 你会看到，$name属性是有内容的。
/// output :
/**
object(Test)[2]
	private 'name' => string 'ok' (length=2) // name是有值的
	private 'storage' (ArrayObject) =>
		array (size=2)
			'a' => string 'a' (length=1)
			'b' => string 'b' (length=1)
 */