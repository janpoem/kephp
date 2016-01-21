<?php

namespace Agi\Route;

use Agi\Http\Request;
use Agi\Action\Parameters;

/**
 * Class Router
 *
 * 路由器类，已经将Routes和Router的逻辑严格分离，对于Router来说，他只有业务逻辑——就是routing，
 * 数据处理的细节，交给Routes处理。
 *
 * 因为目前Routes已经面向可被缓存处理 =>
 * 而Router处理的内容，也明确是针对url purePath处理的，purePath已经去掉了path的前缀和ext后缀 =>
 * 并且特别设定了Result的类来装载每个purePath处理的结果，可以理解，每个purePath对应一个Result结果 =>
 *
 * @todo 实际上Router处理的结果，也可以被缓存处理
 *
 * 特别将Router从Http包中分离出来，Http包和Action包不应该存在必然的耦合性，
 * Http/Request和Http/Response为应该作为独立的和Action无关的东西。
 *
 * 而Route包则属于要创建一个有效的Action\Parameters必须的过程。
 *
 * Http\Request -> Route\Router -> Route\Result -> Action\Parameters -> Action\Controller
 *
 * 当不希望使用MVC模式，则可以完全脱离Route和Action包，仅仅只使用Http包。
 *
 * @package Route
 * @author Janpoem created at 2014/9/22 19:02
 */
class Router
{

    /** @var array */
    private static $instances = array();

    /** @var Routes */
    protected $routes = null;

    /**
     * 基于flag取得Router实例，每一个Router应该对应一个专属的Routes
     *
     * @param string $flag
     * @return Router
     */
    final public static function getInstance($flag)
    {
        if (!isset(self::$instances[$flag])) {
            $class = get_called_class();
            self::$instances[$flag] = new $class(Routes::getInstance($flag));
        }
        return self::$instances[$flag];
    }

    /**
     * Router是进行路由匹配的业务逻辑的
     *
     * Router的逻辑怎么进行，基于Router所加载的Routes配置，所以，为了确保Router有自己专属对应的Routes，
     * 将Router构造函数设为私有，而通过Router::getInstances($flag)的方式来取得相关的实例。
     *
     * @param Routes $routes
     */
    final private function __construct(Routes $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @return Routes
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param Request $request
     * @return Result
     */
    public function routing(Request $request)
    {
        $result = new Result($this->routes->defaultParams);
        // 不再绑定request
//        $result->request = $request;
//        $result->router = $this;
        $result->path = $request->purePath; // 不在这里将path转小写，而在初始化Params的时候进行过滤
        $this->detectNamespace($result)->mapping($result);
        return $result;
    }

    protected function detectNamespace(Result $result)
    {
        $pos = null;
        $ns = trim($result->path, PATH_NOISE);
        while (!empty($ns)) {
            if (isset($this->routes->modules[$ns])) {
                // 补完namespace设置
                if (empty($this->routes->modules[$ns]['namespace']) || !is_string($this->routes->modules[$ns]['namespace']))
                    $this->routes->modules[$ns]['namespace'] = $ns;
                if (empty($this->routes->modules[$ns]['mappings']) || !is_array($this->routes->modules[$ns]['mappings']))
                    $this->routes->modules[$ns]['mappings'] = array();
                $result->path = isset($pos) ? substr($result->path, $pos + 1) : SPR_HTTP_DIR;
                $result->module = $ns;
                $result->params['namespace'] = $this->routes->modules[$ns]['namespace'];
                break;
            }
            $pos = strrpos($ns, '/');
            $ns = substr($ns, 0, $pos);
        }
        return $this;
    }

    protected function mapping(Result $result)
    {
        if ($result->baseMapping)
            $mappings = &$this->routes->baseMappings;
        else if (empty($result->module))
            $mappings = &$this->routes->mappings;
        else
            $mappings = &$this->routes->modules[$result->module]['mappings'];

        if (!empty($mappings)) {
            foreach ($mappings as $name => & $mapping) {
                if (empty($mapping))
                    continue;
                if (!isset($mapping['_pattern_']))
                    $mapping = $this->routes->compileRoute($mapping);
                if (empty($mapping) || empty($mapping['_pattern_']))
                    continue;
                if (preg_match($mapping['_pattern_'], $result->path, $result->matches)) {
                    $result->matched = $mapping;
                    $tail = substr($result->path, mb_strlen($result->matches[0]));
                    if (!empty($tail))
                        $tail = trim($tail, PATH_NOISE);
                    if (!empty($mapping[2]))
                        $result->params += $mapping[2];
                    $params = array_intersect_key($result->matches, $mapping['_tokens_']);
                    if (!empty($params))
                        $result->params = array_merge($result->params, $params);
                    $result->params['tail'] = empty($tail) ? null : $tail;
                    if (isset($mapping[3]) && is_callable($mapping[3]))
                        call_user_func($mapping[3], $result);
                    break;
                }
            }
        }

        if ($result->matched === false) {
            if (!$result->baseMapping) {
                $result->baseMapping = true;
                $this->mapping($result);
            } else {
                // 当已经是baseMapping，而且还是没有匹配到的结果
                return $this;
            }
        }
        return $this;
    }
}
