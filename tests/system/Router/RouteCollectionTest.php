<?php
use Config\Path;
use PHPUnit\Framework\TestCase;
use AnserGateway\Router\RouteCollector;
use AnserGateway\Router\Exception\RouteException;

/**
 * test usage
 */
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

class RouteCollectionTest extends TestCase
{
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Path();
    }
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->config = null;
    }

    /**
     * 無參數直接call loadRoutes
     *
     * @return void
     */
    public function testCallRoute()
    {
        $routeClosure = RouteCollector::loadRoutes();

        assertInstanceOf('Closure', $routeClosure);
        assertNotNull(RouteCollector::getRouteList());
        assertTrue(RouteCollector::getDidDiscover());
    }

    /**
     * 載入Routes成功測試
     *
     * @return void
     */
    public function testLoadRouteSuccess()
    {
        $realRouteClosure = function (RouteCollector $route) {
            $route->get('/test', [\Test\Support\Controllers\TestController::class, 'index'], ['filter'=>'test2:dual,noreturn']);
            $route->get('/test/{name}', [\Test\Support\Controllers\TestController::class, 'index'], ['filter'=>'test2:dual,noreturn']);
            $route->post('/test', [\Test\Support\Controllers\TestController::class, 'show'], ['filter'=>'test2:dual,noreturn']);

            $route->get('/closure/{name}', function ($params) {
                echo "123".PHP_EOL;
            }, ['filter'=>'test2:dual,noreturn']);
            $route->group('/api', ['filter'=>'test'], function (RouteCollector $route) {
                $route->get('/test/{name}/{name2}', [\Test\Support\Controllers\TestController::class, 'index'], ['filter'=>'test2']);
                $route->post('/testTwo', [\Test\Support\Controllers\TestController::class, 'show'], ['filter'=>'test2']);
                $route->get('/asd/{name}', function ($params, $request, $response) {
                    var_dump($request->method());
                }, ['filter'=>'test2']);
            });
        };

        $routeClosure = RouteCollector::loadRoutes($this->config->testDirectory . '/_support/Routes/Routes.php');
        
        assertEquals($routeClosure, $realRouteClosure);
        assertInstanceOf('Closure', $routeClosure);
        assertNotNull(RouteCollector::getRouteList());
        assertTrue(RouteCollector::getDidDiscover());
    }
}
