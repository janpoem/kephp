#AgiMVC 4.0

##4.0的重要变化

###与PSR规范接轨

之所以考虑遵循PSR的规范，主要考虑的是，能朝未来兼容，[PSR官网](http://www.php-fig.org/)，可以看到，目前市面上主流的框架都宣布参与了PSR规范的制定，所以未来很有可能这种规范会反过来约束和规范PHP的语法本身，所以框架在升级过程中，主动向主流规范靠拢是必由之路——未来能更省事。

考虑接轨PSR规范的第二个理由是，规范的推广和使用，或直接采用标准规范，能减少框架介绍文档中，对代码风格和文件命名规则方面说明，更利于团队交接、标准化交流方面的事宜。

###namespace模式转变

除了采用PSR以外，在类命名的风格上，也会转为使用PHP 5.3之后的namespace规范命名。

1. 在过去尝试升级框架的过程中，已经对PHP 5.3的namespace做过很多测试，除了在使用上的一点点异样感——需要写更多的use、namespace的引用的代码，其他都还好。
2. 至今PHP已经进入到5.6环境，目前国外大多数的框架、类库，已经多数转移类命名的风格为namespace的模式了，仍然采用过去PHP5.2的伪命名空间模式，已经有些不合时宜。
3. 立足于更好的对代码分化管理，采用更好的namespace风格，也是有利的。

##编码规范

4.0版本的编码规范遵循[PSR-1](http://www.php-fig.org/psr/psr-1/)、[PSR-2](http://www.php-fig.org/psr/psr-2/)，以下是对应的中文翻译：

* [PSR-1中文](http://blog.mosil.biz/2012/09/psr-1-basic-coding-standard/)
* [PSR-2中文](http://blog.mosil.biz/2012/08/psr-2-basic-coding-standard/)

##AutoLoad实现机制

AutoLoad实现机制，遵循[PSR-0](http://www.php-fig.org/psr/psr-0/)，并部分遵循[PSR-4](http://www.php-fig.org/psr/psr-4/)。

* [PSR-0中文](http://blog.mosil.biz/2012/08/psr-0-autoloading-standard/)

PSR-4规范中，有一条：

	Autoloader implementations MUST NOT throw exceptions, MUST NOT raise errors of any level, and SHOULD NOT return a value.

PSR-4的这个规范是为了确保其他类库和框架混合使用时，不会存在彼此冲突和报错。这里在实现上还有些问题，毕竟框架是要让整个项目处于某种特点的状态下运行，如果在框架的AutoLoad中无法捕捉未命中的类，似乎有点说不过去——当然，实际上如果按照PSR4规范去做，造成的主要影响也是在开发过程中会发生，因为谁都不会将不安全的代码提交上正式的站点去使用。（2014.9.20）

###4.0版本AutoLoad机制

经过不断的简化和优化，4.0的类自动加载机制，说明如下：

1. App::bootstrap()过程里，会装载两个类定义文件：
	1. **coreClasses** => library/agimvc/classes.php，框架核心类，核心类默认基于AGI_DIR目录进行加载。
	2. **userClasses** => library/classes.php，用户定义类，这里的类声明需要声明全路径。
2. App::registerClasses()方法，提供给开发用户添加和注册自己的类，这部分类会添加userClasses空间中。只可添加不重名的类，不能覆盖已经存在的类，以class => path的结构添加，path需要是全路径（允许添加phar://协议）。
3. App::bootstrap()会注册PHP的spl_autoload_register到App::loadClass()方法上。
4. loadClass方法，会先检查coreClasses、userClasses是否定义了该类的路径（HashMap的检索）。
5. 如果coreClasses、userClasses未定义，则将className中的\\替换为路径分隔符（默认替换为Linux的路径分隔符/）。

