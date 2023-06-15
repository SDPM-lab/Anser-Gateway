<?php

namespace AnserGateway\Worker;

use AnserGateway\Autoloader;
use AnserGateway\Worker\WorkerRegistrar;
use Config\Gateway;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;
use AnserGateway\Worker\Swow;
use Swow\Coroutine;
use AnserGateway\Router\RouteCollector;
use AnserGateway\Router\Router;
use AnserGateway\AnserGateway;

class GatewayWorker extends WorkerRegistrar
{
    protected Gateway $gatewayConfig;

    public function __construct()
    {
        $this->gatewayConfig = new Gateway();
        self::staticSetting();
    }

    public function initWorker(): Worker
    {
        $config    = $this->gatewayConfig;

        $webWorker = new Worker(
            sprintf(
                '%s://%s:%s',
                $config->ssl ? 'https' : 'http',
                '0.0.0.0',
                $config->listeningPort
            ),
            $this->gatewayConfig->ssl ? [
                'ssl' => [
                    'local_cert'        => $config->sslCertFilePath,
                    'local_pk'          => $config->sslKeyFilePath,
                    'verify_peer'       => $config->sslVerifyPeer,
                    'allow_self_signed' => $config->sslAllowSelfSigned,
                ],
            ] : []
        );
        $webWorker->name = 'AnserGateway';
        $webWorker->reloadable = true;
        $this->instanceSetting($webWorker);

        // On start
        $webWorker->onWorkerStart = static function (Worker $worker) use ($config) {
            Autoloader::$instance->appRegister();
            Autoloader::$instance->composerRegister();
            //此處開始框架其他部件初始化

        };

        // Worker
        $webWorker->onMessage = static function (TcpConnection $connection, Request $request) use ($config) {
            Coroutine::run(static function () use ($connection, $request, $config): void {
                $config->runtimeTcpConnection($connection, $request);

                # Do get routeCollector and new a Router class
                $routeList = RouteCollector::loadRoutes();
                $router    = new Router($routeList);
                # Injection Router class to AnserGateway
                $gateway   = new AnserGateway($router);

                try {
                    $workermanResponse = $gateway->handleRequest($request);
                } catch (\Exception $e) {
                    $workermanResponse = new Response(
                        500,
                        [
                            'Content-Type' => 'application/json charset=utf-8',
                        ],
                        json_encode([
                            'code' => 500,
                            'msg' => $e->getMessage(),
                            'data' => null
                        ])
                    );
                }

                //此處將響應轉換成 Workerman 的 Response
                // $workermanResponse = new Response(
                //     200,
                //     [],
                //     ''
                // );
                $connection->send($workermanResponse);
            });


        };

        return $webWorker;
    }

    protected function instanceSetting(Worker &$worker)
    {
        $worker->count      = $this->gatewayConfig->workerCount;
        $worker->user       = $this->gatewayConfig->workerUser;
        $this->gatewayConfig->initWorker($worker);
    }

    public function staticSetting()
    {
        Worker::$eventLoopClass                  = Swow::class;
        Worker::$stdoutFile                      = $this->gatewayConfig->stdoutFile;
        Worker::$logFile                         = $this->gatewayConfig->logFile;
        TcpConnection::$defaultMaxPackageSize    = $this->gatewayConfig->defaultMaxPackageSize;
        TcpConnection::$defaultMaxSendBufferSize = $this->gatewayConfig->defaultMaxSendBufferSize;
    }

}
