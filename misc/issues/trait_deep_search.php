<?php
/**
 * 如何迅速定位某个类到底使用了那个trait？
 *
 * 如下所示，到了ClassA1，就不再保留TraitA的记录了，使用class_uses，无法迅速的定位出他是否包含了某个trait
 *
 * 一个简单解决办法，是添加一个空接口类（Interface），使用了trait的，手动添加实现这个接口（impl是可以多重的），
 * 实际代码中，判断：$obj instance Impl，或者is_subclass($obj, Impl::class)，就能非常迅速的定位。
 */

trait TraitA
{

	public function sayHi()
	{
		echo 'hi';
	}
}

interface ImplA
{
}

class ClassA implements ImplA
{
	use TraitA;
}

class ClassA1 extends ClassA
{
}

$a1 = new ClassA1();

var_dump(class_uses($a1));                                // Array ( ), empty
var_dump(class_uses(ClassA1::class));                     // Array ( ), empty
var_dump(class_uses(ClassA::class));                      // Array ( [TraitA] => TraitA ), bingo
var_dump(is_subclass_of($a1, ImplA::class));              // true, yes
var_dump(is_subclass_of(ClassA1::class, ImplA::class));   // true, yes
var_dump(is_subclass_of(ClassA::class, ImplA::class));    // true, yes
var_dump($a1 instanceof ImplA);                           // true, yes