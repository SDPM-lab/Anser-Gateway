<?php

namespace AnserGateway\ServiceDiscovery;

use Config\ServiceDiscovery as ServiceDiscoveryConfig;
use Config\GatewayRegister;
use SDPMlab\Anser\Service\Action;
use Psr\Http\Message\ResponseInterface;
use SDPMlab\Anser\Service\ConcurrentAction;
use SDPMlab\Anser\Exception\ActionException;
use AnserGateway\ServiceDiscovery\LoadBalance\LoadBalance;
use AnserGateway\ServiceDiscovery\Exception\ServiceDiscoveryException;
use SDPMlab\Anser\Service\ServiceSettings;

class ServiceDiscovery
{
    /**
     * ServiceDiscovery 設定檔實體
     *
     * @var \Config\ServiceDiscovery
     */
    protected $serviceDiscoveryConfig;

    /**
     * GatewayRegister 設定檔實體
     *
     * @var GatewayRegister
     */
    protected $gatewayRegister;

    /**
     * 須被訪問的服務
     *
     * @var array
     */
    protected array $defaultServiceGroup = [];

    /**
     * 重新搜尋的等待Timer
     *
     * @var integer
     */
    public int $reloadTime;

    /**
     * Consul Server的真實位置 e.g. http://127.0.0.1:8500
     *
     * @var string
     */
    protected string $consulAddress;

    /**
     * 即將訪問的Consul Server DataCenter名稱
     *
     * @var string
     */
    protected string $consulDataCenter;

    /**
     * 負載均衡策略
     *
     * @var object
     */
    public $LBStrategy;


    /**
     * 從Consul Server探索的可訪問服務
     *
     * @var array<string,array<string,string>>
     */
    public array $localServices = [];

    /**
     * 用於比對的可訪問服務，參照$service屬性
     *
     * @var array<string,array<string,string>>
     */
    protected array $localVerifyServices = [null];


    public function __construct()
    {
        $this->serviceDiscoveryConfig = new ServiceDiscoveryConfig();
        $this->gatewayRegister        = new GatewayRegister();
        $this->defaultServiceGroup    = $this->serviceDiscoveryConfig->defaultServiceGroup;
        $this->reloadTime             = $this->serviceDiscoveryConfig->reloadTime;
        $this->consulDataCenter       = $this->serviceDiscoveryConfig->dataCenter;
        $this->consulAddress          = sprintf(
            '%s://%s',
            strtolower($this->serviceDiscoveryConfig->scheme),
            $this->serviceDiscoveryConfig->address,
        );
        $this->LBStrategy             = $this->serviceDiscoveryConfig->LBStrategy;
        LoadBalance::setStrategy($this->LBStrategy);
    }

    /**
     * 註冊AnserGateway 至 Consul Server
     *
     * @param string $httpScheme
     * @param integer $port
     * @return bool
     */
    public function registerSelf(string $httpScheme, int $port): bool
    {

        $gatewayAddress =  sprintf(
            '%s://%s:%s',
            $httpScheme,
            $this->gatewayRegister->address,
            $port
        );

        $checkRoute = sprintf(
            '%s/%s',
            $gatewayAddress,
            $this->gatewayRegister->healthRoute
        );

        array_push($this->gatewayRegister->tags, "http_scheme={$httpScheme}");

        $action = (new Action(
            $this->consulAddress,
            "PUT",
            "v1/agent/service/register"
        ))->addOption("json", [
            "id" => $this->gatewayRegister->id,
            "name" => $this->gatewayRegister->name,
            "tags" => $this->gatewayRegister->tags,
            "address" => $this->gatewayRegister->address,
            "port" => (int)$port,
            "check" => [
                "name" => $this->gatewayRegister->name,
                "service_id" => $this->gatewayRegister->id,
                "http" => $checkRoute,
                "interval" => $this->gatewayRegister->interval,
                "timeout" => $this->gatewayRegister->timeout
            ]
        ])->doneHandler(function (
            ResponseInterface $response,
            Action $runtimeAction
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        })->failHandler(function (
            ActionException $e
        ) {
            if($e->isClientError()) {
                $e->getAction()->setMeaningData([
                    "code" => $e->getStatusCode(),
                    "msg" => "client error"
                ]);
            } elseif ($e->isServerError()) {
                $e->getAction()->setMeaningData([
                    "code" => $e->getStatusCode(),
                    "msg" => "server error"
                ]);
            } elseif($e->isConnectError()) {
                $e->getAction()->setMeaningData([
                    "msg" => $e->getMessage()
                ]);
            }
        });

        $data = $action->do()->getMeaningData();

        if (isset($data['msg'])) {
            throw \AnserGateway\ServiceDiscovery\Exception\ServiceDiscoveryException::forAnserGatewayRegisterError($data);
        }

        /**
         * Consul 註冊成功回傳為null
         */
        if(is_null($data)) {
            return true;
        }
        return false;
    }

    /**
     * 執行服務探索步驟
     * 於ServiceDiscoverWorker被呼叫
     *
     * @return void|null
     */
    public function doServiceDiscovery()
    {
        if(!$this->isNeedToUpdateServiceList()) {
            return;
        }
        $this->setLocalServices();
    }

    /**
     * 使用Anser-Action 做並行請求，取得所有服務資訊
     *
     * @return array
     */
    public function doFoundServices(): array
    {
        $actionList = [];

        foreach ($this->defaultServiceGroup as $serviceName) {
            $action = (new Action(
                $this->consulAddress,
                "GET",
                "/v1/health/service/{$serviceName}"
            ))->addOption("query", [
                "passing" => "true",
                "dc"      => $this->consulDataCenter
            ])->doneHandler(function (
                ResponseInterface $response,
                Action $runtimeAction
            ) {
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);
                $runtimeAction->setMeaningData($data);
            })->failHandler(function (
                ActionException $e
            ) {
                if($e->isClientError()) {
                    $e->getAction()->setMeaningData([
                        "code" => $e->getStatusCode(),
                        "msg" => "client error"
                    ]);
                } elseif ($e->isServerError()) {
                    $e->getAction()->setMeaningData([
                        "code" => $e->getStatusCode(),
                        "msg" => "server error"
                    ]);
                } elseif($e->isConnectError()) {
                    $e->getAction()->setMeaningData([
                        "msg" => $e->getMessage()
                    ]);
                }
            });
            $actionList[$serviceName] = $action;
        }

        $concurrent = new ConcurrentAction();
        $concurrent->setActions($actionList)->send();
        return $concurrent->getActionsMeaningData();
    }

    /**
     * 設定本地service陣列
     *
     * @return void
     */
    public function setLocalServices(): void
    {
        $this->cleanLocalServices();
        $servicesData = $this->doFoundServices();

        foreach ($servicesData as $serviceName => $serviceData) {
            if (is_null($servicesData[$serviceName]) || count($serviceData) == 0) {
                throw ServiceDiscoveryException::forServiceNotFound($serviceName);
            }

            if(count($servicesData[$serviceName]) > 1) {
                $this->setServices($serviceData);
            } else {
                $this->setService($serviceData);
            }
        }
    }

    /**
     * 如服務回傳多組service的話則使用該方法
     *
     * @param array $services
     * @return void
     */
    public function setServices(array $services): void
    {
        foreach ($services as $service) {
            $serviceEntity        = $service["Service"];
            $serviceTags          = $serviceEntity["Tags"];
            $serviceName          = $serviceEntity["Service"];
            $serviceAddress       = $serviceEntity["Address"];
            $servicePort          = $serviceEntity["Port"];
            $serviceSchemeIsHttp  = false;

            foreach ($serviceTags as $tag) {
                if (strpos($tag, 'http_scheme=') === 0) {
                    $serviceScheme = substr($tag, strlen('http_scheme='));
                    $serviceSchemeIsHttp = strtolower($serviceScheme) == 'http' ? false : true;
                    break;
                }
            }

            $this->localServices[$serviceName][] = [
                "name"    => $serviceName,
                "address" => $serviceAddress,
                "port"    => $servicePort,
                "scheme"  => $serviceSchemeIsHttp
            ];
        }
    }

    /**
     * 如服務只回傳一組service的話則使用該方法
     *
     * @param array $service
     * @return void
     */
    public function setService($service): void
    {
        $serviceEntity        = $service[0]["Service"];
        $serviceTags          = $serviceEntity["Tags"];
        $serviceName          = $serviceEntity["Service"];
        $serviceAddress       = $serviceEntity["Address"];
        $servicePort          = $serviceEntity["Port"];
        $serviceSchemeIsHttp  = false;

        foreach ($serviceTags as $tag) {
            if (strpos($tag, 'http_scheme=') === 0) {
                $serviceScheme = substr($tag, strlen('http_scheme='));
                $serviceSchemeIsHttp = strtolower($serviceScheme) == 'http' ? false : true;
                break;
            }
        }

        $this->localServices[$serviceName][] = [
            "name"    => $serviceName,
            "address" => $serviceAddress,
            "port"    => $servicePort,
            "scheme"  => $serviceSchemeIsHttp
        ];
    }

    /**
     * 確認是否需要更新真實的服務列表
     * 如新一輪的服務探索取得的服務與原有不一致，則進行ServiceList更新
     * @return boolean
     */
    protected function isNeedToUpdateServiceList(): bool
    {
        if ($this->localServices !== $this->localVerifyServices) {
            $this->cleanLocalVerifyServices();
            $this->localVerifyServices = $this->localServices;
            return true;
        }
        return false;
    }

    /**
     * 重置$service屬性
     *
     * @return void
     */
    protected function cleanLocalServices(): void
    {
        $this->localServices = [];
    }

    /**
     * 重置$verifyServices屬性
     *
     * @return void
     */
    protected function cleanLocalVerifyServices(): void
    {
        $this->localVerifyServices = [null];
    }

    /**
     * 真實更新到service list的步驟
     *
     * @return callable
     */
    public function serviceDataHandler(): callable
    {
        return static function (string $serviceName) {
            if (filter_var($serviceName, FILTER_VALIDATE_URL) !== false) {
                $parseUrl = parse_url($serviceName);
                if(isset($parseUrl["port"])) {
                    $port = (int)$parseUrl["port"];
                } else {
                    $port = $parseUrl["scheme"] === "https" ? 443 : 80;
                }
                return new ServiceSettings(
                    $parseUrl["host"],
                    $parseUrl["host"],
                    $port,
                    $parseUrl["scheme"] === "https"
                );
            }

            $services = \AnserGateway\Worker\GatewayWorker::$serviceDiscovery->localServices[$serviceName];

            if (isset($services)) {
                if (count($services) > 1) {
                    $realServiceArray = LoadBalance::do($services);
                    return new ServiceSettings(
                        $realServiceArray["name"],
                        $realServiceArray["address"],
                        $realServiceArray["port"],
                        $realServiceArray["scheme"],
                    );
                } else {
                    if (count($services) === 0) {
                        // 服務不存在
                        log_message('warning', "未發現服務-{$serviceName} 於Consul進行服務探索時失效，請檢察是否於Consul註冊該服務或於Anser-Gateway設定檔(env)檢查是否設定正確。");
                        throw ServiceDiscoveryException::forServiceNotFound($serviceName);
                    }
                    // 做服務設定的步驟
                    return new ServiceSettings(
                        $services[0]["name"],
                        $services[0]["address"],
                        $services[0]["port"],
                        $services[0]["scheme"],
                    );
                }
            } else {
                return null;
            }

        };
    }
}
