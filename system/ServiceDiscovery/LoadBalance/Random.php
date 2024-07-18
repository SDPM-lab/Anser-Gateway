<?php 
namespace AnserGateway\ServiceDiscovery\LoadBalance;

class Random
{
    /**
     * 隨機選出一個服務
     *
     * @param array $services
     * @return array
     */
    public function do(array $services): array
    {
        $serviceCount = count($services);
        $rand         = rand(0,$serviceCount-1); 

        return $services[$rand];
    }
}


?>