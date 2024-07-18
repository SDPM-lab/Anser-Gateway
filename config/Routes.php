<?php
namespace Config;
use AnserGateway\Router\RouteCollector;
use Workerman\Protocols\Http\Response;
return function (RouteCollector $route) {
    /**
     * system default route
     */
    $route->get('/',[\App\Controllers\HeartBeat::class, 'index']);
    $route->get('/zerotrust',[\App\Controllers\ZeroTrustController::class, 'index']);
    $route->post('/ztAPI',[\App\Controllers\ZeroTrustController::class, 'ztAPI']);
    // $route->post('/ztAdmin',[\App\Controllers\ZeroTrustController::class, 'ztAdmin'],['filter'=>'zeroTrust']);
    $route->post('/api/v1/user/login',[\App\Controllers\ZeroTrustController::class, 'login'],['filter'=>'zeroTrust']);
    $route->post('/api/v1/user/nonZTLogin',[\App\Controllers\ZeroTrustController::class, 'nonZTLogin']);
    
    // $route->get('/api/v1/prduct',[\App\Controllers\ZeroTrustController::class, 'login']);
    $route->post('/api/v1/prduct',[\App\Controllers\ZeroTrustController::class, 'login']);
    // $route->put('/api/v1/prduct/$id',[\App\Controllers\ZeroTrustController::class, 'login']);
}

?>

