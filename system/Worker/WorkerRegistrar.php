<?php

namespace AnserGateway\Worker;

use Workerman\Worker;

abstract class WorkerRegistrar
{
    protected Worker $worker;

    abstract public function initWorker(): Worker;
}
