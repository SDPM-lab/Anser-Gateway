<?php
namespace Config;
use AnserGateway\Router\RouteCollector;
use Workerman\Protocols\Http\Response;
return function (RouteCollector $route) {

    $route->get('/test',[\App\Controllers\TestController::class, 'index'],['filter'=>'test2:dual,noreturn']);
    $route->post('/test',[\App\Controllers\TestController::class, 'show'],['filter'=>'test2:dual,noreturn']);

    $route->get('/asd/{name}', function($params){
        echo "123".PHP_EOL;
    },['filter'=>'test2:dual,noreturn']);
    $route->group('/api',['filter'=>'test'], function (RouteCollector $route) {
        $route->get('/test', [\App\Controllers\V1\TestController::class, 'index'],['filter'=>'test2']);
        $route->get('/asd/{name}', function($params,$request,$response){
            var_dump($request->method());
        },['filter'=>'test2']);
    });
}

?>