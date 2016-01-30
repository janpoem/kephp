# kephp - Keep PHP easy!

**目前只能作为Feature preview版本，不要用于实际生产的项目中！未来还有一些大的改动。**

历经2年，从早期MST Library到Agimvc时期，彻底地将各种冗余的代码整理、重构完成，严格意义上说，其实不止2年了，从Agimvc 3.2的版本时期，就已经在做各种尝试，那时候是2011年的光景。

## 新特性列表

### 面向php7

忘记php5吧，忘记吧！

### 多应用程序源代码集合

这里主要借鉴了Java的项目机制。

1. 一个项目所有php源代码，都基于src目录下，而不再基于app或application目录。
2. src下必须声明一个命名空间，如：\MyTest，那么Controller（\MyTest\Controller）、Model（\MyTest\Model）等代码都基于这个命名空间下。

假定我有一个项目，叫做X-ERP，这个项目的命名空间基于：\xErp，然后我需要创建一个新项目（\MyErp），这里我希望能重用X-ERP的代码，于是我可以有几个做法：

**做法1**：

将xErp放入新项目的src目录内，新项目内，src目录下，有src\MyErp和src\xErp。

在新项目中，类可以直接继承自xErp的类

```php

namespace MyErp\Model;


class User extends xErp\Model\User 
{

}

```

如果希望能重用xErp的视图组件（View、Component、Layout），则只要手动注册目录即可。

```php
$web->component->setDirs([
	'xErpView'      => [$app->src('xErp/View'), 200, Component::VIEW],
	'xErpComponent' => [$app->src('xErp/Component'), 200],
]);
```

**做法2**：

为新项目，注册旧项目的目录所在。

```php
$app->getLoader()->setDirs([
	'xErpSrc'    => ['any_path', 200],
	'xErpHelper' => ['any_path/Helper', 200, Loader::HELPER],
]);
```

### Loader和Component(Loader)

`\Ke\Loader`用于项目的Class、Helper（functions）的加载。

`\Ke\Web\Component`用于项目的View、Widget、Layout加载。

这里都支持注册多个目录，进行多个目录的检索。并允许控制权重值。

### 更好Composer兼容

要加载Composer，在项目的bootstrap.php文件，进行加载即可。

### 通过面向对象定义配置入口、重载入口、事件入口

这是一个不太好说明的问题，也是我从2012年开始思考至今。

**配置入口**

传统基于数组的方式进行配置，虽然很实用，但是在代码层面，并不友好，尤其是在对数据全面性判断时，需要做大量的判断。

现在完全改用类属性的方式，比如App的全局应用程序配置，直接改用类属性进行配置。

```php

namespace MyApp;

class App extends Ke\App 
{

	protected $name = 'MyApp';

	protected $salt = 'xxxxxxxxxxxxxx';

}

```

**重载入口**

这次重新封装，三大入口：App、Web、Console，都允许在实际的应用程序继承，并重载。

核心类库提供一种默认的行事风格，但是并不完全封闭重构这个行事风格的入口。

核心类库必要隐藏的属性和特性，通过对象实例进行隐蔽，继承类只要关心自己要实现什么逻辑即可。

比如，默认的Controller，当碰到action不存在的时候，会抛出异常，但用户可以重载onMissing的方法，来自行控制：

```php

class Index extends Controller {

	protected function onMissing(string $action)
	{
		// 原来的做法
		// throw new \Exception("Action {$action} not found!");
	}

}

```

**事件入口**

其实我个人并不喜欢，如wordpress的action和filter一般机制（通过字符串的方式来声明、调用事件），一方面来说，这种做法实在不是什么高性能的做法，其次，大规模的普及这种事件钩子，对于开发人员来说，实在太痛苦了，需要记得事件表太多了。

各个主要核心的类（App、Web、Console、Command、Controller、Renderer），都提供on为前缀的事件方法，并且都是protected的，这些方法，在源代码层面都是可被代码编辑器智能感知的。

Model层的命名保留以前Ror的风格：before*、after*。

这种做法的目的，核心的思想就是，通过面向对象的方式（使代码可被感知易于提示的情况下），开放、开放，彻底的开放。使框架可以成为开发者自己的框架，而不是别人的框架。

### Command彻底重做，实现ReflectionCommand

重做的目的，就是要彻底的简单化，简单化，更加简单，不要思考，只要动手写业务逻辑即可。

反射的命令的用法如下：

```php
class MyCmd extends ReflectionCommand
{

	protected static $commandName = 'my_cmd';

	protected static $commandDescription = '';

	// define fields: type|require|default|field|shortcut
	//         types: string|integer|double|bool|dir|file|realpath|json|concat|dirs|files|any...
	/**
	 * 对应执行命令时候的第一个参数，如：php ke.php my_cmd <name>
	 * 该参数为必须的参数
     * 
	 * @var string
	 * @type string
	 * @require true
	 * @field   1
	 */
	protected $name = '';

	/**
	 * 对应执行命令时候的第一个参数，如：php ke.php my_cmd <name> -s=<source>
	 * 该参数不是必须的
	 * 
	 * @var string
	 * @type string
	 * @field s
	 */
	protected $source = '';

	/**
	 * @var string
	 * @type string
	 * @field s
	 */
	protected $source = null;

	protected $tip = 'Creating model';

	protected function onConstruct($argv = null)
	{
		// 命令实例化接口
	}

	protected function onPrepare($argv = null)
	{
		// 命令执行前的预备阶段
	}

	protected function onExecute($argv = null)
	{
		// 命令执行时的实际入口
		// 这个方法是必须的
	}
}

```

通过上述类的属性声明，会自动对应到执行命令时所执行的参数，并自动做值类型的过滤和转换。一般来说，只要针对实际的业务逻辑进行编程即可，不会考虑多余事情。

### Command的检索顺序

1. 假定用户输入了new_cmd的命令，会转化为几个版本如：New_Cmd、NewCmd、new_cmd、newcmd，进行匹配
2. 优先检查src/Cmd，其次是kephp类库内的命令。

kephp本身提供许多命令，比如GitExport，这个命令主要用于导出当前项目和上一个版本（或指定版本）更新了的文件，并放入指定的目录，执行命令为：`php ke.php git_export`。

如果用户希望在这里基础上，增加一些新的功能，可以在项目添加：src/Cmd/GitExport

```php

namespace Cmd\GitExport;

class GitExport extends \Ke\Cli\Cmd\GitExport 
{

	
}
```

那么同样在执行这个命令时候，会优先加载用户设定的类，并且并不会和全局的ClassLoader产生冲突。

### Command的调用

除了使用命令的方式调用，还可以通过实例化一个Command来调用（可以参考\Ke\Cli\Cmd\Add）指令。

```php

$cmd = new UpdateModel([null, 'User']);
$cmd->execute();
```

### Router转发风格

现在对于WebRouter的做法，有很多新的做法，比如：$web->get('/post/:id/edit')，这种，还有MiddleWare的模式等。

请恕我鲁钝，无法接受那些五花八门的东西，我坚持一个原则：一个项目的路由分发，应该只在一个地方进行统一、集中的配置，而不应该到处都能分发，不然要找现在到底是哪个分发器起作用，或者是去找这个action下一步又跳转去哪个action去了。

这里和过往MST Library、Agimvc里面所坚持的编程方针有关。

因为Model是具有数据、具有行为能力（方法）的实体，所以业务逻辑应该作为Model的接口行为，而Controller\Action层面，则应该只包含最简单的流程控制。所有涉及数据实体的操作，应该一律放在Model层（同样的道理，所有展现相关的，应该放在View层），Model层和View层中间，使用Helper进行无状态衔接。

新版本的Router配置仍然采用数组的方式：

```php
<?php
// config/routes.php

$router->routes = [
	// 根空间分发
	'*' => [
	],
	// 匹配所有hello/*的请求
	'hello' => [
		'namespace' => 'hi',
		'mappings' => [
			['world/{name}', 'controller#action', ['name' => '[a-z0-9_-]+']],
		]
	],
	'admin' => [
		'path' => 'management', // 允许通过path字段，对前缀进行修改，匹配management/*，而不会匹配admin
		// 这里没有指定命名空间，他会使用节点名admin，注意不是path的management
	],
];

```

上述所示，是传统路由器的分发模式，每个节点，代表着的是一个基础的前缀路径（同时也是命名空间）。每个节点都可以配置相关的`mappings`字段。

如果没指定，则按照`controller/action/tail`的方式进行匹配。

新版本增加了两种匹配的模式：

一、controller分发模式

```php
<?php
// config/routes.php

$router->routes = [
	// 根空间分发
	'user' => [
		'controller' => 'user',
	],
];

```

这种分发模式，会将所有user/*的请求，绑定到user控制器，user/profile => { controller: user, action: profile }。

二、class分发模式

```php
<?php
// config/routes.php

$router->routes = [
	// 根空间分发
	'assets' => [
		'class' => MyApp\Controller\Asset::class,
		'action' => 'output',
	],
];

```

这种模式下，会 将所有assets/*请求都转发到Asset这个类，注意这个类必须继承自Controller。

并且指定了默认的action，除非指定了mappings，否则所有的请求都会使用output这个action。

controller和class模式，会使用action/tail的方式进行默认的匹配。区别于传统模式。


### controller-action命名

默认行为中：

1. controller、action，都会强制转为小写
2. 如果controller包含namespace，也会强制小写，类分隔符`\`，会替换为Linux的目录分隔符`/`，如：admin/post
3. controller的类名：驼峰首字母大写，保留下划线，如：hello_world/say_hi => Hello_World/Say_Hi
4. action的方法名：强制小写，保卫下划线（.-都会替换为_）。

当然，如上述，这只是一个保守的默认行为而已，所有命名风格，允许用户继承Web，并进行重载相关的方法。但是前后一致性的问题，需要自行处理。

默认行为中，`namespace/controller#action`为字符表达格式。

### action执行

假定有动作：login，如果只是GET请求，只会关联方法`login`，如果是post请求，则会进一步关联`login`和`post_login`，两者共享一个当前的controller实例。

默认行为，如果不存在login方法，会抛出异常。

action输出，有三种方法：

1. 什么都不return（return null），默认以controller/action进行view匹配。
2. return false，什么都不输出。
3. $this->view()、$this->text()、$this->json()、$this->redirect()，执行具体的输出（不需要加return）。

在`login`和`post_login`的时候，

1. 什么都不return依旧生效，对应的实体是`login`。
2. 如果在`login`优先执行了上述3.的强制输出（view/text/json/redirect），则不再会执行`post_login`

实际上核心判断的标准是，检测当前的`$web->isRender()`，如果任何操作出发了Wen渲染，则其他操作都不会再生效。

默认的输出，可以通过重载Controller的`defaultReturn`方法进行控制。

Router匹配到的特定变量，会作为action执行时的参数，比如：



```php
<?php
// config/routes.php

$router->routes = [
	// 根空间分发
	'assets' => [
		'class' => MyApp\Controller\Asset::class,
		'mappings' => [
			['{path}', '#output', ['path' => '.*']],
		],
	],
];

```

上述的匹配，默认行为，会匹配：

```

namespace MyApp\Controller;

class Asset extends Controller {

	public function output($path = '') {
		
	}
}


```

如果希望定制传入的参数，可以重载Controller的`getActionArgs(array $params)`方法的返回结果。

### Web\Context

在实际项目中的`index.php`

```php
<?php

require '../bootstrap.php';

$web = new \Ke\Web\Web();
$web->dispatch();

```

如果属于自己定制的Web，可以改为`new \MyApp\Web()`。

而用户不必特别去global标识$web，通过：`\MyApp\Web::getWeb()`，或者`\Ke\Web\Web::getWeb()`，都可以获取回这个实例（\Ke\Cli\Console和\Ke\App都是类似的道理），为了保证开放性，没有严格限制Web和Console的重复创建实例的问题，App则限制了重复实例。

Web部分的结构：

Web -> Router -> Controller -> Context -> Renderer

变量的传递，不需要在调用`$this->view()`的方法中传递，只需要将变量绑定到Controller上（注意要public），就会自动传递到Context上。

Context是渲染视图时的上下文变量环境，通过Controller的$this绑定的变量，被视作超级全局变量，直接绑定到Context上，比如：

```php

class User extends Controller {

	protected $tempVar = 'temp'; // 这个属性不会传递到Context中

	public $topWidget = 'user_bar'; // 这个变量会传递到Context

	public function index() {
		$this->user = User::loadCache(1); // 这个也会传递到Context
	}


}

```

相应的，在`user/index`view中：

```php
<?php

$this->topWidget; // user_bar
$this->user; // User::loadCache(1);

?>
```



在View环境下，$this指向的就是当前Web渲染时的Context实例。

在View环境下，拥有两个变量：`$this`和`$web`，$this对应的是Context，$web则对应的是Web的实例。

而在Layout中，会多一个变量：`$content`，这个是加载视图时获取到的内容，你只要`print $content ?? ''`。

在Component中，情况就会复杂一些，但也只是复杂一些，假定在view中，我加载了一个component：

```php
<?php

$this->component('user_bar', ['user' => $this->user]);

```

那么在`user_bar`这个component中：

```php
<?php

$this->user; // 是Context中的变量，User::loadCache(1);

$user; // 则是加载这个component时，传递过来的局部变量，他只在当前的component中有效。

```

component的加载，还允许增加一个layout，这是一个很变态的做法，很多时候，我们的前端都会将一个区块的代码，做成一个可复用的module，比如：

```html
<div class="module user-module">
	<div class="title">
		title
	</div>
	<div class="body">
		body
	</div>
</div>
```

恩，很好很规整，那么我们只要把上述的代码稍微修改，并放入src/Component/layout/user_module.phtml中：

```php
<div class="module user-module">
	<div class="title">
		<?php print $title ?? '' ?>
	</div>
	<div class="body">
		<?php print $content ?? '' ?>
	</div>
</div>
```

然后在需要的时候调用

```php
<?php

$this->component('user_profile', 'user_module', ['title' => '个人资料']);
```

而且如果你想的，可以进一步的将他封装为一个函数，反正是你的自由。

最后说一点：

```
$web->setContext(new MyApp\Helper\UserContext);
```

你懂的。

### Model层优化

一、重新调整Columns的生成，尽量不破坏原Columns的内容。

二、dbColumns()接口，为数据库的声明，请不要随意修改，请直接修改`protected static $columns`属性

三、Columns声明增加update和create的区别：

```php

namespace MyApp\Model;


class User extends \Ke\Model {

	protected static $columns = [
		'email' => ['label' => '邮箱', 'require' => 1, 'unique' => 1, 'min' => 5, 'max' => 128, 'email' => true],
		'name' => [
			'label' => '姓名',
			self::ON_UPDATE => [ 'require' => 1, 'min' => 3 ], // 嗯这样的逻辑比较奇葩，只是为了展示特性
		],
		'created_at' => [self::ON_CREATE => 'now'],
		'updated_at' => [self::ON_UPDATE => 'now'],
		'saved_at'   => [self::ON_SAVE   => 'now'], // create update都会触发
	];
	
}

```

四、重新调整Model缓存的问题，增加相应的接口：`onCreateCache`,`onUpdateCache`,`onSaveCache`,`onDeleteCache`。可缓存Model，要求必须有主键的Model才可以。加载缓存：`Mode::loadCache(1)`。`before*`和`after*`系列接口就不用介绍了。

五、数据更新前会做差异化比较。

六、去掉很多没用的东西、代码优化。

### CacheModel和CacheModelTrait

这个是专门用来针对缓存数据的模型。呃，如果不能理解为什么会有这样的东西，这段就跳过吧。

`prepareData`，接口必须实现，用于实现一个缓存数据模型的装填和准备阶段。

`onCreate`,`onUpdate`,`onSave`,`onDelete`接口。

### Query

新版本的查询构造器，都改用`Query`实现，可以通过数组的方式构建。

```php

$query = (new Query())->select('id', 'name', 'email', ['login_id', '...'])->from('user');
$query->where('status', '=', 1)->addWhere([
	['id', 'in', 1,2,[4,5],6,7,8,9,10],
	'OR',
	[
		['name', 'like', 'jan%'],
		'AND',
		['email', '=', 'janpoem@163.com'],
	]
]);
$query->join(QueryBuilder::LEFT_JOIN, 'user_log.user_id', 'user.id');
// 输出调试的sql，这里会让变量放入sql字符串中，方便直接调试。
$query->sql();

$query->find(); // 查全部
$query->findOne(); // 查一条
$query->count(); // 查记录数
$query->column('id'); // 取id这一列
$query->columnOne('id');

```

如果希望绑定到Model实例上：

```php

User::query()->where(...); // 通过Model调用的，可以不需要 写from

```

### Model和Query的特定查询构造

```php

class User extends Model {

	protected static $queries = [
		'logs' => [
			'select' => 'tb1.*',
			'from'   => 'user', // 这个其实不是必须写的，并且表索引会自动关联tb1
			'join'   => [
				[QueryBuilder::LEFT_JOIN, 'user_log.user_id', 'tb1.id']
			],
			'order'  => ['status', 'updated_at', -1], // -1是DESC，1是ASC 
		]
	];

}

```

假定我需要复用这个查询：

```php

User::query('logs')->where('tb1.id', '>', 100)->limit(20); // 这里会clone一个新的查询，而不会污染原来的查询构造。

```

### 其他

#### \Ke\Uri

所有生成Uri的地方，都使用了这个类，比如：`$web->uri('hello/world');`

注意，这里要生成uri的时候，需要使用`$uri->newUri()`的方法。这个方法会clone一个新的实例。当然Web和Asset的代码，已经进行相关的封装，一般用户不用关心这个问题。

假定你的网站根目录为：`/my_app`，那么注意传参数时候的区别：

```php

$baseUri->newUri('hello/world'); // => /my_app/hello/world/，注意，如果不是xxx.yyy的结尾，会强制补/
$baseUri->newUri('/good'); // => /good/

$baseUri->newUri('.././good'); // => /my_app/../good/，注意这里不会做../的目录缩进的处理，而交给服务器吧。但是会去掉./ 

```

同样的，查询字符串也会自动合并：

```php

$baseUri->newUri('what?id=1', ['id' => 2]); // 最终生成是：/my_app/what/?id=2

```

#### \Ke\MimeType

这是文件Media Type的匹配，本来是希望作为一个静态类来使用的，谁没事搞几个版本的Mime呢？不过为了保持这个版本的风格一致，还是使用了对象实例的方式。


#### php ke.php scan_tables

哦哦，根据当前项目配置的数据库自动生成Model文件，如果文件存在，则更新。很爽。

#### \Ke\OutputBuffer

```php

$content = $ob->getFunctionBuffer(null, function() {
	echo 'hello world';
	var_dump('good!');
});

$content = $ob->getImportBuffer(null, 'test.php', ['var1' => 123]);

```

嗯……

### Html

html标签构造辅助器，在view层，可直接访问Context的html变量即可获取：

```php
$this->html->tag('div', 'hello world', ['id' => 'id']);
$this->html->textInput('value');
```

诸如此类。

设计这个辅助器的目的，尽量减少在View层面去写实际的、大量的html标签，而应该用一种简化编程方式去实现，且不同的CSS框架下也能无缝的切换。

### Web\UI接口

Web\UI接口，用于整体重载Web渲染。

目前版本UI只包含两个接口：

```php
interface UI
{

	public function getHtml(); // 返回当前web环境所使用的Html Helper

	public function getContext(); // 返回当前Web环境所实行的Context

}
```

```php
$web->setUI(new MyUI());
```

这个类的设计，是为了解决多个CSS Framework（Bootstrap、SemanticUI、UIKit）之间，展现层代码可以无缝切换。实际上在Agimvc后期版本，对HTML、UI已经实现过一次，已经可以做到不同的CSS框架之间，无缝切换。然而我是一个很……的人，到kephp，我还是坚持忘记原来的具体实现，重新收拾代码，整理（有价值的地方会继承）。

这里浅层上说，是不同CSS Framework（标签结构、class命名）的切换，可是更深层来说，不同的项目之间，同样存在这种需求。这样才能从本质上，让一个已存在的项目，可以迅速的重用到另一个项目上。

## 版本说明

当前为先行版本，包含实现了主要特性，不过不要放入实际项目中，还有一些东西没做。