<?php

namespace AnserGateway\Worker;

use Config\Gateway;
use Swow\Coroutine;
use Workerman\Timer;
use Workerman\Worker;
use AnserGateway\Autoloader;
use AnserGateway\Worker\Swow;
use AnserGateway\AnserGateway;
use AnserGateway\Router\Router;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use SDPMlab\Anser\Service\ServiceList;
use AnserGateway\Router\RouteCollector;
use Workerman\Connection\TcpConnection;
use AnserGateway\HTTPConnectionManager;
use AnserGateway\Worker\WorkerRegistrar;
use AnserGateway\ServiceDiscovery\ServiceDiscovery;

class GatewayWorker extends WorkerRegistrar
{
    protected Gateway $gatewayConfig;

    public static $routeList;

    public static $router;

    public static $serviceDiscovery;

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
            require_once PROJECT_CONFIG . 'Service.php';
            //此處開始框架其他部件初始化
            \AnserGateway\Worker\GatewayWorker::$serviceDiscovery = new ServiceDiscovery();
            \AnserGateway\Worker\GatewayWorker::$routeList        = RouteCollector::loadRoutes();
            \AnserGateway\Worker\GatewayWorker::$router           = new Router(\AnserGateway\Worker\GatewayWorker::$routeList);


            ServiceList::setGlobalHandlerStack(HTTPConnectionManager::connectionMiddleware());
            HTTPConnectionManager::$hostMaxConnectionNum = 150;
            HTTPConnectionManager::$waitConnectionTimeout = 200;

            // Timer包co ，實作服務發現邏輯...
            // first discovery
            \AnserGateway\Worker\GatewayWorker::$serviceDiscovery->doServiceDiscovery();
            Timer::add(
                \AnserGateway\Worker\GatewayWorker::$serviceDiscovery->reloadTime,
                static function () {
                    Coroutine::run(static function (): void {
                        \AnserGateway\Worker\GatewayWorker::$serviceDiscovery->doServiceDiscovery();
                    });
                }
            );
        };

        // Worker
        $webWorker->onMessage = static function (TcpConnection $connection, Request $request) use ($config) {
            Coroutine::run(static function () use ($connection, $request, $config): void {
                $config->runtimeTcpConnection($connection, $request);

                # Injection Router class to AnserGateway
                $gateway = new AnserGateway(\AnserGateway\Worker\GatewayWorker::$router);

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
                unset($gateway);
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
