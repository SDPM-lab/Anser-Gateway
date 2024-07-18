<?php

namespace AnserGateway;

use AnserGateway\Worker\WorkerRegistrar;
use Workerman\Worker;

class WorkerContainer
{
    protected static $baseWorkers = [];

    /**
     * Worker list
     *
     * @var array<string,Worker>
     */
    protected static $workerList = [];

    /**
     * Get worker by worker registrar class name
     */
    public static function getWorker(string $workerRegistrarClassName): ?Worker
    {
        return self::$workerList[$workerRegistrarClassName] ?? null;
    }

    /**
     * Merge worker list
     *
     * @return void
     */
    public static function registerWorkers(array $workerRegistrarClasses)
    {
        self::$baseWorkers = array_merge(self::$baseWorkers, $workerRegistrarClasses);
    }

    /**
     * Init and run all workers
     *
     * @return void
     */
    public static function run()
    {
        self::init();
        Worker::runAll();
    }

    /**
     * Init worker
     *
     * @return void
     */
    protected static function init()
    {
        foreach (self::$baseWorkers as $workerRegistrarClass) {
            /** @var WorkerRegistrar */
            $workerRegistrar                         = new $workerRegistrarClass();
            $worker                                  = $workerRegistrar->initWorker();
            self::$workerList[$workerRegistrarClass] = $worker;
        }
    }
}
