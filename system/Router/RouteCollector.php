<?php

namespace AnserGateway\Router;
use AnserGateway\Router\Exception\RouteException;
class RouteCollector extends RouteCollection
{
    /**
     * 路由表
     *
     * @var callable
     */
    protected static $routeList = null;

    /**
     * A little performance booster.
     *
     * @var bool
     */
    protected static $didDiscover = false;

    /**
     * 載入主要的路由檔案
     *
     * 除非重置，否則檔案只會載入一次
     *
     */
    public static function loadRoutes(string $routesFile = PROJECT_CONFIG . 'Routes.php')
    {
        if (self::$didDiscover) {
            return self::$routeList;
        }

        self::$routeList   = require $routesFile;
        self::$didDiscover = true;

        return self::$routeList;
    }

    /**
     * 取得routeList
     *
     * @return callable|null
     */
    public static function getRouteList()
    {
        return self::$routeList;
    }

    /**
     * 取得didDiscover
     *
     * @return bool
     */
    public static function getDidDiscover()
    {
        return self::$didDiscover;
    }

    public static function resetDiscover()
    {
        self::$didDiscover = false;
    }
}
