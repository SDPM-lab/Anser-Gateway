<?php

namespace Config;

use AnserGateway\Config\BaseConfig;

class GatewayRegister extends BaseConfig
{
    /**
     * service 唯一名稱
     *
     * @var string
     */
    public string $id = 'AnserGateway1';

    /**
     * service 群組名稱
     *
     * @var string
     */
    public string $name = 'AnserGateway';

    /**
     * service 標記
     *
     * @var array
     */
    public array $tags = [];

    /**
     * gateway address
     *
     * @var string
     */
    public string $address = 'localhost';

    /**
     * consul 進行 服務健康檢查的時間間隔
     *
     * @var string
     */
    public string $interval = '60s';

    /**
     * consul 進行 服務健康檢查的timeout時間
     *
     * @var string
     */
    public string $timeout  = '5s';

    /**
     * 服務健康檢查路由
     *
     * @var string
     */
    public string $healthRoute = 'heartBeat';

    public function __construct()
    {
        parent::__construct();

        // 因.env傳入為字串，故使用explode作切割
        if(getenv('gatewayregister.tags') !== '') {
            $this->tags = explode(',', getenv('gatewayregister.tags'));
        }
    }
}
