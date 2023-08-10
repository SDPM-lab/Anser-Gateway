<?php

namespace AnserGateway\Router;

use FastRoute\Dispatcher;
use AnserGateway\Router\RouteCollectionInterface;
use AnserGateway\Router\Exception\RouteException;
use AnserGateway\Router\RouteCollector;
use Closure;

use function FastRoute\simpleDispatcher;

class Router implements RouterInterface
{
    /**
     * RouteCollector
     *
     * @var Closure|RouteCollector
     */
    public Closure|RouteCollector $collector;
    /**
     * 路由適配器
     *
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * 當前路由對應的Controller名稱
     *
     * @var Closure|string
     */
    protected $controller;

    /**
     * 當前路由對應的method名稱
     *
     * @var string
     */
    protected string $method;

    /**
     * 當前路由請求所包含的參數
     *
     * @var array
     */
    protected array $params = [];

    /**
     * 路由註冊的filter
     *
     * @var array
     */
    protected array $filters = [];

    public function __construct($routes)
    {
        $this->collector  = $routes;
        $this->dispatcher = $this->dispatcher($routes);
    }

    /**
     * 找尋與uri對應的Controller方法
     *
     * @param string $httpMethod
     * @param string $uri
     */
    public function handle($httpMethod, $uri)
    {
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        $uri = rawurldecode($uri);

        // 路由調度
        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw RouteException::forRouteNotExist($uri);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                throw RouteException::forMethodNotAllowed($httpMethod, $allowedMethods[0]);
                break;
            case Dispatcher::FOUND: // 請求配對成功，回傳找到的路由資料

                $this->resetFilters();
                if($this->checkRoute($routeInfo)) {
                    return $this->controller;
                }
                throw RouteException::forControllerNotExist($routeInfo[1]['handler'][0]);
                break;
        }
    }

    /**
     * 實例化適配器
     *
     * @param callable $routeList
     * @return Dispatcher
     */
    public function dispatcher($routeList)
    {
        return simpleDispatcher($routeList, [
            'routeCollector' => RouteCollector::class,
        ]);
    }

    /**
     * 確認 routeInfo
     *
     * @param $routeInfo
     * @return boolean
     */
    protected function checkRoute($routeInfo): bool
    {

        if (empty($routeInfo[1])) {
            return false;
        }
        
        $handler = $routeInfo[1]['handler'];  // controller or closure
        $options = $routeInfo[1]['options'];  // route 本身的附加選項
        $group   = $routeInfo[1]['group'];    // route 依附的group附加選項
        $params  = $routeInfo[2];             // route 本身附加的參數


        if (! is_string($handler) && is_callable($handler)) {

            $this->controller = $handler;

            $this->params = $params;

            $this->setFilter($options, $group);

            return true;
        }

        $controller = $handler[0];
        $method     = $handler[1];

        if (strpos($controller, '/') !== false) {
            throw RouteException::forInvalidControllerName($controller);
        }

        if ($this->scanHandler($controller)) {
            $this->setFilter($options, $group);
            $this->setRequest($controller, $method, $params);

            return true;
        }

        return false;
    }

    protected function setRequest($controller, $method, $params)
    {
        $this->controller = $controller;

        if (! empty($method)) {
            $this->method = $method;
        }

        $this->params = $params;
    }

    /**
     * 確認Controller檔案是否存在
     *
     * @return bool
     */
    protected function scanHandler($controller): bool
    {

        $controllerPath = explode('\\', $controller);

        if ($controllerPath[0] === "Test") {
            $controllerName        = explode('Test\\Support\\Controllers\\', $controller)[1];
            $replaceControllerName = str_replace('\\', DIRECTORY_SEPARATOR, $controllerName);

            if (! is_file(PROJECT_TEST . '_support' . DIRECTORY_SEPARATOR . 'Controllers'  . DIRECTORY_SEPARATOR . $replaceControllerName.'.php')) {
                throw RouteException::forControllerFileNotExist($controller);
            }
            return true;
        }

        if ($controllerPath[0] === "App") {
            $controllerName        = explode('App\\Controllers\\', $controller)[1];
            $replaceControllerName = str_replace('\\', DIRECTORY_SEPARATOR, $controllerName);

            if (! is_file(PROJECT_APP . 'HTTP' . DIRECTORY_SEPARATOR . 'Controllers'  . DIRECTORY_SEPARATOR . $replaceControllerName.'.php')) {
                throw RouteException::forControllerFileNotExist($controller);
            }
            return true;
        }
           
        return false;
    }

    /**
     * 儲存route對應的filter
     *
     * @param array $options
     * @param array $group
     * @return void
     */
    protected function setFilter($options, $group): void
    {
        if (! empty($group) && isset($group['filter'])) {
            $this->filters['group'] = $group['filter'];
        }

        if (! empty($options) && isset($options['filter'])) {
            $this->filters['route'] = $options['filter'];
        }
    }

    /**
     * 重置filter
     *
     * @return void
     */
    protected function resetFilters()
    {
        $this->filters = [];
    }

    /**
     * 回傳所有Filter
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Returns the name of the matched controller.
     *
     * @return Closure|string Controller className or Closure
     */
    public function getController()
    {
        return $this->controller ?? null;
    }
    public function getMethod()
    {
        return $this->method ?? null;
    }

    public function getParams()
    {
        return $this->params ?? null;
    }
}
