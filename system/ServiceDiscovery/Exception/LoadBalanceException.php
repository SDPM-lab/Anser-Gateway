<?php

namespace AnserGateway\ServiceDiscovery\Exception;

use AnserGateway\Exception\AnserGatewayException;

class LoadBalanceException extends AnserGatewayException
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

    public static function forStrategyNotFound($strategyName): LoadBalanceException
    {
        return new self("{$strategyName} 負載策略不存在，請確認設定的策略是否存在。");
    }
}
