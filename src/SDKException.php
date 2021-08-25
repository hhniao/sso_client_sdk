<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/7/12
 * @copyright Canton Univideo
 */

namespace SSOClientSDK;


use Exception;
use Throwable;

class SDKException extends Exception
{
    private $data = [];

    public function __construct($message = "", $code = 0, $data = [], Throwable $previous = null)
    {
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    public function getData()
    {
        return $this->data;
    }
}