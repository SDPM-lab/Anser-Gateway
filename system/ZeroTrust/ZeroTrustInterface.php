<?php 
namespace AnserGateway\ZeroTrust;
use Workerman\Protocols\Http\Request;
interface ZeroTrustInterface
{
    public static function verifyProcess(string $username,string $password, string $serviceName, Request $request, string $requestSerivceAction);
    public static function verifyUser(string $username,string $password): string;
}

?>