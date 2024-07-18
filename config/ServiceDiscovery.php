<?php

namespace Config;

use AnserGateway\Config\BaseConfig;

class ServiceDiscovery extends BaseConfig
{
    /**
     * 需要被探索的服務名稱
     *
     * @var array<string>
     */
    public array $defaultServiceGroup = [];

    /**
     * Consul Server IP Address and port
     *
     * @var string
     */
    public string $address = 'http://localhost:8500';

    /**
     * HTTP Scheme [http or https]
     *
     * @var string
     */
    public string $scheme = 'http';

    /**
     * Consul Server DataCenter
     *
     * @var string
     */
    public string $dataCenter = '';

    /**
     * 請求間隔
     *
     * @var integer
     */
    public int $reloadTime = 10;

    /**
     * 服務負載均衡演算法
     *
     * @var string
     */
    public string $LBStrategy = 'random';

    public function __construct()
    {
        parent::__construct();

        // 因.env傳入為字串，故使用explode作切割
        if(getenv('servicediscovery.defaultServiceGroup') !== ''){
            $this->defaultServiceGroup = explode(',', getenv('servicediscovery.defaultServiceGroup'));
        }
    }
}
