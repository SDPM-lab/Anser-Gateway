<?php
use AnserGateway\Log\Logger;

if (! function_exists('log_message')) {
    /**
     * 全域使用的log方法
     * 允許的LOG等級:
     *  - emergency
     *  - alert
     *  - critical
     *  - error
     *  - warning
     *  - notice
     *  - info
     *  - debug
     *
     * @return bool
     */
    function log_message(string $level, string $message, array $context = []): bool
    {
        $logger = new Logger();

        return $logger->log($level, $message, $context);
    }
}
