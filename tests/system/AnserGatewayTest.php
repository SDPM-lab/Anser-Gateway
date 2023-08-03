<?php
use Config\Path;
use AnserGateway\AnserGateway;
use PHPUnit\Framework\TestCase;
use AnserGateway\Router\Router;
use AnserGateway\Filters\Filters;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use AnserGateway\Router\RouteCollector;
use Test\Support\Filters\Filters as TestFilters;
use AnserGateway\Router\Exception\RouteException;

/**
 * test usage
 */
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertSame;

class AnserGatewayTest extends TestCase
{
    protected $gateway;
    protected function setUp(): void
    {
        parent::setUp();

        $path = new Path();

        # Do get routeCollector and new a Router class
        $routeList = RouteCollector::loadRoutes($path->testDirectory . '/_support/Routes/Routes.php');
        $router    = new Router($routeList);

        # Injection Router class to AnserGateway
        $this->gateway = new AnserGateway($router);

        # show test value
        // fwrite(STDERR, print_r($response, TRUE));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->gateway = null;

        RouteCollector::resetDiscover();
    }

    /**
     * 測試建構方法是否有建立對應屬性
     *
     * @return void
     */
    public function testConstruct()
    {
        $phpUnit = $this;
        $closure = function () use ($phpUnit) {
            $phpUnit->assertNotEmpty($this->router);
            $phpUnit->assertNotEmpty($this->response);
        };
        $binding = $closure->bindTo($this->gateway, get_class($this->gateway));
        $binding();
    }

    /**
     * tryToRouteIt
     *
     * @return void
     */
    public function testTryToRouteIt()
    {
        $uri1        = "/test";
        $httpMethod1 = "GET";
        $filters1    =  $this->gateway->tryToRouteIt($httpMethod1, $uri1);

        assertSame("test2:dual,noreturn", $filters1["route"]);
    }

    /**
     * tryToRouteIt 具備階層關係的路由測試
     *
     * @return void
     */
    public function testTryToRouteItWithGroup()
    {
        $uri        = "/api/testTwo";
        $httpMethod = "POST";
        $filters    = $this->gateway->tryToRouteIt($httpMethod, $uri);

        assertSame("test2", $filters["route"]);
        assertSame("test", $filters["group"]);
    }

    /**
     * startController Closure執行測試
     *
     * @return void
     */
    public function testStartControllerWithRunClosure()
    {
        $uri1        = "/closure/testText";
        $httpMethod1 = "GET";

        $closure = function () use ($uri1, $httpMethod1) {
            $this->tryToRouteIt($httpMethod1, $uri1);
            $closureResult =  $this->startController();
            assertNotEmpty($closureResult["name"]);
            assertNotNull($closureResult["name"]);
            assertEquals("testText", $closureResult["name"]);
        };
        $binding = $closure->bindTo($this->gateway, get_class($this->gateway));
        $binding();
    }

    /**
     * startController 無指定controller 拋出 Exception
     *
     * @return void
     */
    public function testStartControllerWithUnKnownController()
    {
        $uri1        = "/closure/testText";
        $httpMethod1 = "GET";
        $phpUnit     = $this;

        $closure = function () use ($phpUnit, $uri1, $httpMethod1) {
            $this->tryToRouteIt($httpMethod1, $uri1);
            $this->controller = null;

            $phpUnit->expectException(RouteException::class);
            $phpUnit->expectExceptionMessage("找無指定的Controller");
            $this->startController();

        };
        $binding = $closure->bindTo($this->gateway, get_class($this->gateway));
        $binding();
    }

    /**
     * startController 嘗試使用autoload找尋該controller後失敗 拋出例外
     *
     * @return void
     */
    public function testStartControllerWithUnExistController()
    {
        $uri1        = "/closure/testText";
        $httpMethod1 = "GET";
        $phpUnit     = $this;

        $closure = function () use ($phpUnit, $uri1, $httpMethod1) {
            $this->tryToRouteIt($httpMethod1, $uri1);
            $this->controller = "Test\Support\Controllers\NotExistController";
            $phpUnit->expectException(RouteException::class);
            $phpUnit->expectExceptionMessage("Controller - {$this->controller} 不存在，請確認是否已定義名稱為 {$this->controller} 的Controller");
            $this->startController();
        };
        $binding = $closure->bindTo($this->gateway, get_class($this->gateway));
        $binding();
    }

    /**
     * startController
     * 該controller並未繼承 BaseController 則視為非\AnserGateway\Controller類型，拋出Exception
     *
     * @return void
     */
    public function testStartControllerWithUnExtendsBaseController()
    {
        $uri1        = "/invalid";
        $httpMethod1 = "GET";
        $phpUnit     = $this;
        
        $closure = function () use ($phpUnit, $uri1, $httpMethod1) {
            $this->tryToRouteIt($httpMethod1, $uri1);
            $phpUnit->expectException(RouteException::class);
            $phpUnit->expectExceptionMessage("Controller - {$this->controller} 尚未繼承 AnserGateway\Config\BaseController類別");
            $this->startController();

        };
        $binding = $closure->bindTo($this->gateway, get_class($this->gateway));
        $binding();
    }

    /**
     * startController
     * 該controller內找不到對應method，拋出Exception
     *
     * @return void
     */
    public function testStartControllerWithControllerMethodNotFound()
    {
        $uri1        = "/unknownMethod";
        $httpMethod1 = "GET";
        $phpUnit     = $this;

        $closure = function () use ($phpUnit, $uri1, $httpMethod1) {
            $this->tryToRouteIt($httpMethod1, $uri1);
            $this->method = "unknownMethod";
            $phpUnit->expectException(RouteException::class);
            $phpUnit->expectExceptionMessage("Controller - {$this->controller} 中不存在名稱為 - {$this->method} 的方法");
            $this->startController();

        };
        $binding = $closure->bindTo($this->gateway, get_class($this->gateway));
        $binding();
    }

    /**
     * createController
     * 實例化 Controller測試
     *
     * @return void
     */
    public function testCreateController()
    {
        $uri1        = "/test";
        $httpMethod1 = "GET";

        $closure = function () use ($uri1, $httpMethod1) {
            $this->request = new Request('');
            $this->tryToRouteIt($httpMethod1, $uri1);
            $this->startController();
            $controllerInstance = $this->createController();

            assertInstanceOf("App\Controllers\BaseController", $controllerInstance);
            assertInstanceOf("Test\Support\Controllers\TestController", $controllerInstance);
        };
        $binding = $closure->bindTo($this->gateway, get_class($this->gateway));
        $binding();
    }

    /**
     * runController
     * 測試 Controller 運行結果
     *
     * @return void
     */
    public function testRunController()
    {
        $uri1        = "/test";
        $httpMethod1 = "GET";

        $closure = function () use ($uri1, $httpMethod1) {
            $this->request = new Request('');
            $this->tryToRouteIt($httpMethod1, $uri1);
            $this->startController();
            $controllerInstance = $this->createController();
            $response = $this->runController($controllerInstance);
            $decodeBody = json_decode($response->rawBody());

            assertInstanceOf("Workerman\Protocols\Http\Response", $response);
            assertEquals("200", $response->getStatusCode());
            assertEquals("200", $decodeBody->status);
            assertEquals("index method", $decodeBody->msg);

        };
        $binding = $closure->bindTo($this->gateway, get_class($this->gateway));
        $binding();
    }

    /**
     * handleRequest 成功響應測試
     *
     * @return void
     */
    public function testHandleRequestSuccess()
    {
        $oldRequest = new Request('');

        $request = function () {
            $this->_data["method"] = "GET";
            $this->_data["uri"]    = "/test";
        };
        $bindingRequest = $request->bindTo($oldRequest, get_class($oldRequest));
        $bindingRequest();

        $closure = function () use ($oldRequest) {
            $response   =  $this->handleRequest($oldRequest);
            $decodeBody = json_decode($response->rawBody());

            assertInstanceOf("Workerman\Protocols\Http\Response", $response);
            assertEquals("200", $response->getStatusCode());
            assertEquals("200", $decodeBody->status);
            assertEquals("index method", $decodeBody->msg);
        };
        $binding = $closure->bindTo($this->gateway, get_class($this->gateway));
        $binding();
    }

    /**
     * handleRequest 直接響應Response
     *
     * @return void
     */
    public function testHandleReturnResponse()
    {
        $oldRequest = new Request('');

        $request = function () {
            $this->_data["method"] = "GET";
            $this->_data["uri"]    = "/letResponse";
        };
        $bindingRequest = $request->bindTo($oldRequest, get_class($oldRequest));
        $bindingRequest();


        $response = new Response();
        $filter   = new Filters($oldRequest, $response);

        // 替換filter config為測試Filter
        $filters = function () {
            $this->config = null;
            $this->config = new TestFilters();
        };
        $bindingFilter = $filters->bindTo($filter, get_class($filter));
        $bindingFilter();

        $closure = function () use ($filter) {
            $httpMethod  = "GET";
            $uri         = "/letResponse";

            $routeFilter = $this->tryToRouteIt($httpMethod, $uri);
            $filter->enableFilters($routeFilter, 'before');
            $filter->enableFilters($routeFilter, 'after');
            
            $possibleResponse = $filter->run($uri, 'before');
            $decodeBody = json_decode($possibleResponse->rawBody());

            assertInstanceOf("Workerman\Protocols\Http\Response", $possibleResponse);
            assertEquals("200", $possibleResponse->getStatusCode());
            assertEquals("200", $decodeBody->code);
            assertEquals("success", $decodeBody->msg);
        };
        $binding = $closure->bindTo($this->gateway, get_class($this->gateway));
        $binding();
    }
}
