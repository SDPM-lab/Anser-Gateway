<?php

namespace AnserGateway\ServiceDiscovery\Exception;

use AnserGateway\Exception\AnserGatewayException;

class ServiceDiscoveryException extends AnserGatewayException
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

    public static function forServiceNotFound($serviceName): ServiceDiscoveryException
    {
        return new self("服務 - {$serviceName} 並未被搜尋成功，請確認是否已成功註冊。");
    }

    public static function forAnserGatewayRegisterError($data): ServiceDiscoveryException
    {
        return new self("AnserGateway 註冊至 Consul 時發生錯誤，錯誤訊息 - [{$data['code']}] {$data['msg']}，請確認.env檔中，關於 servicediscovery 或 gatewayregister 設定是否有誤");
    }

    public static function forConsulServiceNotFound($statusCode): ServiceDiscoveryException
    {
        return new self("HTTP連線錯誤[{$statusCode}]，請確認服務探索中心 - Consul 是否運行正常");
    }
}
