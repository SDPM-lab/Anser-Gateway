<?php

namespace AnserGateway\Filters\Exception;

use AnserGateway\Exception\AnserGatewayException;

class FilterException extends AnserGatewayException
{
    /**
     * 初始化
     *
     * @param string $message 錯誤訊息
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forNoAlias($name): FilterException
    {
        return new self("{$name} Filter 必須有一個匹配的別名定義");
    }

    public static function forIncorrectInterface($class): FilterException
    {
        return new self("{$class} 必須實作 AnserGateway/Filters/FilterInterface");
    }

}
