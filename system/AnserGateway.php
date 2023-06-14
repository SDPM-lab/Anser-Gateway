<?php

namespace AnserGateway;

use AnserGateway\Router\Router;
use AnserGateway\Router\RouterInterface;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use AnserGateway\Router\Exception\RouteException;
use AnserGateway\Filters\Filters;

class AnserGateway
{
    /**
     * request.
     *
     * @var Request|null
     */
    protected $request;

    /**
     * Current response.
     *
     * @var Response
     */
    protected $response;

    /**
     * Router to use.
     *
     * @var Router
     */
    protected $router;

    /**
     * Controller to use.
     *
     * @var Closure|string
     */
    protected $controller;

    /**
     * Controller method to invoke.
     *
     * @var string
     */
    protected $method;

    /**
     * 請求夾帶參數
     *
     * @var array
     */
    protected $params;

    public function __construct(RouterInterface $router)
    {
        /**
         * Injection route,if you want get routeCollector
         * can use $this->router->collector;
         */
        $this->router    = $router;
        $this->response  = new Response();
    }

    public function handleRequest(Request $request)
    {
        $uri           = $request->uri();
        $httpMethod    = $request->method();
        $this->request = $request;

        $routeFilter = $this->tryToRouteIt($httpMethod, $uri);

        $filters = new Filters($this->request, $this->response);

        # Do before() filter
        if ($routeFilter !== null) {
            $filters->enableFilters($routeFilter, 'before');
            $filters->enableFilters($routeFilter, 'after');
        }

        $possibleResponse = $filters->run($uri, 'before');

        // 如果回傳一個response類型的回傳值，則直接響應
        if ($possibleResponse instanceof Response) {
            return $possibleResponse;
        }

        if ($possibleResponse instanceof Request) {
            $this->request = $possibleResponse;
        }

        # Closure 在此方法執行
        $returned = $this->startController();

        # do Controller
        if (! is_callable($this->controller)) {
            $controller = $this->createController();
            $returned   = $this->runController($controller);
        }

        /**
         * 用instanceof 判斷returned是否為response
         * 預設Controller都回傳Response
         */
        if ($returned instanceof Response) {
            $this->response = $returned;
        }

        # Do after() filter  something...
        $filters->setResponse($this->response);
        $response = $filters->run($uri, 'after');

        if ($response instanceof Response) {
            $this->response = $response;
        }

        unset($uri);

        return $this->response;
    }

    /**
     * match route and return filters
     *
     * @param string $httpMethod
     * @param string $uri
     * @return array|null
     */
    public function tryToRouteIt($httpMethod, $uri)
    {

        $this->controller = $this->router->handle($httpMethod, $uri);
        $this->method     = $this->router->getMethod();
        $this->params     = $this->router->getParams();

        return $this->router->getFilters();
    }

    /**
     * 如果有Closure直接執行，否則執行Controller檢查
     *
     * @return Response|string|void
     */
    public function startController()
    {
        // Is it routed to a Closure?
        if (is_object($this->controller) && (get_class($this->controller) === 'Closure')) {
            $controller = $this->controller;

            # 或許我們需要將執行週期的request、response傳入Closure中
            return $controller($this->router->getParams(), $this->request, $this->response);
        }

        // 無指定controller 拋出 Exception
        if (empty($this->controller)) {
            throw RouteException::forEmptyController();
        }

        // 嘗試使用autoload找尋該controller
        if (! class_exists($this->controller, true)) {
            throw RouteException::forControllerNotExist($this->controller);
        }

        // 該controller並未繼承 BaseController 則視為非\AnserGateway\Controller類型，拋出Exception
        if (! new $this->controller() instanceof \AnserGateway\Controller) {
            throw RouteException::forControllerNotValid($this->controller);
        }

        // 該controller內找不到對應method，拋出Exception
        if (!method_exists($this->controller, $this->method)) {
            throw RouteException::forControllerMethodNotExist($this->controller, $this->method);
        }
    }

    /**
     * 實例化 Controller
     *
     * @return Controller
     */
    protected function createController()
    {
        assert(is_string($this->controller));

        $class = new $this->controller();
        # Do init controller
        // $class->initController(new Request(), $this->response);
        $class->initController($this->request, $this->response);

        return $class;
    }

    /**
     * 執行Controller
     *
     * @param mixed $class
     * @return false|Response|string|void
     */
    protected function runController($class)
    {
        $params = $this->router->getParams();

        $output = $class->{$this->method}(...$params);

        return $output;
    }
}
