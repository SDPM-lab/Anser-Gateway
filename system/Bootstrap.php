<?php

namespace AnserGateway;

use AnserGateway\WorkerContainer;
use Config\Gateway;

class Bootstrap
{
    public static function serverRun()
    {
        $gatewayConfig = new Gateway();
        WorkerContainer::registerWorkers($gatewayConfig->serverWorkers);
        WorkerContainer::run();
    }

    public static function testRun()
    {
        $gatewayConfig = new Gateway();
        WorkerContainer::registerWorkers($gatewayConfig->serverWorkers);
        // WorkerContainer::run();
    }
}
