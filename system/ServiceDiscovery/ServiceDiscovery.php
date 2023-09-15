<?php

namespace AnserGateway\ServiceDiscovery;

use Config\ServiceDiscovery as ServiceDiscoveryConfig;
use SDPMlab\Anser\Service\Action;
use Psr\Http\Message\ResponseInterface;
use SDPMlab\Anser\Service\ConcurrentAction;
use SDPMlab\Anser\Exception\ActionException;

class ServiceDiscovery
{
    /**
     * ServiceDiscovery 設定檔實體
     *
     * @var \Config\ServiceDiscovery
     */
    protected $serviceDiscoveryConfig;

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
     * 從Consul Server探索的可訪問服務
     *
     * @var array<string,array<string,string>>
     */
    protected array $localServices = [];

    /**
     * 用於比對的可訪問服務，參照$service屬性
     *
     * @var array<string,array<string,string>>
     */
    protected array $localVerifyServices = [null];


    public function __construct()
    {
        $this->serviceDiscoveryConfig = new ServiceDiscoveryConfig();
        $this->defaultServiceGroup    = $this->serviceDiscoveryConfig->defaultServiceGroup;
        $this->reloadTime             = $this->serviceDiscoveryConfig->reloadTime;
        $this->consulDataCenter       = $this->serviceDiscoveryConfig->dataCenter;
        $this->consulAddress          = sprintf(
            '%s://%s',
            strtolower($this->serviceDiscoveryConfig->scheme),
            $this->serviceDiscoveryConfig->address,
        );
    }

    /**
     * 執行服務探索步驟
     * 於ServiceDiscoverWorker被呼叫
     *
     * @return void|null
     */
    public function doServiceDiscovery()
    {
        $this->setLocalServices();

        if(!$this->isNeedToUpdateServiceList()) {
            return;
        }

        var_dump("update Service");
        var_dump("---------");
        // do update anser-action service list
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
    public function setLocalServices()
    {
        $this->cleanLocalServices();
        $servicesData = $this->doFoundServices();
        foreach ($servicesData as $serviceName => $serviceData) {
            if(count($servicesData[$serviceName]) > 1) {
                $this->setServices($serviceData);
            } else {
                if(count($serviceData) == 0) {
                    log_message('warning', "未發現服務-{$serviceName} 於Consul進行服務探索時失效，請檢察是否於Consul註冊該服務或於Anser-Gateway設定檔(env)檢查是否設定正確。");
                    continue;
                }
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
                $serviceName,
                $serviceAddress,
                $servicePort,
                $serviceSchemeIsHttp
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
            $serviceName,
            $serviceAddress,
            $servicePort,
            $serviceSchemeIsHttp
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


}
