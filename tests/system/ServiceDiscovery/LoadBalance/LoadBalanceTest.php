<?php

use PHPUnit\Framework\TestCase;
use AnserGateway\ServiceDiscovery\LoadBalance\LoadBalance;
use AnserGateway\ServiceDiscovery\LoadBalance\Random;
use AnserGateway\ServiceDiscovery\Exception\LoadBalanceException;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsArray;

class LoadBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
    /**
     * setStrategy test
     *
     * @return void
     */
    public function testSetStrategy()
    {
        LoadBalance::setStrategy('random');
        assertInstanceOf('AnserGateway\ServiceDiscovery\LoadBalance\Random', LoadBalance::$strategy);
    }

    public function testSetStrategyThrowException()
    {
        $this->expectException(LoadBalanceException::class);
        $this->expectExceptionMessage("NotExistStrategy 負載策略不存在，請確認設定的策略是否存在。");
        LoadBalance::setStrategy('NotExistStrategy');
    }

    /**
     * do test
     *
     * @return void
     */
    public function testDoWithRandomServices()
    {
        $services = [
            [
                "name" => "Order_Service",
                "address" => "localhost",
                "port" => 80,
                'scheme' => false
            ],
            [
                "name" => "Order_Service1",
                "address" => "localhost",
                "port" => 80,
                'scheme' => false
            ]
        ];
        LoadBalance::setStrategy('random');
        $result = LoadBalance::do($services);
        assertIsArray($result);
    }

    /**
     * do test
     *
     * @return void
     */
    public function testDoWithRandomService()
    {
        $services = [
            [
                "name" => "Order_Service",
                "address" => "localhost",
                "port" => 80,
                'scheme' => false
            ]
        ];
        LoadBalance::setStrategy('random');
        $result = LoadBalance::do($services);
        assertEquals($services[0], $result);
    }
}
