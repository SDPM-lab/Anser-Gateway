<?php
namespace Config;
use AnserGateway\Router\RouteCollector;
use Workerman\Protocols\Http\Response;
return function (RouteCollector $route) {
    /**
     * system default route
     */
    $route->get('/',[\App\Controllers\HeartBeat::class, 'index']);
}

?>