<?php

namespace Agi\Util;

use Agi\Exception;

/**
 * Class String
 *
 * @package Agi\Util
 * @author Janpoem<janpoem@163.com>
 */
class String
{

    const JSON_SCHEME = 'json';

    const PHP_SERIALIZE_SCHEME = 'php';

    const CONCAT_SCHEME = 'concat';

    const CONCAT_DEFAULT_DELIMITER = ',';

    const SERIALIZE_SCHEME_REGEX = '#^(json|php|concat)(?:\[(.*)\])?:([\s\S]*)$#m';

    const REGEX_SUB = '#\{([^\{\}]+)\}#';

    const EMPTY_VALUE = '';

    const ZERO = '0';

    public static function from($value)
    {
        if ($value === null) return '';
        elseif ($value === true) return '1';
        elseif ($value === false) return '0';
        elseif ($value === 0) return '0';
        $type = gettype($value);
        if ($type === PHP_OBJ) {
            if (method_exists($value, '__toString'))
                return $value->__toString();
            else
                return get_class($value);
        } elseif ($type === PHP_RES) {
            return get_resource_type($value);
        } elseif ($type === PHP_ARY) {
            return 'array []';
        } else {
            return strval($value);
        }
    }

    public static function length($str)
    {
        return mb_strlen($str);
    }

    public static function width($str)
    {
        return mb_strwidth($str);
    }

    public static function cut($str, $length, $suffix = '...')
    {
        $strLen = mb_strlen($str);
        $suffixLen = mb_strlen($suffix);
        if ($strLen <= $length || $strLen <= $suffixLen)
            return $str;
        return (mb_substr($str, 0, $length - $suffixLen)) . $suffix;
    }

    public static function widthCut($str, $width, $suffix = '...')
    {
        $strWidth = mb_strwidth($str);
        $suffixWidth = mb_strwidth($suffix);
        if ($strWidth <= $width || $strWidth <= $suffixWidth)
            return $str;
        $newStr = mb_strimwidth($str, 0, $width, $suffix);
        return $newStr;
    }

    public static function substitute($str, $suffix = '---')
    {
        if(empty($str))
            return $suffix;
        return $str;
    }

    static public function path2class($path, $delimiters = '_\/\\\\')
    {
        $path = mb_strtolower(ltrim(ucwords($path), '/'));
        return preg_replace_callback('#(^|[' . $delimiters . '])([a-z]{1})?#', function ($matches) {
            return $matches[1] . mb_strtoupper($matches[2]);
        }, $path);
    }

    /**
     * 字符替换，使用{变量}，该方法不处理嵌套替换
     *
     * @param string $str
     * @param array $args
     * @param string $regex
     * @param array $matches
     *
     * @return string
     */
    public static function sub($str, array $args = array(), $regex = self::REGEX_SUB, array & $matches = null)
    {
        if (empty($str))
            return $str === self::ZERO ? $str : self::EMPTY_VALUE; // 字符串为'0'时，应该直接返回
        if (empty($args))
            return $str;
        if (empty($regex))
            $regex = self::REGEX_SUB;
        return preg_replace_callback($regex, function ($_matches) use ($args, & $matches) {
            if (isset($args[$_matches[1]])) {
                $matches[$_matches[1]] = $args[$_matches[1]];
                return $args[$_matches[1]];
            }
            return '';
        }, $str);
    }

    /**
     * 嵌套替换字符
     *
     * $args = array('a' => '你好，{b}！', 'b' => 'John');
     * $str = '{a}请登录！';
     *
     * String::deepSub($str, $args);
     *
     * @param string $str
     * @param array $args
     * @param string $regex
     * @param array $matches
     *
     * @return string
     */
    public static function deepSub($str, array $args = null, $regex = self::REGEX_SUB, array & $matches = null)
    {
        if (empty($regex))
            $regex = self::REGEX_SUB;
        $str = static::sub($str, $args, $regex, $matches);
        if (preg_match($regex, $str))
            return static::deepSub($str, $args, $regex, $matches);
        else
            return $str;
    }

    public static function summary($content, $len = 256)
    {
        $content = nl2br($content);
        $content = strip_tags($content);
        $content = str_replace('&nbsp;', ' ', $content);
        $content = trim($content);
        $content = preg_replace('/([\r\n]+|[\s]{2,})/i', ' ', $content);
        $content = static::cut($content, $len);
        return $content;
    }

    /**
     * 加密数据，允许直接传入数组或对象进行加密，但是解密出来的内容，会一律视作array处理
     *
     * @param mixed $content
     * @param string $key
     * @return string
     * @throws Exception
     */
    public static function encrypt($content, $key)
    {
        if (empty($key))
            throw new Exception('Encrypt empty key');
        $content = json_encode($content);
        $encryptContent = gzdeflate($content, 9);
        $hash = hash('sha256', $key);
        $packHash = pack('H*', $hash);
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        $cipherContent = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $packHash, $encryptContent, MCRYPT_MODE_CBC, $iv);
        $cipherHash = md5($cipherContent, true); // 增加一个密文的指纹
        $cipherContent = $iv . $cipherContent . $cipherHash;
        return base64_encode($cipherContent);
    }

    /**
     * 解密数据，如果加密传入的是一个对象，解密出来的会变成一个数组
     *
     * @param string $content
     * @param string $key
     * @return bool|mixed
     * @throws Exception
     */
    public static function decrypt($content, $key)
    {
        if (empty($key))
            throw new Exception('Decrypt empty key');
        $hash = hash('sha256', $key);
        $packHash = pack('H*', $hash);
        $decodeContent = base64_decode($content);
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $ivDec = substr($decodeContent, 0, $ivSize);
        $decryptContent = substr($decodeContent, $ivSize);
        $decryptHash = substr($decryptContent, -16); // 取出密文指纹
        $decryptContent = substr($decryptContent, 0, -16);
        if ($decryptHash !== md5($decryptContent, true))
            return false;
        $decryptContent = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $packHash, $decryptContent, MCRYPT_MODE_CBC, $ivDec);
//        $decryptContent = trim($decryptContent); // 这个总是感觉不是太放心
        $decryptContent = gzinflate($decryptContent);
        return json_decode($decryptContent, true);
    }

    public static function serialize($data, $scheme, $param = null) {
        $type = gettype($data);
        // 要先检查，如果data不是以下类型，则表示可以安全执行字符串检查
        if ($type !== PHP_ARY && $type !== PHP_OBJ && $type !== PHP_RES) {
            // 如果检查本身已经带有序列化的标记，则不管，直接返回值
            if (preg_match(self::SERIALIZE_SCHEME_REGEX, $data, $matches)) {
                return $data;
            }
        }
        if ($type === PHP_RES)
            $data = ''; // 资源类型不做序列化
        $str = $scheme;
        if ($scheme === self::JSON_SCHEME) {
            $str .= ':' . json_encode($data);
        }
        elseif ($scheme === self::CONCAT_SCHEME) {
            if (empty($param) || !is_string($scheme))
                $param = self::CONCAT_DEFAULT_DELIMITER;
            if ($type === PHP_OBJ) {
                $data = get_object_vars($data);
                $type = PHP_ARY;
            }
            if ($type !== PHP_ARY)
                $data = array($data);
            $str .= "[{$param}]:" . implode($param, $data);
        }
        else {
            $str .= ':' . serialize($data);
        }
        return $str;
    }

    public static function unserialize($value)
    {
        if (empty($value))
            return $value;
        $type = gettype($value);
        if ($type === PHP_ARY || $type === PHP_OBJ || $type === PHP_RES)
            return $value;
        if (preg_match(self::SERIALIZE_SCHEME_REGEX, $value, $matches)) {
            list(, $scheme, $param, $str) = $matches;
            if ($scheme === self::JSON_SCHEME) {
                return json_decode($str, true);
            }
            elseif ($scheme === self::PHP_SERIALIZE_SCHEME) {
                return unserialize($str);
            }
            elseif ($scheme === self::CONCAT_SCHEME) {
                if (empty($param))
                    $param = self::CONCAT_DEFAULT_DELIMITER;
                return explode($param, $str);
            }
        }
        // 不知道是何种类型的反序列化
        return $value;
    }
}
