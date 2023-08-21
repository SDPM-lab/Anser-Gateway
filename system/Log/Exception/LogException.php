<?php

namespace AnserGateway\Log\Exception;

use AnserGateway\Exception\AnserGatewayException;

class LogException extends AnserGatewayException
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

    public static function forLogLevelInvalid($level): LogException
    {
        return new self("Log Level 錯誤 : {$level} 不存在，請確認 Log Level 是否為以下其一，[debug, info, notice, warning, error, critical, alert, emergency]");
    }

}
