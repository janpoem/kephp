<?php

namespace Agi\Output;

use Agi\Action\Parameters;

/**
 * Class Responder
 *
 * @package Agi\Output
 * @author Janpoem created at 2014/9/25 12:06
 */

/**
 * Interface Responder
 *
 * 响应器接口，具体实现交给实现类，提供更高的灵活性给不同的输出相应器的设计。
 *
 * @package Agi\Output
 */
interface Responder
{

    public function assign($key, $value = null);

    public function output();

}
