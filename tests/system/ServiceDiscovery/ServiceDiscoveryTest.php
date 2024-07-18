<?php

use PHPUnit\Framework\TestCase;
use AnserGateway\ServiceDiscovery\ServiceDiscovery;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

class ServiceDiscoveryTest extends TestCase
{
    /**
     * 服務探索實體
     *
     * @var ServiceDiscovery
     */
    protected $serviceDiscoveryInstance;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceDiscoveryInstance = new ServiceDiscovery();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * construct test
     *
     * @return void
     */
    public function testConstruct()
    {
        $closure = function () {
            // fwrite(STDERR, print_r($this->serviceDiscoveryInstance, TRUE));
            assertInstanceOf("\Config\ServiceDiscovery", $this->serviceDiscoveryConfig);
            assertEquals(['Order-Service1','Product-Service1'], $this->defaultServiceGroup);
            assertEquals('5', $this->reloadTime);
            assertEquals('', $this->consulDataCenter);
            assertEquals('http://host.docker.internal:8500', $this->consulAddress);
            assertEquals('random', $this->LBStrategy);
            assertInstanceOf('\AnserGateway\ServiceDiscovery\LoadBalance\Random', \AnserGateway\ServiceDiscovery\LoadBalance\LoadBalance::$strategy);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * cleanLocalVerifyServices test
     *
     * @return void
     */
    public function testCleanLocalVerifyServices()
    {
        $closure = function () {
            // fwrite(STDERR, print_r($this->serviceDiscoveryInstance, TRUE));
            $this->cleanLocalVerifyServices();
            assertEquals([null], $this->localVerifyServices);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * cleanLocalServices test
     *
     * @return void
     */
    public function testCleanLocalServices()
    {
        $closure = function () {
            // fwrite(STDERR, print_r($this->serviceDiscoveryInstance, TRUE));
            $this->cleanLocalServices();
            assertEquals([], $this->localServices);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * setService test
     *
     * @return void
     */
    public function testSetService()
    {
        $serviceArray = [
            [
                "Service" => [
                    "Service" => "Order_Service",
                    "Address" => "localhost",
                    "Port"    => "80",
                    "Tags" => [
                        "http_scheme=http",
                        "foo",
                        "bar"
                    ]
                ]
            ]
        ];

        $verifyService = [
            "Order_Service" => [
                [
                    "name" => "Order_Service",
                    "address" => "localhost",
                    "port" => 80,
                    'scheme' => false
                ]
            ]
        ];

        $closure = function () use ($serviceArray, $verifyService) {

            $this->setService($serviceArray);
            assertEquals($verifyService, $this->localServices);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * setServices test
     *
     * @return void
     */
    public function testSetServices()
    {
        $serviceArray = [
            [
                "Service" => [
                    "Service" => "Order_Service1",
                    "Address" => "localhost",
                    "Port"    => "8081",
                    "Tags" => [
                        "http_scheme=http",
                        "foo",
                        "bar"
                    ],
                ]
            ],
            [
                "Service" => [
                    "Service" => "Order_Service2",
                    "Address" => "localhost",
                    "Port"    => "8082",
                    "Tags" => [
                        "http_scheme=https",
                        "foo",
                        "bar"
                    ],
                ]
            ]
        ];

        $verifyService = [
            "Order_Service1" => [
                    [
                        "name" => "Order_Service1",
                        "address" => "localhost",
                        "port" => '8081',
                        'scheme' => false
                    ]
                ],
            "Order_Service2" => [
                    [
                        "name" => "Order_Service2",
                        "address" => "localhost",
                        "port" => '8082',
                        'scheme' => true
                    ]
                ]
        ];

        $closure = function () use ($serviceArray, $verifyService) {

            $this->setServices($serviceArray);
            assertEquals($verifyService, $this->localServices);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * doFoundServices test success
     * need open consul
     *
     * @return void
     */
    public function testDoFoundServicesSuccess()
    {
        $closure = function () {
            $this->defaultServiceGroup = ['Order_Service1'];
            $result = $this->doFoundServices();
            assertNotNull($result);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * doFoundServices test ConnectError
     *
     * @return void
     */
    public function testDoFoundServicesConnectError()
    {
        $closure = function () {
            $this->defaultServiceGroup = ['Order_Service1'];
            $this->consulAddress = 'http://example.com:8600';
            $result = $this->doFoundServices();
            assertNotNull($result);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * doFoundServices test null
     *
     * @return void
     */
    public function testDoServiceDiscoveryAssertNull()
    {
        $closure = function () {
            // fwrite(STDERR, print_r($this->serviceDiscoveryInstance, TRUE));
            $this->localServices = [];
            $this->localVerifyServices = [];
            $result = $this->doServiceDiscovery();
            assertNull($result);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * setLocalServices test
     *
     * @return void
     */
    public function testSetLocalServices()
    {

        $closure = function () {
            // fwrite(STDERR, print_r($this->serviceDiscoveryInstance, TRUE));
            $this->defaultServiceGroup = ['Order-Service1','Product-Service1'];
            $this->setLocalServices();
            assertNotNull($this->localServices);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * setLocalServices test Throw Exception
     *
     * @return void
     */
    public function testSetLocalServicesThrowException()
    {
        $phpUnit = $this;
        $closure = function () use ($phpUnit) {
            $this->defaultServiceGroup = ['notExist_service'];
            $phpUnit->expectException(\AnserGateway\ServiceDiscovery\Exception\ServiceDiscoveryException::class);
            $phpUnit->expectExceptionMessage("服務 - notExist_service 並未被搜尋成功，請確認是否已成功註冊。");
            $this->setLocalServices();
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * isNeedToUpdateServiceList test true
     *
     * @return void
     */
    public function testIsNeedToUpdateServiceListAssertTrue()
    {
        $closure = function () {
            $this->localServices  = [];
            $this->localVerifyServices  = [null];
            $result = $this->isNeedToUpdateServiceList();
            assertTrue($result);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * isNeedToUpdateServiceList test false
     *
     * @return void
     */
    public function testIsNeedToUpdateServiceListAssertFalse()
    {
        $closure = function () {
            $this->localServices  = [];
            $this->localVerifyServices  = [];
            $result = $this->isNeedToUpdateServiceList();
            assertFalse($result);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * doServiceDiscovery test
     *
     * @return void
     */
    public function testDoServiceDiscovery()
    {
        $closure = function () {
            $this->localServices  = [];
            $this->localVerifyServices  = [null];
            $this->doServiceDiscovery();
            assertNotNull($this->localServices);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }

    /**
     * serviceDataHandler test
     *
     * @return void
     */
    public function testServiceDataHandlerAssertCallable()
    {
        $result = $this->serviceDiscoveryInstance->serviceDataHandler();
        assertInstanceOf('Closure', $result);
    }

    /**
     * serviceDataHandler test
     *
     * @return void
     */
    public function testServiceDataHandlerHttpAddress()
    {
        $result = call_user_func($this->serviceDiscoveryInstance->serviceDataHandler(), 'https://www.google.com:80/');
        assertInstanceOf('\SDPMlab\Anser\Service\ServiceSettings', $result);
        $result = call_user_func($this->serviceDiscoveryInstance->serviceDataHandler(), 'http://www.google.com/');
        assertInstanceOf('\SDPMlab\Anser\Service\ServiceSettings', $result);
    }

    /**
     * serviceDataHandler test
     *
     * @return void
     */
    public function testServiceDataHandlerAssertNull()
    {
        $result = call_user_func($this->serviceDiscoveryInstance->serviceDataHandler(), 'Order_Service');
        assertNull($result);
    }

    /**
     * serviceDataHandler test
     *
     * @return void
     */
    public function testServiceDataHandlerAssertService()
    {
        \AnserGateway\Worker\GatewayWorker::$serviceDiscovery = $this->serviceDiscoveryInstance;
        \AnserGateway\Worker\GatewayWorker::$serviceDiscovery->localServices['Order_Service1'] = [
            [
                "name" => "Order_Service",
                "address" => "localhost",
                "port" => 80,
                'scheme' => false
            ]
        ];
        $result = call_user_func($this->serviceDiscoveryInstance->serviceDataHandler(), 'Order_Service1');
        assertInstanceOf('\SDPMlab\Anser\Service\ServiceSettings', $result);
        assertEquals(\AnserGateway\Worker\GatewayWorker::$serviceDiscovery->localServices['Order_Service1'][0]['name'], $result->name);
        assertEquals(\AnserGateway\Worker\GatewayWorker::$serviceDiscovery->localServices['Order_Service1'][0]['address'], $result->address);
        assertEquals(\AnserGateway\Worker\GatewayWorker::$serviceDiscovery->localServices['Order_Service1'][0]['port'], $result->port);
        assertEquals(\AnserGateway\Worker\GatewayWorker::$serviceDiscovery->localServices['Order_Service1'][0]['scheme'], $result->scheme);
    }

    /**
     * serviceDataHandler test
     *
     * @return void
     */
    public function testServiceDataHandlerAssertServices()
    {
        \AnserGateway\Worker\GatewayWorker::$serviceDiscovery = $this->serviceDiscoveryInstance;
        \AnserGateway\Worker\GatewayWorker::$serviceDiscovery->localServices['Order_Service1'] = [
            [
                "name" => "Order_Service",
                "address" => "localhost",
                "port" => 80,
                'scheme' => false
            ],
            [
                "name" => "Order_Service1",
                "address" => "localhost",
                "port" => 8081,
                'scheme' => false
            ],
        ];
        $result = call_user_func($this->serviceDiscoveryInstance->serviceDataHandler(), 'Order_Service1');
        assertInstanceOf('\SDPMlab\Anser\Service\ServiceSettings', $result);
    }

    /**
     * serviceDataHandler test
     *
     * @return void
     */
    public function testServiceDataHandlerThrowException()
    {
        \AnserGateway\Worker\GatewayWorker::$serviceDiscovery = $this->serviceDiscoveryInstance;
        \AnserGateway\Worker\GatewayWorker::$serviceDiscovery->localServices['Order_Service1'] = [];
        $this->expectException(\AnserGateway\ServiceDiscovery\Exception\ServiceDiscoveryException::class);
        $this->expectExceptionMessage("服務 - Order_Service1 並未被搜尋成功，請確認是否已成功註冊。");
        $result = call_user_func($this->serviceDiscoveryInstance->serviceDataHandler(), 'Order_Service1');
    }

    /**
     * registerSelf test
     *
     * @return void
     */
    public function testRegisterSelf()
    {
        $closure = function () {
            $this->consulAddress = 'http://host.docker.internal:8500';
            $res = $this->registerSelf('http', 8080);
            assertTrue($res);
        };
        $binding = $closure->bindTo($this->serviceDiscoveryInstance, get_class($this->serviceDiscoveryInstance));
        $binding();
    }
}
