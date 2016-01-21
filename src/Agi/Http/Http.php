<?php

namespace Agi\Http;

/**
 * Class Base
 *
 * @package Agi\Http
 * @author Janpoem created at 2014/9/26 17:51
 */
abstract class Http
{

    const GET = 'GET';

    const POST = 'POST';

    const XHR_FIELD = 'HTTP_X_REQUESTED_WITH';

    const XHR_VALUE = 'xmlhttprequest';

    /**
     * Http StatusCode
     *
     * code => message
     *
     * @var array
     */
    protected static $statusCodes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'There are too many connections from your internet address',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        449 => 'Retry With',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
    );

    /**
     * MimeType声明
     *
     * format => contentType
     * format => array(contentType, charset)
     *
     * @var array
     */
    protected static $mimeTypes = array(
        'html'   => array('text/html', HTTP_CHARSET),
        'xml'    => array('application/xml', HTTP_CHARSET),
        'js'     => array('application/javascript', HTTP_CHARSET),
        'json'   => array('application/json', HTTP_CHARSET),
        'txt'    => array('text/plain', HTTP_CHARSET),
        'css'    => array('text/css', HTTP_CHARSET),
        'csv'    => array('text/csv', HTTP_CHARSET),
        'gif'    => 'image/gif',
        'jpg'    => 'image/jpeg',
        'png'    => 'image/png',
        'pdf'    => 'application/pdf',
        'zip'    => 'application/zip',
        'tar'    => 'application/x-tar',
        'rar'    => 'application/x-rar-compressed',
        '7z'     => 'application/x-7z-compressed',
        'bz'     => 'application/x-bzip',
        'bz2'    => 'application/x-bzip2',
        'gz'     => 'application/x-gzip',
        'xls'    => 'application/vnd.ms-excel',
        'xlsx'   => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'doc'    => 'application/msword',
        'docx'   => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'stream' => 'application/octet-stream',
    );

    /**
     * 对MimeType的别名
     *
     * format => format
     *
     * @var array
     */
    protected static $formats = array(
        'jpeg' => 'jpg',
        'htm'  => 'html',
    );

    public static function setStatusCode($code, $message)
    {
        if (is_numeric($code) && $code > 0 && !empty($message) && is_string($message))
            self::$statusCodes[$code] = $message;
    }

    public static function setStatusCodes(array $codes)
    {
        foreach ($codes as $code => $message)
            static::setStatusCode($code, $message);
    }

    public static function setMimeType($format, $contentType, $charset = null)
    {
        $format = mb_strtolower(trim($format, '.'));
        if (!empty($format) && is_string($format)) {
            $type = gettype($contentType);
            $mimeType = array();
            // 'text/html'
            if ($type === PHP_STR && !empty($contentType)) {
                $mimeType[] = $contentType;
                if (!empty($charset) && is_string($charset))
                    $mimeType[] = $charset;
            }
            // array('text/html', 'UTF-8')
            // array('text/html')
            elseif ($type === PHP_ARY && !empty($contentType[0]) && is_string($contentType[0])) {
                $mimeType[] = $contentType[0];
                if (!empty($contentType[1]) && is_string($contentType[1]))
                    $mimeType[] = $contentType[1];
            }
            if (!empty($mimeType)) {
                self::$mimeTypes[$format] = $mimeType;
                // 如果已经存在了这个format的alias，添加完以后，要去掉这个format原来的alias
                if (isset(self::$formats[$format]))
                    unset(self::$formats[$format]);
            }
        }
    }

    public static function alias($newFormat, $format)
    {
        $format = mb_strtolower(trim($format, '.'));
        $newFormat = mb_strtolower(trim($newFormat, '.'));
        $isRightFormat = !empty($newFormat) && is_string($newFormat);
        // 新格式为有效的格式，并且指定的format已经存在，且新格式并未定义在mimeTypes中
        if ($isRightFormat && isset(self::$mimeTypes[$format]) && !isset(self::$mimeTypes[$newFormat])) {
            self::$formats[$newFormat] = $format;
        }
    }

    public static function setMimeTypes(array $types)
    {
        foreach ($types as $format => $type)
            static::setMimeType($format, $type);
    }

    public static function hasMimeType($format)
    {
        $format = mb_strtolower(trim($format, '.'));
        return isset(self::$formats[$format]) || isset(self::$mimeTypes[$format]);
    }

    public static function getMimeType($format)
    {
        if (isset(self::$formats[$format]))
            $format = self::$formats[$format];
        if (isset(self::$mimeTypes[$format]))
            return self::$mimeTypes[$format];
        return false;
    }

    public static function getStatusCode($code)
    {
        if (isset(self::$statusCodes[$code]))
            return self::$statusCodes[$code];
        return false;
    }

    public static function filterContentType($contentType)
    {
        if (empty($contentType))
            return false;
        $str = "Content-Type: ";
        $type = gettype($contentType);
        if ($type === PHP_STR)
            $str .= $contentType;
        elseif ($type === PHP_ARY) {
            $str .= $contentType[0];
            if (isset($contentType[1]))
                $str .= "; charset={$contentType[1]}";
        }
        return $str;
    }

    public static function detectContentType($format)
    {
        $type = static::getMimeType($format);
        if ($type !== false)
            return static::filterContentType($type);
        return false;
    }

    public static function detectStatusCode($code)
    {
        if (isset(self::$statusCodes[$code])) {
            $message = self::$statusCodes[$code];
            return "HTTP/1.1 {$code} {$message}";
        }
        return false;
    }
}

 