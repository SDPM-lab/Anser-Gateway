<?php

namespace Test\Support\Routes;

use AnserGateway\Router\RouteCollector;

return function (RouteCollector $route) {
    $route->get('/test', [\Test\Support\Controllers\TestController::class, 'index'], ['filter'=>'test2:dual,noreturn']);
    $route->get('/test2', [\Test\Support\Controllers\TestController::class, 'index'], ['filter'=>'test']);
    $route->get('/unknownMethod', [\Test\Support\Controllers\TestController::class, 'unknownMethod']);
    $route->get('/invalid', [\Test\Support\Controllers\InvalidController::class, 'index']);
    $route->get('/unExistController', ["","" ]);
    $route->get('/routeSlashError', ["Test/Support/Controllers/InvalidController::class", 'index']);
    $route->get('/unImplementFilter', [\Test\Support\Controllers\TestController::class, 'index'], ['filter'=>'unImplementFilter']);
    $route->get('/letResponse', [\Test\Support\Controllers\TestController::class, 'index'], ['filter'=>'letResponse']);
    $route->get('/test/{name}', [\Test\Support\Controllers\TestController::class, 'index'], ['filter'=>'test2:dual,noreturn']);
    $route->post('/test', [\Test\Support\Controllers\TestController::class, 'show'], ['filter'=>'test2:dual,noreturn']);
    $route->get('/closure/{name}', function ($params) {
        return $params;
    }, ['filter'=>'test2:dual,noreturn']);
    $route->group('/api', ['filter'=>'test'], function (RouteCollector $route) {
        $route->get('/test/{name}/{name2}', [\Test\Support\Controllers\TestController::class, 'index'], ['filter'=>'test2']);
        $route->post('/testTwo', [\Test\Support\Controllers\TestController::class, 'show'], ['filter'=>'test2']);
        $route->get('/asd/{name}', function ($params, $request, $response) {
            return $request->method();
        }, ['filter'=>'test2']);
    });
};
