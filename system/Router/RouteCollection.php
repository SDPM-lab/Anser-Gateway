<?php

namespace AnserGateway\Router;

use AnserGateway\Router\RouteCollectionInterface;
use FastRoute\DataGenerator;
use FastRoute\RouteParser;

abstract class RouteCollection implements RouteCollectionInterface
{
    /** @var RouteParser */
    protected $routeParser;

    /** @var DataGenerator */
    protected $dataGenerator;


    /** @var string */
    protected $currentGroupPrefix;

    /**
     * 存儲當前option的備份
     * 創建時應用
     *
     * @var array|null
     */
    protected $currentOptions;

    /**
     * Constructs a route collector.
     *
     * @param RouteParser   $routeParser
     * @param DataGenerator $dataGenerator
     */
    public function __construct(RouteParser $routeParser, DataGenerator $dataGenerator)
    {
        $this->routeParser = $routeParser;
        $this->dataGenerator = $dataGenerator;
        $this->currentGroupPrefix = '';
    }

    /**
     * Adds a route to the collection.
     *
     * The syntax used in the $route string depends on the used route parser.
     *
     * @param string|string[] $httpMethod
     * @param string $route
     * @param mixed  $remixHandler
     */
    public function addRoute($httpMethod, $route, $remixHandler)
    {
        $route = $this->currentGroupPrefix . $route;
        $routeDatas = $this->routeParser->parse($route);
        foreach ((array) $httpMethod as $method) {
            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRoute($method, $routeData, $remixHandler);
            }
        }
    }

    /**
     * Create a route group with a common prefix.
     *
     * All routes created in the passed callback will have the given group prefix prepended.
     *
     * @param string $prefix
     * @param array $options
     * @param callable $callback
     */
    public function group(string $prefix, ...$params)
    {
        $callback = array_pop($params);

        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        if ($params && is_array($params[0])) {
            $this->currentOptions = array_shift($params);
        }

        if (is_callable($callback)) {
            $callback($this);
        }

        $this->currentGroupPrefix = $previousGroupPrefix;
    }

    /**
     * 新增一個 get 路由至 collection
     *
     * @param string $route
     * @param mixed  $handler
     * @param array  $options
     */
    public function get(string $route, mixed $handler, array $options = [])
    {
        $remixHandler = [
            'handler' => $handler,
            'options' => $options,
            'group'   => $this->currentOptions?? []
        ];

        $this->addRoute('GET', $route, $remixHandler);
    }

    /**
     * 新增一個 post 路由至 collection
     *
     * @param string $route
     * @param mixed  $handler
     * @param array  $options
     */
    public function post(string $route, mixed $handler, array $options = [])
    {
        $remixHandler = [
            'handler' => $handler,
            'options' => $options,
            'group'   => $this->currentOptions?? []
        ];
        $this->addRoute('POST', $route, $remixHandler);
    }

    /**
     * 新增一個 put 路由至 collection
     *
     * @param string $route
     * @param mixed  $handler
     * @param array  $options
     */
    public function put(string $route, mixed $handler, array $options = [])
    {
        $remixHandler = [
            'handler' => $handler,
            'options' => $options,
            'group'   => $this->currentOptions?? []
        ];
        $this->addRoute('PUT', $route, $remixHandler);
    }

    /**
     * 新增一個 delete 路由至 collection
     *
     * @param string $route
     * @param mixed  $handler
     * @param array  $options
     */
    public function delete(string $route, mixed $handler, array $options = [])
    {
        $remixHandler = [
            'handler' => $handler,
            'options' => $options,
            'group'   => $this->currentOptions?? []
        ];
        $this->addRoute('DELETE', $route, $remixHandler);
    }

    /**
     * 新增一個 patch 路由至 collection
     *
     * @param string $route
     * @param mixed  $handler
     * @param array  $options
     */
    public function patch(string $route, mixed $handler, array $options = [])
    {
        $remixHandler = [
            'handler' => $handler,
            'options' => $options,
            'group'   => $this->currentOptions?? []
        ];
        $this->addRoute('PATCH', $route, $remixHandler);
    }

    /**
     * 新增一個 head 路由至 collection
     *
     * @param string $route
     * @param mixed  $handler
     * @param array  $options
     */
    public function head(string $route, mixed $handler, array $options = [])
    {
        $remixHandler = [
            'handler' => $handler,
            'options' => $options,
            'group'   => $this->currentOptions?? []
        ];
        $this->addRoute('HEAD', $route, $remixHandler);
    }

    /**
     * 新增一個 options 路由至 collection
     *
     * @param string $route
     * @param mixed  $handler
     * @param array  $options
     */
    public function options(string $route, mixed $handler, array $options = [])
    {
        $remixHandler = [
            'handler' => $handler,
            'options' => $options,
            'group'   => $this->currentOptions?? []
        ];
        $this->addRoute('OPTIONS', $route, $remixHandler);
    }

    /**
     * Returns the collected route data, as provided by the data generator.
     *
     * @return array
     */
    public function getData()
    {
        return $this->dataGenerator->getData();
    }


}
