<?php

namespace AnserGateway\ZeroTrust;

use SDPMlab\Anser\Service\Action;
use Psr\Http\Message\ResponseInterface;
use SDPMlab\Anser\Exception\ActionException;
use Config\ZeroTrust as ZeroTrustConfig;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak as ZTClient;

use function Swow\Debug\isStrictStringable;

class ZeroTrust{

    private $ztConfig=null;

    private $ztClient=null;

    private $ztToken = null;
    private $ztRefreshToken = null;

    public function __construct()
    {
        $this->ztConfig = new ZeroTrustConfig();
        $this->ztClient = new ZTClient([
            'authServerUrl'         => $this->ztConfig->tokenauthurl,
            'realm'                 => $this->ztConfig->realm,
            'clientId'              => $this->ztConfig->clientID,
            'clientSecret'          => $this->ztConfig->clientSecret,
            'username'              => $this->ztConfig->username,
            'pasword'               => $this->ztConfig->password,
        ]);
    }

    /**
     * Return $ztClient
     *
     * @return ztClient 
     */

    
    public function getZTClient() : ZTClient
    {     
        return $this->ztClient;
    }

    /**
     * Get admin token
     *
     * @return string
     */
    public function getAdminToken($username, $pwd) {

        if (!is_null($this->ztToken) && !is_null($this->ztRefreshToken)) {
            return $this->ztToken;
        }

        $action = (new Action(
            $this->ztConfig->host,
            "POST",
            $this->ztConfig->tokenauthurl
        ))
        ->addOption("form_params",[
            'grant_type'  => $this->ztConfig->grantType,
            'client_id' => $this->ztConfig->clientID,
            'client_secret' => $this->ztConfig->clientSecret,
            'username' => $username,
            'password' => $pwd,
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            var_dump($body);
            var_dump("data=", $data);
            $runtimeAction->setMeaningData($data);
        })->failHandler(function (
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
        });

        $adminToken = $action->do()->getMeaningData();
        $this->ztToken = $adminToken["access_token"];
        $this->ztRefreshToken = $adminToken["refresh_token"];
        return $this->ztToken;
    }

    // 先確定 Login 行為 (method)

    /**
     * Login using env configuration *(admin user)
     *
     * @return bool
     */
    public function checkLogin($username, $pwd) {
        // Call the getAdminToken function to retrieve the admin token
        $adminToken = $this->getAdminToken($username, $pwd);

        // Check if the admin token is null
        if (!is_null($adminToken)) {
            return false;
        }
        // 其他登入判斷 
        return true;
    }

    // 加上舊有使用者 register 行為 (user service 行為)  // 還要擴充 user service
    

    public function verifyToken($token) {
        $action = (new Action(
            $this->ztConfig->host,
            "POST",
            $this->ztConfig->introspecturl
        ))
        ->addOption("form_params",[
            'grant_type'  => $this->ztConfig->grantType,
            'client_id' => $this->ztConfig->clientID,
            'client_secret' => $this->ztConfig->clientSecret,
            'token' => $token,
        ])
        ->doneHandler(function(
            ResponseInterface $response,
            Action $runtimeAction,
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            var_dump($body);
            var_dump("data=", $data);
            $runtimeAction->setMeaningData($data);
        })->failHandler(function (
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
        });

        $bearerTokenData = $action->do()->getMeaningData();

        return $bearerTokenData;
    }

    
}

?>