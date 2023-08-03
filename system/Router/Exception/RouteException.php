<?php

namespace AnserGateway\Router\Exception;

use AnserGateway\Exception\AnserGatewayException;

class RouteException extends AnserGatewayException
{
    /**
     * 初始化
     *
     * @param string $message 錯誤訊息
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forRouteFileNotExist($routesFile): \Error
    {
        return new \Error("路由表檔案 : {$routesFile} 不存在");
    }
    
    public static function forRouteNotExist($uri): RouteException
    {
        return new self("請求路由 : {$uri} 不存在，可能尚未定義");
    }

    public static function forInvalidControllerName($handler): RouteException
    {
        return new self("{$handler} Namespace分隔符號為反斜線(\)，非斜線(/)");
    }

    public static function forMethodNotAllowed($httpMethod, $allowedMethods): RouteException
    {
        return new self("請求類型錯誤({$httpMethod}),當前允許的請求類型({$allowedMethods})");
    }

    public static function forControllerFileNotExist($controllerFileName): RouteException
    {
        return new self("Controller - {$controllerFileName} 檔案不存在，請確認是否已定義檔案");
    }

    public static function forEmptyController(): RouteException
    {
        return new self("找無指定的Controller");
    }

    public static function forControllerNotExist($controllerName): RouteException
    {
        return new self("Controller - {$controllerName} 不存在，請確認是否已定義名稱為 {$controllerName} 的Controller");
    }
    public static function forControllerMethodNotExist($controllerName, $controllerMethodName): RouteException
    {
        return new self("Controller - {$controllerName} 中不存在名稱為 - {$controllerMethodName} 的方法");
    }

    public static function forControllerNotValid($controller): RouteException
    {
        return new self("Controller - {$controller} 尚未繼承 AnserGateway\Config\BaseController類別");
    }


}
