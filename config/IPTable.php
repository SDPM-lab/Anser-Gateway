<?php
namespace Config;
use AnserGateway\ServiceDiscovery\LoadBalance\LoadBalance;
use Exception;
class IPTable
{
    /**
     * ip table
     *
     * @var array
     */
    public static $tables = [
        "ProductService" => [
            "http://140.127.74.161:8081"
        ]
    ];

    /**
     * ServiceList Update callback From Anser-Gateway 
     *
     * @var null|callable
     */
    protected static $serviceDataHandlerCallback = null;

    public static function setIPTableWithServiceDiscovery(callable $serviceDataHandlerCallback)
    {
        static::$serviceDataHandlerCallback = $serviceDataHandlerCallback;
    }

    /**
     * get single service data
     *
     * @param string $serviceName 服務名稱
     */
    public static function getServiceData(string $serviceName)
    {
        if(is_null(static::$serviceDataHandlerCallback)){
            $address = LoadBalance::do(static::$tables[$serviceName]);

            if (filter_var($address, FILTER_VALIDATE_URL) !== false) {
                $parseUrl = parse_url($address);
                if(isset($parseUrl["port"])){
                    $port = (int)$parseUrl["port"];
                }else{
                    $port = $parseUrl["scheme"] === "https" ? 443 : 80;
                }
                return [
                    "name"    => $parseUrl["host"],
                    "address" => $parseUrl["host"],
                    "port"    => $port,
                    "scheme"  => $parseUrl["scheme"] === "https"
                ];
            }
            
        } else {
            $callableResult = call_user_func(static::$serviceDataHandlerCallback);
            if (!is_array($callableResult)) {
                throw new Exception("{$serviceName} 定義的回呼函數回傳型別錯誤，請檢查回傳型別是否為 array 或 null。");
            }
            return $callableResult; 
        }
    }
}
?>