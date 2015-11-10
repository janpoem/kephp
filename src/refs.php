<?php
/**
 * KePHP, Keep PHP easy!
 *
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @copyright Copyright 2015 KePHP Authors All Rights Reserved
 * @author    曾建凯 <janpoem@163.com>
 */

/**
 * Ke/refs.php
 *
 * 这个文件的目的在于对KePHP常量做一个索引，辅助IDE能智能提示。
 *
 * 请不要在开发中require这个文件。
 */

/**
 * KePHP引导的开始时间戳，允许在外部重载
 * @defined Ke/Core/Bootstrap.php
 */
const KE_BOOTSTRAP = '0.39046000 1445285532';
/**
 * 类库根目录
 * @defined Ke/Core/Bootstrap.php
 */
const KE_LIB = 'dir';

/**
 * 字符串类型字面值
 * @defined Ke/Core/common.php
 */
const KE_STR = 'string';
/**
 * 数组类型字面值
 * @defined Ke/Core/common.php
 */
const KE_ARY = 'array';
/**
 * 对象类型字面值
 * @defined Ke/Core/common.php
 */
const KE_OBJ = 'object';
/**
 * 资源类型字面值
 * @defined Ke/Core/common.php
 */
const KE_RES = 'resource';
/**
 * 整型类型字面值
 * @defined Ke/Core/common.php
 */
const KE_INT = 'integer';
/**
 * 浮点字面值
 * @defined Ke/Core/common.php
 */
const KE_FLOAT = 'double';
/**
 * 布尔类型字面值
 * @defined Ke/Core/common.php
 */
const KE_BOOL = 'boolean';
/**
 * null类型字面值
 * @defined Ke/Core/common.php
 */
const KE_NULL = 'NULL';

/**
 * Class的目录分隔符号
 * @defined Ke/Core/common.php
 */
const KE_DS_CLASS = '\\';
/**
 * Windows系统的目录分隔符号
 * @defined Ke/Core/common.php
 */
const KE_DS_WIN = '\\';
/**
 * Unix和Linux系统的目录分隔符号
 * @defined Ke/Core/common.php
 */
const KE_DS_UNIX = '/';
/**
 * 深度查询数据的符号
 * @defined Ke/Core/common.php
 */
const KE_DEPTH_QUERY = '->';
/**
 * 是否为Windows服务器环境
 * @defined Ke/Core/common.php
 */
const KE_IS_WIN = false;

/**
 * 路径名中的噪音值，主要用trim函数中
 * @defined Ke/Core/common.php
 */
const KE_PATH_NOISE = '/\\.';
/**
 * pathPurge函数，点（./../）删除处理
 * @defined Ke/Core/common.php
 */
const KE_PATH_DOT_REMOVE = 0;
/**
 * pathPurge函数，点（./../）转为正确的路径的处理
 * @defined Ke/Core/common.php
 */
const KE_PATH_DOT_NORMALIZE = 1;
/**
 * pathPurge函数，保持点（./../）不做任何处理
 * @defined Ke/Core/common.php
 */
const KE_PATH_DOT_KEEP = -1;
/**
 * pathPurge函数，最开头的路径分隔符强制清除
 * @defined Ke/Core/common.php
 */
const KE_PATH_LEFT_TRIM = 0;
/**
 * pathPurge函数，最开头的路径分隔符强制保留（如果没有会自动补充）
 * @defined Ke/Core/common.php
 */
const KE_PATH_LEFT_REMAIN = 1;
/**
 * pathPurge函数，最开头的路径分隔符维持原样
 * @defined Ke/Core/common.php
 */
const KE_PATH_LEFT_NATIVE = -1;

/**
 * 执行的脚本文件的全路径
 * @defined Ke/Core/Bootstrap.php
 */
const KE_SCRIPT_PATH = '/';
/**
 * 执行的脚本文件的目录路径
 * @defined Ke/Core/Bootstrap.php
 */
const KE_SCRIPT_DIR = '/';
/**
 * 执行的脚本文件的文件名
 * @defined Ke/Core/Bootstrap.php
 */
const KE_SCRIPT_FILE = '/';

/**
 * 当前PHP运行的运行模式
 * @defined Ke/Core/Bootstrap.php
 */
const KE_APP_MODE = '/';
/**
 * 当前项目的根目录的路径
 * @defined Ke/Core/Bootstrap.php
 */
const KE_APP = '/';
/**
 * 当前项目的网站目录的路径
 * @defined Ke/Core/Bootstrap.php
 */
const KE_WEB = '/';
/**
 * 当前项目的源代码目录的路径
 * @defined Ke/Core/Bootstrap.php
 */
const KE_SRC = '/';
/**
 * 当前项目的配置文件目录的路径
 * @defined Ke/Core/Bootstrap.php
 */
const KE_CONF = '/';
/**
 * 当前项目的Composer的路径
 * @defined Ke/Core/Bootstrap.php
 */
const KE_COMPOSER = '/';
/**
 * 当前项目的APP类的类名
 * @defined Ke/Core/Bootstrap.php
 */
const KE_APP_CLASS = '/';
/**
 * 当前项目的APP类命名空间，也是整个项目的根命名空间
 * @defined Ke/Core/Bootstrap.php
 */
const KE_APP_NS = '/';

/**
 * 项目运行环境
 * @defined Ke/Core/App.php
 */
const KE_APP_ENV = 'development';
/**
 * 项目名称
 * @defined Ke/Core/App.php
 */
const KE_APP_NAME = 'development';
/**
 * 项目的简写码
 * @defined Ke/Core/App.php
 */
const KE_APP_HASH = '';
/**
 * 项目加密的盐
 * @defined Ke/Core/App.php
 */
const KE_APP_SALT = '';
/**
 * 项目的时区
 * @defined Ke/Core/App.php
 */
const KE_APP_TIMEZONE = '';
/**
 * 项目的编码
 * @defined Ke/Core/App.php
 */
const KE_APP_ENCODING = '';

/** 当前请求的URI的SCHEME */
const KE_REQUEST_SCHEME = 'http';
/** 当前请求的URI的HOST */
const KE_REQUEST_HOST = 'localhost';
/** 当前请求的URI */
const KE_REQUEST_URI = '/';
/** 当前请求的URI的PATH */
const KE_REQUEST_PATH = '/';
/**
 * 当前请求的URI
 * @defined Ke/Core/App.php
 */
//const KE_HTTP_URI = '';
/**
 * 当前请求路径
 * @defined Ke/Core/App.php
 */
//const KE_HTTP_PATH = '';
/**
 * 当前请求的目录
 * @defined Ke/Core/App.php
 */
//const KE_HTTP_DIR = '';
/**
 * 当前请求的文件名
 * @defined Ke/Core/App.php
 */
//const KE_HTTP_FILE = '';
/**
 * 当前请求根目录
 * @defined Ke/Core/App.php
 */
const KE_HTTP_BASE = '';
/**
 * HTTP的安全验证字段名
 * @defined Ke/Core/App.php
 */
const KE_HTTP_SECURITY_FIELD = '';
/**
 * HTTP的安全验证的加密盐
 * @defined Ke/Core/App.php
 */
const KE_HTTP_SECURITY_SALT = '';

global $_KE;

$_KE = [
	'APP_PATH'            => null,
	'APP_CLASS'           => null,
	'APP_LOCALE'          => 'en_US',
	'SRC_PATH'            => null,
	'SRC_DIR'             => 'src',
	'WEB_PATH'            => null,
	'WEB_DIR'             => 'public',
	'CONF_PATH'           => null,
	'CONF_DIR'            => 'config',
	'COMPOSER_PATH'       => null,
	'COMPOSER_DIR'        => 'vendor',
	// loader switch
	'USE_KE_LOADER'       => true,
	'USE_COMPOSER_LOADER' => false,
];