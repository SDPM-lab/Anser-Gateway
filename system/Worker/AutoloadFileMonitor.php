<?php

namespace AnserGateway\Worker;

use AnserGateway\Worker\WorkerRegistrar;
use Config\Gateway;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Workerman\Timer;
use Workerman\Worker;

class AutoloadFileMonitor extends WorkerRegistrar
{
    protected Gateway $gatewayConfig;

    public function __construct()
    {
        $this->gatewayConfig = new Gateway();
    }

    public function initWorker(): Worker
    {
        $worker             = new Worker();
        $worker->name       = 'AutoloadFileMonitor';
        $worker->reloadable = false;

        global $last_mtime;
        $last_mtime     = time();
        $monitorDir     = $this->gatewayConfig->autoReloadDir;
        $scanExtensions = $this->gatewayConfig->autoReloadScanExtensions;
        $reloadMode     = $this->gatewayConfig->autoReloadMode;

        $worker->onWorkerStart = static function () use ($monitorDir, $scanExtensions, $reloadMode) {
            // watch files only in daemon mode
            if (! Worker::$daemonize) {
                // chek mtime of files per second
                Timer::add(
                    1,
                    static function (string $monitorDir, array $scanExtensions, string $reloadMode) {
                        global $last_mtime;

                        // recursive traversal directory
                        $dir_iterator = new RecursiveDirectoryIterator($monitorDir);
                        $iterator     = new RecursiveIteratorIterator($dir_iterator);

                        foreach ($iterator as $file) {
                            // only check php files
                            if (in_array(pathinfo($file, PATHINFO_EXTENSION), $scanExtensions, true) !== true) {
                                continue;
                            }

                            // check mtime
                            if ($last_mtime < $file->getMTime()) {
                                posix_kill(posix_getppid(), SIGUSR1);
                                $last_mtime = $file->getMTime();
                                break;
                            }
                        }
                    },
                    [$monitorDir, $scanExtensions, $reloadMode]
                );
            }
        };

        return $worker;
    }
}
