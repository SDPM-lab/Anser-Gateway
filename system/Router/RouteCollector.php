<?php 
namespace AnserGateway\Router;

class RouteCollector extends RouteCollection
{
    /**
     * 路由表
     *
     * @var callable
     */
    protected static $routeList;

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

        try{
            self::$routeList   = require_once $routesFile;
            self::$didDiscover = true;
        }catch(\Exception $th){
            throw $th;
        }
    
        return self::$routeList;
    }
}


?>