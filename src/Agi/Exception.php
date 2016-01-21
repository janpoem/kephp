<?php
namespace Agi;

use \App;

/**
 * Class Exception
 * @package Agi
 * @author Janpoem<janpoem@163.com>
 */
class Exception extends \Exception
{

    final public function __construct($msg, $code = 0, \Exception $ex = null)
    {
        if (!empty($msg)) {
            $type = gettype($msg);
            $args = array();
            if ($type === PHP_ARY) {
                $args = $msg;
                $msg  = array_shift($args);
            }
            elseif ($type === PHP_OBJ) {
                $msg  = get_class($msg);
            }
            $msg = \App::getLang()->deepSub($msg, $args);
        }
        parent::__construct($msg, $code, $ex);
    }
}
