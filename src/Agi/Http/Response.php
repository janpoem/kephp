<?php

namespace Agi\Http;

use Agi\Output\Buffer;
use Agi\Output\Exception;
use Agi\Output\Responder;

/**
 * Class Response
 *
 * Response只处理http从输出header，到output，根据传入output的Agi\Impl\Renderer实例，决定如何render
 * 所以，Response除了绑定和HttpHeader输出有关的数据外，并不负载任何和render有关的数据，controller、helper等数据实例
 * 应该负载在Renderer实例中。
 *
 * 为了便于方法的链式调用，所以Response被设计成全局的单例模式，使用Response::getInstance()方式取得全局唯一实例。
 *
 * 如：
 * <code>
 * $view = new Agi\Renderer\View();
 * $view->setLayout('default');
 * $view->setRenderFile('index/sign_in');
 * Response::getInstance()->setFormat('txt')->output($view);
 * </code>
 *
 * @package Agi\Http
 * @author Janpoem<janpoem@163.com>
 */
class Response
{

    const DEFAULT_FORMAT = 'html';

    /** 初始化的状态 */
    const INIT = -1;

    /** 已经发送Header状态 */
    const SEND_HEADER = 0;

    /** 完整输出状态 */
    const OUTPUT = 1;

    /** @var Response */
    private static $instance = null;

    /** @var int 当前Response的状态 */
    private static $status = self::INIT;

    /** @var array 需要输出的Headers */
    private static $headers = array();

    protected $statusCode = 200;

    protected $mimeType = array();

    protected $format = self::DEFAULT_FORMAT;

    /**
     * 返回当前Response的状态
     *
     * @return int
     */
    final public static function getStatus()
    {
        return self::$status;
    }

    /**
     * 检查Response状态是否指定值
     * @param int $equal
     * @return bool
     */
    final public static function isStatus($equal)
    {
        return self::$status === $equal;
    }

    final public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $class = get_called_class();
            self::$instance = new $class;
        }
        return self::$instance;
    }

    final private function __construct()
    {

    }

    final public function setFormat($format)
    {
        $this->format = strtolower($format);
        return $this;
    }

    final public function setMimeType($contentType, $charset = null)
    {
        if (!empty($contentType) && is_string($contentType)) {
            $this->mimeType = array($contentType);
            if (!empty($charset) && is_string($charset))
                $this->mimeType[] = $charset;
        }
        return $this;
    }

    final public function setStatusCode($code)
    {
        if (is_numeric($code) && $code > 0)
            $this->statusCode = intval($code);
        return $this;
    }

    final public function addHeader($header)
    {
        if (!empty($header))
            self::$headers[] = $header;
        return $this;
    }

    final public function addHeaders(array $headers)
    {
        self::$headers += $headers;
        return $this;
    }

    final public function mergeHeaders(array $headers)
    {
        self::$headers = array_merge(self::$headers, $headers);
        return $this;
    }

    final public function sendHeader()
    {
        if (headers_sent())
            self::$status = self::SEND_HEADER;
        if (self::$status === self::INIT) {
            self::$status = self::SEND_HEADER;
            $httpStatusCode = Http::detectStatusCode($this->statusCode);
            if (!empty($this->mimeType))
                $contentType = Http::filterContentType($this->mimeType);
            else
                $contentType = Http::detectContentType($this->format);
            if ($httpStatusCode !== false)
                header($httpStatusCode, true);
            if ($contentType !== false)
                header($contentType, true);
            if (!empty(self::$headers)) {
                // headers只支持2级数组
                foreach (self::$headers as $field => $header) {
                    if (!empty($header) && is_string($header)) {
                        header($header);
                    }
                }
            }
        }
        return $this;
    }

    final public function removeHeader()
    {
        // 只要确保状态还没变成output状态下
        if (self::$status <= self::SEND_HEADER) {
            header_remove();
            self::$status = self::INIT; // 回滚状态
        }
        return $this;
    }

    final public function respond(Responder $responder = null)
    {
        // 清空已经存在缓冲内容，确保在输出header前所输出的内容都被清空
        Buffer::getInstance()->clean();
        // 先检测是否处于初始化状态，输出header
        if (self::$status === self::INIT)
            $this->sendHeader();
        // 重新启动一个新的输出缓冲控制，用于控制在渲染过程出现的异常
        Buffer::getInstance()->start();
        try {
            // 不是输出完成的状态时，开始处理renderer输出
            if (self::$status !== self::OUTPUT && isset($responder)) {
                $responder->output(); // render就一句而已
                self::$status = self::OUTPUT; // 成功了，才变更状态
            }
        } catch (\Exception $ex) {
            // 捕捉到渲染过程内的异常
            Buffer::getInstance()->clean(); // 同样需要清空掉已经输出的内容
            // 抛出Output异常，让全局的App::exceptionHandle捕捉，并进入异常流程
            throw new Exception('output.exception', null, $ex);
        }
        return $this;
    }
}
