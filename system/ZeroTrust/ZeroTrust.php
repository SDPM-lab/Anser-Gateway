<?php

namespace AnserGateway\ZeroTrust;

use AnserGateway\ZeroTrust\ZeroTrustInterface;
use SDPMlab\Anser\Service\Action;
use Psr\Http\Message\ResponseInterface;
use SDPMlab\Anser\Exception\ActionException;
use Config\ZeroTrust as ZeroTrustConfig;
use Workerman\Protocols\Http\Request;

use function Swow\Debug\isStrictStringable;

class ZeroTrust implements ZeroTrustInterface{

    /**
     * config of zero trust
     *
     * @var ZeroTrustConfig
     */
    private static $ZeroTrustConfig = null;

    /**
     * token of gateway
     *
     * @var string
     */
    private static $gatewayAccessToken = null;

    /**
     * Url of keycloak api
     *
     * @var array<string<string>>
     */
    private static $keycloakAccessUrlMap = [
        "tokenAuth"   => "protocol/openid-connect/token",
        "introspect"  => "protocol/openid-connect/token/introspect",
        "resourceSet" => "authz/protection/resource_set",
        "permission"  => "authz/protection/permission"
    ];

    /**
     * Grant Type of keycloak api params
     */
    private static $keycloakGrantType = [
        "password"          => "password",
        "clientCredentials" => "client_credentials",
    ];

    /**
     * resources的UUID陣列
     *
     * @var array
     */
    public static $resourcesUUIDArray = [];

    private static $resource_access = [];

    /**
     * initialization for get all client
     *
     * @return void
     */
    public static function initialization(ZeroTrustConfig $config): void
    {
        static::$ZeroTrustConfig = $config;
        // 驗證gateway client是否合法
        $verify = static::gatewayClientVerify();
        // 如果合法就取得其他client的資料
        if ($verify) {
            static::setResourcesUUID(static::$ZeroTrustConfig->customClient);
            static::afterInit();
        }
    }

    /**
     * Step 1. check gateway client status
     *
     * @return bool
     */
    protected static function gatewayClientVerify(): bool
    {
        $accessURL = "realms/" . static::$ZeroTrustConfig->realm . "/" . static::$keycloakAccessUrlMap["tokenAuth"];
        $action = (new Action(
            static::$ZeroTrustConfig->host,
            "POST",
            $accessURL
        ))
        ->addOption("form_params",[
            'grant_type'    => static::$keycloakGrantType["clientCredentials"],
            'client_id'     => static::$ZeroTrustConfig->gatewayClientID,
            'client_secret' => static::$ZeroTrustConfig->gatewayClientSecret,
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        })->failHandler(static::errorHandler());

        $adminToken = $action->do()->getMeaningData();
        if (isset($adminToken["access_token"]) && !empty($adminToken["access_token"])) {
            return true;
        }
        if (isset($data["code"])) {
            return null;
        }
        return false;
    }

    /**
     * Step 2.取得各個client的token上層
     *
     * @param array $customClientArray
     * @return void
     */
    protected static function setResourcesUUID(array $customClientArray): void
    {
        try {
            foreach ($customClientArray as $customClient) {
                $resourceToken = static::getResourceToken($customClient);

                if (is_null($resourceToken)) {
                    throw new \Exception("customClinet {$customClient['clientID']} not found, please check your keycloak.", 1);
                }
                $resourceUUIDs = static::getResourceUUIDs($resourceToken);
                static::$resourcesUUIDArray[$customClient["clientID"]] = $resourceUUIDs;
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Step 3.取得各個client的token
     *
     * @param array $customClient env裏頭的client定義
     * @return string|null
     */
    protected static function getResourceToken(array $customClient): ?string
    {
        $accessURL = "realms/" . static::$ZeroTrustConfig->realm . "/" . static::$keycloakAccessUrlMap["tokenAuth"];
        $action = (new Action(
            static::$ZeroTrustConfig->host,
            "POST",
            $accessURL
        ))
        ->addOption("form_params",[
            'grant_type'    => static::$keycloakGrantType["clientCredentials"],
            'client_id'     => $customClient["clientID"],
            'client_secret' => $customClient["clientSecret"],
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        })->failHandler(static::errorHandler());

        $data = $action->do()->getMeaningData();
        if (isset($data["access_token"]) && !empty($data["access_token"])) {
            return $data["access_token"];
        }
        if (isset($data["code"])) {
            return null;
        }
        return null;
    }

    /**
     * 取得單一resource的access token
     *
     * @param string $resourceToken
     * @return array|null
     */
    protected static function getResourceUUIDs(string $resourceToken): ?array
    {
        $accessURL = "realms/" . static::$ZeroTrustConfig->realm . "/" . static::$keycloakAccessUrlMap["resourceSet"];
        $action = (new Action(
            static::$ZeroTrustConfig->host,
            "GET",
            $accessURL
        ))
        ->addOption("headers",[
            'Authorization' => "Bearer {$resourceToken}",
            'Content-Type'  => "application/json",
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        })->failHandler(static::errorHandler());

        $data = $action->do()->getMeaningData();
        if (isset($data["error"])) {
            return null;
        }
        if (isset($data["code"])) {
            return null;
        }
        return $data;
    }

    /**
     * action error handler
     *
     * @return callable
     */
    protected static function errorHandler(): callable
    {
        return function (
            ActionException $e
        ) {
            if($e->isClientError()){
                $e->getAction()->setMeaningData([
                    "code" => $e->getStatusCode(),
                    "msg" => "client error"
                ]);
            }else if ($e->isServerError()){
                $e->getAction()->setMeaningData([
                    "code" => $e->getStatusCode(),
                    "msg" => "server error"
                ]);
            }else if($e->isConnectError()){
                $e->getAction()->setMeaningData([
                    "msg" => $e->getMessage()
                ]);
            }   
        };
    }

    public static function afterInit()
    {
        echo "========================================================================================\n";
        echo "                            AnserGateway - ZeroTrust is enabled...                      \n";
        echo "========================================================================================\n";
    }


    public static function verifyProcess(string $username, string $password, string $serviceName, Request $request, string $requestSerivceAction)
    {
        try {
            // 拿到gateway client的token
            $gatewayAccessToken      = static::verifyUser($username, $password);
            // 用gateway client的token去拿resource access
            $resource_access         = static::introspect($gatewayAccessToken);
            // 持久化resource_access
            static::$resource_access = $resource_access;
            // 去找當前訪問的service client
            $customClientAccessToken = static::getCustomClientTokenByServiceName($serviceName);
            $UUID                    = static::verifyEndpoint($request,$customClientAccessToken,$serviceName);
            $scopes                  = static::getresourceSetByUUID($UUID,$customClientAccessToken);
            $ticket = static::permission($customClientAccessToken, $UUID, $scopes, $requestSerivceAction);
            return [
                "customClientAccessToken" => $customClientAccessToken,
                "ticket"                  => $ticket
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 驗證使用者有沒有 zero-trust-gateway 的權限
     *
     * @param string $username
     * @param string $password
     * @return string
     */
    public static function verifyUser(string $username,string $password): string
    {
        if (is_null($username) || is_null($password)) {
            throw new \Exception("verifyUser Step - Username and password must be passed in", 1);
        }

        $accessURL = "realms/" . static::$ZeroTrustConfig->realm . "/" . static::$keycloakAccessUrlMap["tokenAuth"];
        $action = (new Action(
            static::$ZeroTrustConfig->host,
            "POST",
            $accessURL
        ))
        ->addOption("form_params",[
            'grant_type'    => static::$keycloakGrantType["password"],
            'client_id'     => static::$ZeroTrustConfig->gatewayClientID,
            'client_secret' => static::$ZeroTrustConfig->gatewayClientSecret,
            'username'      => $username,
            'password'      => $password
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        })->failHandler(static::errorHandler());

        $data = $action->do()->getMeaningData();
        if (isset($data["error"])) {
            throw new \Exception("verifyUser Step - KeyCloak Access Error : {$data['error']}, Error_description : {$data['error_description']}", 1);
        }

        if (isset($data["code"])) {
            throw new \Exception("verifyUser Step - Error : HTTP Code {$data['code']}, Error_description : {$data['msg']}", 1);
        }

        if (isset($data["access_token"]) && !empty($data["access_token"])) {
            return $data["access_token"];
        }
        
        throw new \Exception("verifyUser Step - AnserGateway - ZeroTrust Unknown Error , method name : verifyUser", 1);
    }

    /**
     * introspect
     *
     * @param string $access_token
     * @return array
     */
    public static function introspect(string $access_token): array
    {
        if (is_null($access_token)) {
            throw new \Exception("introspect Step - Access_token must be passed in", 1);
        }
        $accessURL = "realms/" . static::$ZeroTrustConfig->realm . "/" . static::$keycloakAccessUrlMap["introspect"];
        $action = (new Action(
            static::$ZeroTrustConfig->host,
            "POST",
            $accessURL
        ))
        ->addOption("form_params",[
            'token'         => $access_token,
            'client_id'     => static::$ZeroTrustConfig->gatewayClientID,
            'client_secret' => static::$ZeroTrustConfig->gatewayClientSecret,
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        })->failHandler(static::errorHandler());

        $data = $action->do()->getMeaningData();

        if (isset($data["active"]) && ($data["active"] == false)) {
            throw new \Exception("introspect Step - KeyCloak Access Error : Active = false", 1);
        }

        if (isset($data["code"])) {
            throw new \Exception("introspect Step - Error : HTTP Code {$data['code']}, Error_description : {$data['msg']}", 1);
        }

        if (isset($data["resource_access"]) && !empty($data["resource_access"])) {
            return array_filter($data["resource_access"], function($key) {
                return strpos($key, 'ansergateway') === 0;
            }, ARRAY_FILTER_USE_KEY);
        }
        
        throw new \Exception("introspect Step - AnserGateway - ZeroTrust Unknown Error , method name : introspect", 1);
    }

    /**
     * 驗證是否有當前訪問的端點存在於keycloak
     *
     * @param Request $request
     * @param string $accessToken
     */
    public static function verifyEndpoint(Request $request,string $customClientAccessToken, string $serviceName)
    {
        if (!isset(static::$ZeroTrustConfig->serviceClientRelation[$serviceName]) || is_null(static::$ZeroTrustConfig->serviceClientRelation[$serviceName])) {
            throw new \Exception("verifyEndpoint Step - {$serviceName} Service client error: Please check if your .env zerotrust.serviceClientRelation has been set up", 1);
        } 

        if (!array_key_exists(static::$ZeroTrustConfig->serviceClientRelation[$serviceName],static::$resourcesUUIDArray)) {
            throw new \Exception("verifyEndpoint Step - {$serviceName} Service client error: Please check if your .env zerotrust.customClient has been set up", 1);
        }
        
        if (is_null($customClientAccessToken)) {
            throw new \Exception("verifyEndpoint Step - {$serviceName} Service client error: Can not access this service in Keycloak.", 1);
        }

        $accessURL = "realms/" . static::$ZeroTrustConfig->realm . "/" . static::$keycloakAccessUrlMap["resourceSet"];

        $action = (new Action(
            static::$ZeroTrustConfig->host,
            "GET",
            $accessURL
        ))
        ->addOption("headers",[
            'Authorization' => "Bearer {$customClientAccessToken}",
            'Content-Type'  => "application/json",
        ])
        ->addOption("query",[
            'name' => $request->path(),
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        })->failHandler(static::errorHandler());

        $data = $action->do()->getMeaningData();

        if (isset($data["error"])) {
            throw new \Exception("verifyEndpoint Step - KeyCloak Access Error : {$data['error']}, Error_description : {$data['error_description']}", 1);
        }

        if (isset($data["code"])) {
            throw new \Exception("verifyEndpoint Step - Error : HTTP Code {$data['code']}, Error_description : {$data['msg']}", 1);
        }

        // 預防原有UUIDs陣列不存在
        if (count(static::$resourcesUUIDArray) == 0) {
            throw new \Exception("verifyEndpoint Step - Error : Resources not found, please check your Keycloak.", 1);
        }
        foreach (static::$resourcesUUIDArray as $resourceName => $UUIDs) {
            // 當前resourcesUUIDArray為空就pass下一輪
            if (count($UUIDs) == 0) {
                continue;
            }
            // 若在當前陣列中則直接回傳true，表示命中
            if (in_array($data[0], $UUIDs)) {
                return $data[0];
            }
        }

        throw new \Exception("verifyEndpoint Step - Error : Resource-{$data[0]} not found , method name : verifyEndpoint", 1);
    }

    /**
     *  驗證serviceName是否存在，並向keycloak取得當前該service clientID的access token
     *
     * @param string $serviceName (userservice)
     * @return void
     */
    public static function getCustomClientTokenByServiceName($serviceName): string
    {
        $accessURL = "realms/" . static::$ZeroTrustConfig->realm . "/" . static::$keycloakAccessUrlMap["tokenAuth"];
        $serviceClient = static::$ZeroTrustConfig->customClient[static::$ZeroTrustConfig->serviceClientRelation[$serviceName]];
        $action = (new Action(
            static::$ZeroTrustConfig->host,
            "POST",
            $accessURL
        ))
        ->addOption("form_params",[
            'grant_type'    => static::$keycloakGrantType["clientCredentials"],
            'client_id'     => $serviceClient["clientID"],
            'client_secret' => $serviceClient["clientSecret"],
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        })->failHandler(static::errorHandler());

        $data = $action->do()->getMeaningData();

        if (isset($data["error"])) {
            throw new \Exception("getCustomClientTokenByServiceName Step - KeyCloak Access Error : {$data['error']}, Error_description : {$data['error_description']}", 1);
        }

        if (isset($data["code"])) {
            throw new \Exception("getCustomClientTokenByServiceName Step - Error : HTTP Code {$data['code']}, Error_description : {$data['msg']}", 1);
        }

        if (isset($data["access_token"]) && !empty($data["access_token"])) {
            return $data["access_token"];
        }
        
        throw new \Exception("getCustomClientTokenByServiceName Step - AnserGateway - ZeroTrust Unknown Error , method name : getCustomClientTokenByServiceName", 1);
    }

    /**
     * Undocumented function
     *
     * @param string $UUID
     * @return void
     */
    public static function getresourceSetByUUID($UUID, $customClientAccessToken)
    {
        $accessURL = "realms/" . static::$ZeroTrustConfig->realm . "/" . static::$keycloakAccessUrlMap["resourceSet"] . "/{$UUID}";

        $action = (new Action(
            static::$ZeroTrustConfig->host,
            "GET",
            $accessURL
        ))
        ->addOption("headers",[
            'Authorization' => "Bearer {$customClientAccessToken}",
            'Content-Type'  => "application/json",
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        })->failHandler(static::errorHandler());

        $data = $action->do()->getMeaningData();

        if (isset($data["error"])) {
            throw new \Exception("getresourceSetByUUID Step - KeyCloak Access Error : {$data['error']}, Error_description : {$data['error_description']}", 1);
        }

        if (isset($data["code"])) {
            throw new \Exception("getresourceSetByUUID Step - Error : HTTP Code {$data['code']}, Error_description : {$data['msg']}", 1);
        }

        if (isset($data['scopes'])) {
            return $data['scopes'];
        }
        throw new \Exception("getresourceSetByUUID Step - AnserGateway - ZeroTrust Unknown Error , method name : getresourceSetByUUID", 1);
    }

    /**
     * 請求 permission 取得ticket
     *
     * @param string $gatewayAccessToken
     * @param string $resourceID
     * @param array $resourceScopes
     * @param string $requestSerivceAction (對應請求scope)
     * @return string
     */
    public static function permission(string $customClientAccessToken, string $resourceID, array $resourceScopes, string $requestSerivceAction): string
    {

        if (!static::verifyScopose($resourceScopes,$requestSerivceAction)) {
            throw new \Exception("permission Step - Scope - '{$requestSerivceAction}' Not Found", 1);
        }

        $accessURL = "realms/" . static::$ZeroTrustConfig->realm . "/" . static::$keycloakAccessUrlMap["permission"];

        $action = (new Action(
            static::$ZeroTrustConfig->host,
            "POST",
            $accessURL
        ))
        ->addOption("headers",[
            'Authorization' => "Bearer {$customClientAccessToken}",
            'Content-Type'  => "application/json",
        ])
        ->addOption("json",[
            [
                'resource_id' => $resourceID,
                'resource_scopes'  => [$requestSerivceAction],  // "[GET]"
            ]
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        })->failHandler(static::errorHandler());
        $data = $action->do()->getMeaningData();
        
        if (isset($data["error"])) {
            throw new \Exception("permission Step - KeyCloak Access Error : {$data['error']}, Error_description : {$data['error_description']}", 1);
        }

        if (isset($data["code"])) {
            throw new \Exception("permission Step - Error : HTTP Code {$data['code']}, Error_description : {$data['msg']}", 1);
        }

        if (isset($data['ticket'])) {
            return $data['ticket'];
        }
        throw new \Exception("permission Step - AnserGateway - ZeroTrust Unknown Error , method name : permission", 1);
    }

    /**
     * 比對scope規則，若使用者帶入GET請求則比對scope是否有GET
     *
     * @param array $scopes
     * @param string $requestSerivceAction (GET, POST, PUT, DELETE)
     * @return bool
     */
    public static function verifyScopose(array $scopes, string $requestSerivceAction): bool
    {
        $names = array_column($scopes, 'name');

        if (in_array($requestSerivceAction, $names)) {
            return true;
        } 
        return false;
    }
}

?>