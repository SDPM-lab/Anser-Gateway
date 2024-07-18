<?php

use Config\Path;
use AnserGateway\AnserGateway;
use PHPUnit\Framework\TestCase;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use AnserGateway\Filters\Filters;


use AnserGateway\Router\RouteCollector;
use AnserGateway\Router\Router;

use Test\Support\Filters\Filters as TestFilters;
use AnserGateway\Filters\Exception\FilterException;

use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

class FilterTest extends TestCase
{
    protected $filter;

    protected $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $request  = new Request('');
        $response = new Response();
        $this->filter = new Filters($request, $response);

        // 替換filter config為測試Filter
        $filter = function () {
            $this->config = null;
            $this->config = new TestFilters();
        };
        $binding = $filter->bindTo($this->filter, get_class($this->filter));
        $binding();

        $path = new Path();

        # Do get routeCollector and new a Router class
        $routeList = RouteCollector::loadRoutes($path->testDirectory . '/_support/Routes/Routes.php');
        $router    = new Router($routeList);

        # Injection Router class to AnserGateway
        $this->gateway = new AnserGateway($router);
    }
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->filter = null;
        RouteCollector::resetDiscover();
    }

    public function testNewFilter()
    {
        $request  = new Request('');
        $response = new Response();
        $filter   = new Filters($request, $response);
        assertInstanceOf("AnserGateway\Filters\Filters", $filter);
    }

    /**
     * 測試Filter 建構方法
     *
     * @return void
     */
    public function testConstruct()
    {
        $closure = function () {
            assertNotNull($this->request);
            assertNotNull($this->response);
            assertNotNull($this->config);
            assertInstanceOf("Workerman\Protocols\Http\Request", $this->request);
            assertInstanceOf("Workerman\Protocols\Http\Response", $this->response);
            assertInstanceOf("Test\Support\Filters\Filters", $this->config);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * setResponse 測試
     *
     * @return void
     */
    public function testSetResponse()
    {
        $response1 = new Response(
            200,
            [
                'Content-Type' => 'application/json charset=utf-8',
            ],
            json_encode([
                'status' => 200,
                'msg'  => "success",
                'data' => null
            ])
        );
        $closure = function () use ($response1) {

            $this->setResponse($response1);
            $decodeBody = json_decode($this->response->rawBody());

            assertNotNull($this->response);
            assertInstanceOf("Workerman\Protocols\Http\Response", $this->response);
            assertEquals("200", $this->response->getStatusCode());
            assertEquals("200", $decodeBody->status);
            assertEquals("success", $decodeBody->msg);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * processGlobals 測試
     *
     * @return void
     */
    public function testProcessGlobals()
    {
        $uri = "/test";
        $closure = function () use ($uri) {

            $this->processGlobals($uri);

            assertNotNull($this->filters["before"]);
            assertNotNull($this->filters["after"]);
            assertEquals("global", $this->filters["before"][0]);
            assertEquals("jsonResponse", $this->filters["after"][0]);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * processGlobals 測試
     *
     * @return void
     */
    public function testProcessGlobalsEmpty()
    {
        $uri = "/test";
        $closure = function () use ($uri) {
            $this->config->globals["before"] = [];
            $this->config->globals["after"]  = [];

            $result = $this->processGlobals($uri);
            assertNull($result);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * processAliasesToClass before 測試
     *
     * @return void
     */
    public function testProcessAliasesToClassWithBefore()
    {
        $uri = "/test";
        $closure = function () use ($uri) {
            $this->processGlobals($uri);
            $this->processAliasesToClass("before");
            assertNotNull($this->filtersClass["before"]);
            assertEquals("Test\Support\Filters\Filters\GlobalFilter", $this->filtersClass["before"][0]);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * processAliasesToClass after 測試
     *
     * @return void
     */
    public function testProcessAliasesToClassWithAfter()
    {
        $uri = "/test";
        $closure = function () use ($uri) {
            $this->processGlobals($uri);
            $this->processAliasesToClass("after");
            assertNotNull($this->filtersClass["after"]);
            assertEquals("AnserGateway\Filters\JsonResponseFilter", $this->filtersClass["after"][0]);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * processAliasesToClass after 測試
     *
     * @return void
     */
    public function testProcessAliasesToClassWithAliasException()
    {
        $phpUnit = $this;
        $closure = function () use ($phpUnit) {
            $phpUnit->expectException(FilterException::class);
            $phpUnit->expectExceptionMessage("unAliasFilter Filter 必須有一個匹配的別名定義");
            $this->filters["before"][] = "unAliasFilter";
            $this->processAliasesToClass("before");
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * enableFilter before測試
     *
     * @return void
     */
    public function testEnableFilterWithBefore()
    {
        $uri1        = "/test";
        $httpMethod1 = "GET";
        $gateway     = $this->gateway;
        $closure = function () use ($gateway, $uri1, $httpMethod1) {

            $gateway->tryToRouteIt($httpMethod1, $uri1);
            $filterInstance = $this->enableFilter("test2:dual,noreturn", "before");

            // assert arguments
            assertEquals("dual", $this->arguments["test2"][0]);
            assertEquals("noreturn", $this->arguments["test2"][1]);

            // assert config->aliases
            assertEquals("AnserGateway\Filters\JsonResponseFilter", $this->config->aliases["jsonResponse"]);
            assertEquals("Test\Support\Filters\Filters\TestFilter", $this->config->aliases["test"]);
            assertEquals("Test\Support\Filters\Filters\TestFilter2", $this->config->aliases["test2"]);
            assertEquals("Test\Support\Filters\Filters\GlobalFilter", $this->config->aliases["global"]);

            // assert argumentsClass
            assertEquals("dual", $this->argumentsClass["Test\Support\Filters\Filters\TestFilter2"][0]);
            assertEquals("noreturn", $this->argumentsClass["Test\Support\Filters\Filters\TestFilter2"][1]);

            // assert filters
            assertEquals("test2", $this->filters["before"][0]);

            // assert filtersClass
            assertEquals("Test\Support\Filters\Filters\TestFilter2", $this->filtersClass["before"][0]);

            // assert instance
            assertInstanceOf("AnserGateway\Filters\Filters", $filterInstance);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * enableFilter after測試
     *
     * @return void
     */
    public function testEnableFilterWithAfter()
    {
        $uri1        = "/test";
        $httpMethod1 = "GET";
        $gateway     = $this->gateway;
        $closure = function () use ($gateway, $uri1, $httpMethod1) {

            $gateway->tryToRouteIt($httpMethod1, $uri1);
            $filterInstance = $this->enableFilter("test2:dual,noreturn", "after");

            // assert arguments
            assertEquals("dual", $this->arguments["test2"][0]);
            assertEquals("noreturn", $this->arguments["test2"][1]);

            // assert config->aliases
            assertEquals("AnserGateway\Filters\JsonResponseFilter", $this->config->aliases["jsonResponse"]);
            assertEquals("Test\Support\Filters\Filters\TestFilter", $this->config->aliases["test"]);
            assertEquals("Test\Support\Filters\Filters\TestFilter2", $this->config->aliases["test2"]);
            assertEquals("Test\Support\Filters\Filters\GlobalFilter", $this->config->aliases["global"]);

            // assert argumentsClass
            assertEquals("dual", $this->argumentsClass["Test\Support\Filters\Filters\TestFilter2"][0]);
            assertEquals("noreturn", $this->argumentsClass["Test\Support\Filters\Filters\TestFilter2"][1]);

            // assert filters
            assertEquals("test2", $this->filters["after"][0]);

            // assert filtersClass
            assertEquals("Test\Support\Filters\Filters\TestFilter2", $this->filtersClass["after"][0]);

            // assert instance
            assertInstanceOf("AnserGateway\Filters\Filters", $filterInstance);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * enableFilter alias 例外測試
     *
     * @return void
     */
    public function testEnableFilterWithAliasException()
    {

        $phpUnit = $this;
        $closure = function () use ($phpUnit) {
            $phpUnit->expectException(FilterException::class);
            $phpUnit->expectExceptionMessage("unKnownAlias Filter 必須有一個匹配的別名定義");
            $this->enableFilter("unKnownAlias:dual,noreturn", "after");
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * enableFilters before測試
     *
     * @return void
     */
    public function testEnableFiltersWithBefore()
    {
        $uri1        = "/test";
        $httpMethod1 = "GET";
        $phpUnit     = $this;
        $gateway     = $this->gateway;
        $closure = function () use ($phpUnit, $gateway, $uri1, $httpMethod1) {
            $routeFilter =  $gateway->tryToRouteIt($httpMethod1, $uri1);
            // fwrite(STDERR, print_r($routeFilter, TRUE));
            $this->enableFilters($routeFilter, "before");
            assertNotNull($this->filters["before"]);
            assertEmpty($this->filters["after"]);
            assertEquals("test2", $this->filters["before"][0]);
            assertNotNull($this->filters["before"]);
            assertEmpty($this->filters["after"]);
            assertEquals("Test\Support\Filters\Filters\TestFilter2", $this->filtersClass["before"][0]);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * enableFilters after測試
     *
     * @return void
     */
    public function testEnableFiltersWithAfter()
    {
        $uri1        = "/test";
        $httpMethod1 = "GET";
        $phpUnit     = $this;
        $gateway     = $this->gateway;
        $closure = function () use ($phpUnit, $gateway, $uri1, $httpMethod1) {
            $routeFilter =  $gateway->tryToRouteIt($httpMethod1, $uri1);
            $this->enableFilters($routeFilter, "after");
            assertNotNull($this->filters["after"]);
            assertEmpty($this->filters["before"]);
            assertEquals("test2", $this->filters["after"][0]);
            assertNotNull($this->filters["after"]);
            assertEmpty($this->filters["before"]);
            assertEquals("Test\Support\Filters\Filters\TestFilter2", $this->filtersClass["after"][0]);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * getFilters 測試
     *
     * @return void
     */
    public function testGetFilters()
    {
        $uri = "/test";
        $closure = function () use ($uri) {
            $this->processGlobals($uri);
            $this->processAliasesToClass("before");
            $filters = $this->getFilters();

            assertNotNull($filters["before"]);
            assertNotNull($filters["after"]);
            assertEquals("global", $filters["before"][0]);
            assertEquals("jsonResponse", $filters["after"][0]);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * getFiltersClass  測試
     *
     * @return void
     */
    public function testGetFiltersClass()
    {
        $uri = "/test";
        $closure = function () use ($uri) {
            $this->processGlobals($uri);
            $this->processAliasesToClass("before");
            $this->processAliasesToClass("after");
            $filtersClass = $this->getFiltersClass();

            assertNotNull($filtersClass["before"]);
            assertNotNull($filtersClass["after"]);
            assertEquals("Test\Support\Filters\Filters\GlobalFilter", $filtersClass["before"][0]);
            assertEquals("AnserGateway\Filters\JsonResponseFilter", $filtersClass["after"][0]);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * getArguments  測試
     *
     * @return void
     */
    public function testGetArguments()
    {
        $uri1        = "/test";
        $httpMethod1 = "GET";
        $gateway     = $this->gateway;
        $closure = function () use ($gateway, $uri1, $httpMethod1) {
            $routeFilter =  $gateway->tryToRouteIt($httpMethod1, $uri1);
            $this->enableFilters($routeFilter, "before");
            $arguments = $this->getArguments();

            assertNotNull($arguments["test2"]);
            assertEquals("dual", $this->arguments["test2"][0]);
            assertEquals("noreturn", $this->arguments["test2"][1]);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * getArguments  傳入key搜尋arguments測試
     *
     * @return void
     */
    public function testGetArgumentsWithKey()
    {
        $uri1        = "/test";
        $httpMethod1 = "GET";
        $gateway     = $this->gateway;
        $closure = function () use ($gateway, $uri1, $httpMethod1) {
            $routeFilter =  $gateway->tryToRouteIt($httpMethod1, $uri1);
            $this->enableFilters($routeFilter, "before");
            $arguments = $this->getArguments("test2");

            assertNotNull($arguments);
            assertEquals("dual", $arguments[0]);
            assertEquals("noreturn", $arguments[1]);
        };
        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * initialize 測試
     *
     * @return void
     */
    public function testInitialize()
    {
        $uri = "/test";

        $closure = function () use ($uri) {
            $filterInstance = $this->initialize($uri);
            assertInstanceOf("AnserGateway\Filters\Filters", $filterInstance);
            assertTrue($this->initialized);
            assertEquals("jsonResponse", $this->filters["after"][count($this->filters["after"])-1]);
        };

        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * run before測試
     *
     * @return void
     */
    public function testRunWithBefore()
    {
        $uri = "/test";

        $closure = function () use ($uri) {
            $result = $this->run($uri, "before");
            assertInstanceOf("Workerman\Protocols\Http\Request", $result);
            assertNotNull($result);
        };

        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * run after測試
     *
     * @return void
     */
    public function testRunWithAfter()
    {
        $uri = "/test";

        $closure = function () use ($uri) {
            $result = $this->run($uri, "after");
            assertInstanceOf("Workerman\Protocols\Http\Response", $result);
            assertNotNull($result);
        };

        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * run 例外測試
     *
     * @return void
     */
    public function testRunWithException()
    {
        $httpMethod = "GET";
        $uri = "/unImplementFilter";
        $phpUnit = $this;
        $gateway = $this->gateway;

        $closure = function () use ($gateway, $phpUnit, $httpMethod, $uri) {

            $phpUnit->expectException(FilterException::class);
            $phpUnit->expectExceptionMessage("Test\Support\Filters\Filters\UnImplementFilter 必須實作 AnserGateway/Filters/FilterInterface");
            $routeFilter = $gateway->tryToRouteIt($httpMethod, $uri);
            $this->enableFilters($routeFilter, 'before');
            $this->enableFilters($routeFilter, 'after');
            $this->run($uri, "before");

            $phpUnit->expectException(FilterException::class);
            $phpUnit->expectExceptionMessage("Test\Support\Filters\Filters\UnImplementFilter 必須實作 AnserGateway/Filters/FilterInterface");
            $routeFilter = $gateway->tryToRouteIt($httpMethod, $uri);
            $this->enableFilters($routeFilter, 'before');
            $this->enableFilters($routeFilter, 'after');
            $this->run($uri, "after");
        };

        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * reset 測試
     *
     * @return void
     */
    public function testReset()
    {
        $uri = "/test";

        $closure = function () use ($uri) {
            $this->run($uri, "after");
            $this->reset();
            assertFalse($this->initialized);
            assertEmpty($this->arguments);
            assertEmpty($this->argumentsClass);
            assertEmpty($this->filters["before"]);
            assertEmpty($this->filters["after"]);
            assertEmpty($this->filtersClass["before"]);
            assertEmpty($this->filtersClass["after"]);
        };

        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * pathApplies 匹配測試
     *
     * @return void
     */
    public function testPathApplies()
    {
        $uri     = "/test2";
        $except  = "unExcept/*";
        $uri = strtolower(trim($uri ?? '', '/ '));
        $closure = function () use ($uri, $except) {
            // $this->processGlobals($uri);
            // fwrite(STDERR, print_r($this->filters, TRUE));
            $result = $this->pathApplies($uri, $except);
            assertFalse($result);
        };

        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * pathApplies 路由不匹配測試
     *
     * @return void
     */
    public function testPathAppliesFail()
    {
        $uri = "/unExcept/testTwo";
        $except = "unExcept/*";
        $uri = strtolower(trim($uri ?? '', '/ '));
        $closure = function () use ($uri, $except) {
            $result = $this->pathApplies($uri, $except);
            assertTrue($result);
        };

        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }

    /**
     * pathApplies empty測試
     *
     * @return void
     */
    public function testPathAppliesEmpty()
    {
        $uri = "/testTwo";
        $except = "";
        $uri = strtolower(trim($uri ?? '', '/ '));
        $closure = function () use ($uri, $except) {
            $result = $this->pathApplies($uri, $except);
            assertTrue($result);
        };

        $binding = $closure->bindTo($this->filter, get_class($this->filter));
        $binding();
    }
}
