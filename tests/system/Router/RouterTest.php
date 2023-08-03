<?php
use Config\Path;
use AnserGateway\Router\Router;
use PHPUnit\Framework\TestCase;
use AnserGateway\Router\RouteCollector;
use AnserGateway\Router\Exception\RouteException;

/**
 * test usage
 */
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

class RouterTest extends TestCase
{
    /**
     *
     * @var RouteCollector
     */
    protected $routeList;

    /**
     * Router instance
     *
     * @var Router
     */
    protected $router;

    /**
     * route列表
     *
     * @var
     */
    protected $routeFilePath;

    /**
     * 路由驗證器
     *
     * @var
     */
    protected $dispatcher;

    /**
     * Path
     *
     * @var Path
     */
    protected $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = new Path();

        # Do get routeCollector and define route file
        $this->routeList        = RouteCollector::loadRoutes($this->path->testDirectory . '/_support/Routes/Routes.php');
        $this->router           = new Router($this->routeList);
        $this->routeFilePath    = $this->path->testDirectory . '/_support/Routes/Routes.php';
        $this->dispatcher       = $this->router->dispatcher($this->routeList);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->path = null;

        # Do get routeCollector and define route file
        $this->routeList        = null;
        $this->router           = null;
        $this->routeFilePath    = null;
        $this->dispatcher       = null;

        # reset the routeCollect
        RouteCollector::resetDiscover();
    }


    /**
     * 當路由不存在時的例外測試
     *
     * @return void
     */
    public function testHandleExceptionForRouteNotExist()
    {
        $uri        = "NotExist";
        $httpMethod = "GET";

        $this->expectException(RouteException::class);
        $this->router->handle($httpMethod, $uri);
    }

    /**
     * 該路由未定義相對的HTTP請求方法時的例外測試
     * (不存在 httpMethod => "DELETE" , uri => "/test")
     *
     * @return void
     */
    public function testHandleExceptionForMethodNotAllowed()
    {
        $uri        = "/test";
        $httpMethod = "DELETE";

        $this->expectException(RouteException::class);
        $this->router->handle($httpMethod, $uri);
    }

    /**
     * 在路由中定義不存在的controller class 並請求它
     *
     * @return void
     */
    public function testHandleExceptionForControllerNotExist()
    {
        $uri        = "/unExistController";
        $httpMethod = "GET";

        $this->expectException(RouteException::class);
        $this->expectExceptionMessage("Controller -  不存在，請確認是否已定義名稱為  的Controller");
        $this->router->handle($httpMethod, $uri);

    }

    /**
     * 測試 handle method
     *
     * @return void
     */
    public function testHandleSuccess()
    {
        $uri            = "/test";
        $httpMethod     = "GET";

        $controllerName = $this->router->handle($httpMethod, $uri);
        assertEquals('Test\Support\Controllers\TestController', $controllerName);
    }

    /**
     * 測試route的適配器
     *
     * @return void
     */
    public function testDispatcher()
    {
        $dispatcher = $this->router->dispatcher($this->routeList);

        assertInstanceOf('FastRoute\Dispatcher', $dispatcher);
    }

    /**
     * 測試checkRoute method，驗證由 dispatcher 回傳的routeInfo是否符合期待
     *
     * @return void
     */
    public function testCheckRouteSuccess()
    {
        $uri        = "/test/testParam";
        $httpMethod = "GET";
        $routeInfo  = $this->dispatcher->dispatch($httpMethod, $uri);
        $handler    = $routeInfo[1]['handler'];  // controller or closure
        $options    = $routeInfo[1]['options'];  // route 本身的附加選項
        $group      = $routeInfo[1]['group'];    // route 依附的group附加選項
        $params     = $routeInfo[2];             // route 本身附加的參數

        $closure = function () use ($routeInfo) {
            $result = $this->checkRoute($routeInfo);
            assertTrue($result);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();

        // assert the controller namespace and method
        assertEquals("Test\Support\Controllers\TestController", $handler[0]);
        assertEquals("index", $handler[1]);

        // assert the options e.g. filters
        assertNotNull($options);
        assertNotNull($options["filter"]);
        assertEquals($options["filter"], "test2:dual,noreturn");

        // assert the options e.g. filters
        assertEmpty($group);

        // assert the route params e.g. /test/{name}
        assertNotNull($params);
        assertNotNull($params["name"]);
        assertEquals($params["name"], "testParam");
    }

    /**
     * 測試checkRoute method，驗證由 dispatcher 回傳的routeInfo是否符合期待
     * 路由執行closure
     *
     * @return void
     */
    public function testCheckRouteByClosureSuccess()
    {
        $uri        = "/closure/testParam";
        $httpMethod = "GET";
        $routeInfo  = $this->dispatcher->dispatch($httpMethod, $uri);
        $handler    = $routeInfo[1]['handler'];  // controller or closure
        $options    = $routeInfo[1]['options'];  // route 本身的附加選項
        $group      = $routeInfo[1]['group'];    // route 依附的group附加選項
        $params     = $routeInfo[2];             // route 本身附加的參數

        $closure = function () use ($routeInfo) {
            $result = $this->checkRoute($routeInfo);
            assertTrue($result);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();

        // assert the controller is closure
        assertInstanceOf(Closure::class, $handler);

        // assert the options e.g. filters
        assertNotNull($options);
        assertNotNull($options["filter"]);
        assertEquals($options["filter"], "test2:dual,noreturn");

        // assert the options e.g. filters
        assertEmpty($group);

        // assert the route params e.g. /closure/{name}
        assertNotNull($params);
        assertNotNull($params["name"]);
        assertEquals($params["name"], "testParam");
    }

    /**
     * 測試checkRoute method，驗證由 dispatcher 回傳的routeInfo是否符合期待
     * (具有group的多層route)
     * @return void
     */
    public function testCheckRouteByGroupSuccess()
    {
        $uri        = "/api/test/testParam/testParam2";
        $httpMethod = "GET";
        $routeInfo  = $this->dispatcher->dispatch($httpMethod, $uri);
        $handler    = $routeInfo[1]['handler'];  // controller or closure
        $options    = $routeInfo[1]['options'];  // route 本身的附加選項
        $group      = $routeInfo[1]['group'];    // route 依附的group附加選項
        $params     = $routeInfo[2];             // route 本身附加的參數

        $closure = function () use ($routeInfo) {
            $result = $this->checkRoute($routeInfo);
            assertTrue($result);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();

        // assert the controller namespace and method
        assertEquals("Test\Support\Controllers\TestController", $handler[0]);
        assertEquals("index", $handler[1]);

        // assert the options e.g. filters
        assertNotNull($options);
        assertNotNull($options["filter"]);
        assertEquals($options["filter"], "test2");

        // assert the group filters
        assertNotNull($group);
        assertNotNull($group["filter"]);
        assertEquals($group["filter"], "test");

        // assert the route params e.g. /api/test/{name}
        assertNotNull($params);
        assertNotNull($params["name"]);
        assertEquals($params["name"], "testParam");
        assertEquals($params["name2"], "testParam2");
    }

    /**
     * 測試checkRoute route命名例外
     * @runInSeparateProcess
     * @return void
     */
    public function testCheckRouteException()
    {
        $uri        = "/routeSlashError";
        $httpMethod = "GET";
        $routeInfo  = $this->dispatcher->dispatch($httpMethod, $uri);
        $phpUnit    = $this;

        $closure = function () use ($phpUnit, $routeInfo) {
            $phpUnit->expectException(RouteException::class);
            $phpUnit->expectExceptionMessage("{$routeInfo[1]['handler'][0]} Namespace分隔符號為反斜線(\)，非斜線(/)");
            $this->checkRoute($routeInfo);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }


    /**
     * 測試 setRequest method
     *
     * @return void
     */
    public function testSetRequest()
    {
        $testController = "Test\Support\Controllers\TestController";
        $testMethod     = "index";
        $testParams     = array("name" => "testParam");

        $closure = function () use ($testController, $testMethod, $testParams) {
            $this->setRequest($testController, $testMethod, $testParams);

            assertEquals($testController, $this->controller);
            assertEquals($testMethod, $this->method);
            assertEquals($testParams, $this->params);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }

    /**
     * 測試 scanHandler method
     *
     * @return void
     */
    public function testScanHandlerByTestControllerSuccess()
    {
        $testController = "Test\Support\Controllers\TestController";

        $closure = function () use ($testController) {
            $result = $this->scanHandler($testController);
            assertTrue($result);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();


    }

    public function testScanHandlerByTestControllerException()
    {
        $testController = "Test\Support\Controllers\NotExistController";

        $phpUnit = $this;
        $closure = function () use ($phpUnit, $testController) {
            $phpUnit->expectException(RouteException::class);
            $phpUnit->expectExceptionMessage("Controller - Test\Support\Controllers\NotExistController 檔案不存在，請確認是否已定義檔案");
            $this->scanHandler($testController);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }

    /**
     * 測試 scanHandler method 成功
     *
     * @return void
     */
    public function testScanHandlerByAppControllerSuccess()
    {
        $testController = "App\Controllers\TestController";

        $closure = function () use ($testController) {
            $result =  $this->scanHandler($testController);
            assertTrue($result);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }

    /**
     * 測試 scanHandler method 例外拋出
     *
     * @return void
     */
    public function testScanHandlerByAppControllerException()
    {
        $testController = "App\Controllers\NotExistController";

        $phpUnit = $this;
        $closure = function () use ($phpUnit, $testController) {
            $phpUnit->expectException(RouteException::class);
            $phpUnit->expectExceptionMessage("Controller - App\Controllers\NotExistController 檔案不存在，請確認是否已定義檔案");
            $this->scanHandler($testController);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }

    /**
     * 測試 setFilter method， route對應的filter屬性儲存
     *
     * @return void
     */
    public function testSetFilter()
    {
        $options = [
            "filter" => 'optionFilter'
        ];

        $group = [
            "filter" => 'groupFilter'
        ];

        $closure = function () use ($options, $group) {
            $this->setFilter($options, $group);

            assertNotNull($this->filters["group"]);
            assertNotNull($this->filters["route"]);

            assertEquals("groupFilter", $this->filters["group"]);
            assertEquals("optionFilter", $this->filters["route"]);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }

    /**
     * 測試 resetFilter method
     *
     * @return void
     */
    public function testResetFilter()
    {
        $options = [
            "filter" => 'optionFilter'
        ];

        $group = [
            "filter" => 'groupFilter'
        ];

        $closure = function () use ($options, $group) {
            $this->setFilter($options, $group);
            $this->resetFilters($options, $group);

            assertSame([], $this->filters);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }

    /**
     * 測試 getController
     *
     * @return void
     */
    public function testGetController()
    {
        $testController = "Test\Support\Controllers\TestController";
        $testMethod     = "index";
        $testParams     = array("name" => "testParam");
        $router         = $this->router;

        $closure = function () use ($router, $testController, $testMethod, $testParams) {
            $this->setRequest($testController, $testMethod, $testParams);
            $controller = $router->getController();

            assertEquals($testController, $controller);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }

    /**
     * 測試 getMethod
     *
     * @return void
     */
    public function testGetMethod()
    {
        $testController = "Test\Support\Controllers\TestController";
        $testMethod     = "index";
        $testParams     = array("name" => "testParam");
        $router         = $this->router;

        $closure = function () use ($router, $testController, $testMethod, $testParams) {
            $this->setRequest($testController, $testMethod, $testParams);
            $method = $router->getMethod();

            assertEquals($testMethod, $method);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }

    /**
     * 測試 getParams
     *
     * @return void
     */
    public function testGetParams()
    {
        $testController = "Test\Support\Controllers\TestController";
        $testMethod     = "index";
        $testParams     = array("name" => "testParam");
        $router         = $this->router;

        $closure = function () use ($router, $testController, $testMethod, $testParams) {
            $this->setRequest($testController, $testMethod, $testParams);
            $params = $router->getParams();

            assertEquals($testParams, $params);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }

    /**
     * 測試 getFilter
     *
     * @return void
     */
    public function testGetFilter()
    {
        $uri        = "/test/testParam";
        $httpMethod = "GET";
        $routeInfo  = $this->dispatcher->dispatch($httpMethod, $uri);
        $router     = $this->router;

        $closure = function () use ($router, $routeInfo) {
            $this->checkRoute($routeInfo);
            $filters = $router->getFilters();
            
            assertNotEmpty($filters["route"]);
            assertNotNull($filters["route"]);
            assertEquals("test2:dual,noreturn", $filters["route"]);
        };
        $binding = $closure->bindTo($this->router, get_class($this->router));
        $binding();
    }
}
