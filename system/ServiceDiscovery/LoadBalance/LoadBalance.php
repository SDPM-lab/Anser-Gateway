<?php 
namespace AnserGateway\ServiceDiscovery\LoadBalance;
use AnserGateway\ServiceDiscovery\Exception\LoadBalanceException;

class LoadBalance
{
    public static $strategyMap = [
        'random' => \AnserGateway\ServiceDiscovery\LoadBalance\Random::class
    ];

    /**
     * 選定的負載策略
     *
     * @var object
     */
    public static $strategy; 

    /**
     * 設定負載策略
     *
     * @param string $strategy
     * @return void
     */
    public static function setStrategy($strategy)
    {
        if (!isset(static::$strategyMap[$strategy])) {
            throw LoadBalanceException::forStrategyNotFound($strategy);
        }
        
        static::$strategy = new static::$strategyMap[$strategy];
    }


    /**
     * 執行負載策略
     *
     * @return array
     */
    public static function do(array $services)
    {
       return static::$strategy->do($services);
    }

}


?>