<?php

namespace Config;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;
use Workerman\Protocols\Http\Request;
use AnserGateway\Config\BaseConfig;

class Gateway extends BaseConfig
{
    /**
     * Auto Reload Mode
     *
     * @var string restart or sreload
     */
    public $autoReloadMode = 'restart';

    /**
     * Auto-scan of the root directory
     *
     * @var string
     */
    public $autoReloadDir = PROJECT_ROOT;

    /**
     * Files with these filename-extension will be auto-scanned.
     *
     * @var array
     */
    public $autoReloadScanExtensions = ['php', 'env'];

    /**
     * Set how many processes to start for the current Worker instance.
     * In non-development environment, the recommended number of workers
     * is twice the number of cpu cores.
     *
     * @var int
     */
    public $workerCount = 1;

    /**
     * Set which user to run the Worker instance as,
     * Windows does not support this setting.
     *
     * @var string
     */
    public $workerUser = 'www-data';

    /**
     * dump() output to the terminal will be redirected to
     * the specified file when workerman run as daemon mode.
     *
     * @var string
     */
    public $stdoutFile = '/dev/null';

    /**
     * Record information about the workerman framework itself,
     * such as start, stop, and some fatal errors(if any).
     *
     * @var string
     */
    public $logFile = PROJECT_WRITABLE . 'logs' . DIRECTORY_SEPARATOR . 'AnserGateway.log';

    /**
     * TCP HTTP service listening port
     *
     * @var int
     */
    public $listeningPort = 8080;

    /**
     * Whether to enable ssl connection
     *
     * @var bool
     */
    public $ssl = false;

    /**
     * ssl cert or pem file path
     *
     * @var string
     */
    public $sslCertFilePath = 'server.pem';

    /**
     * ssl key file path
     *
     * @var string
     */
    public $sslKeyFilePath = 'server.key';

    /**
     * ssl verify peer
     *
     * @var bool
     */
    public $sslVerifyPeer = false;

    /**
     * If it's a self-signed certificate, you need to turn on this option
     *
     * @var bool
     */
    public $sslAllowSelfSigned = false;

    /**
     * Set the default application layer send buffer size for all connections
     *
     * @var int byte
     */
    public $defaultMaxSendBufferSize = 1048576;

    /**
     * Set the max package size can be received
     *
     * @var int byte
     */
    public $defaultMaxPackageSize = 10485760;

    /**
     * Set your worker class name here.
     *
     * @var string[] WorkerRegistrars class name
     */
    public $serverWorkers= [
        \AnserGateway\Worker\GatewayWorker::class,
        // \AnserGateway\Worker\AutoloadFileMonitor::class,
    ];

    /**
     * Select whether service discovery is turned on.
     *
     * @var boolean
     */
    public $enableServiceDiscovery = false;

    // public function __construct()
    // {
    //     parent::__construct();
    // }

    /**
     * You can declare some additional worker setting in this method.
     *
     * @return void
     */
    public function initWorker(Worker &$worker)
    {
    }

    /**
     * Each tcp connection will automatically run this method before
     * starting the AnserGateway processing cycle, and you can control
     * the settings for each connection through this method.
     *
     * @return void
     */
    public function runtimeTcpConnection(TcpConnection &$tcpConnection, Request &$request)
    {
    }
}
