<?php
/**
 * 自定义异常类
 * @author shenyang
 * @date 2019-03-13
 */

namespace app\common;

use Throwable;

class MyException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
