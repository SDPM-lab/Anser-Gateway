<?php 
namespace App\Controllers;

use AnserGateway\Controller;
use Exception;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use AnserGateway\ServiceDiscovery\LoadBalance\LoadBalance;
use AnserGateway\HTTPConnectionManager;
use GuzzleHttp\Psr7\Uri;

abstract class ProxyController extends Controller
{
    /**
     * 服務名稱
     *
     * @var string
     */
    protected $serviceName;

    /**
     * 失敗重試次數
     *
     * @var integer
     */
    protected $retry = 0;

    /**
     * 服務請求逾時時間
     *
     * @var float
     */
    protected $timeout = 2.0;

    protected $loadBalanceStrategy = 'random';

    /**
     * Instance of the main Request object.
     *
     * @var Request
     */
    protected $request;

    protected $preResponse;

    /**
     * 注入Request 與 Response
     */
    /**
     * Constructor.
     */
    public function initController(Request $request, Response $response)
    {
        parent::initController($request, $response);
        LoadBalance::setStrategy($this->loadBalanceStrategy);
        $this->proxyIn();
        $this->proxyOut();
    }

    protected function proxyIn()
    {
        $timeout = $this->timeout;
        $serviceInfo = \Config\IPTable::getServiceData($this->serviceName);

        if (is_null($serviceInfo['port'])) {
            $serviceInfo['port'] = $serviceInfo['scheme'] == 'http' ? 80 : 443;
        }
        var_dump($this->request->uri());
        $preUri = $this->request->uri();
        if (strpos($preUri, "/") === 0) {
            $preUri = substr($preUri, strlen("/"));
        }

        if (strpos($preUri, $this->serviceName) === 0) {
            $preUri = substr($preUri, strlen($this->serviceName));
        }
        
       // Todo : Workerman request Transfer to PSR7
        $psr7ProxyRequest = new \GuzzleHttp\Psr7\ServerRequest(
            $this->request->method(),
            "/users",
            $this->request->header(),
            $this->request->rawBody(),
            $this->request->protocolVersion()
        );

        try {
            $swowResponse = HTTPConnectionManager::useConnection(
                $serviceInfo['address'],
                $serviceInfo['port'],
                static function (\Swow\Psr7\Client\Client $client) use ($psr7ProxyRequest,$timeout): \Psr\Http\Message\ResponseInterface {
                    $swowResponse = $client->setTimeout((int)$timeout * 1000)->sendRequest($psr7ProxyRequest);
                    return $swowResponse;
                }
            );
        } catch (\Exception $exception) {
            throw $exception;
        }

        $this->preResponse = $swowResponse;
        return;
    }

    public function proxyOut()
    {
        $this->response->withStatus($this->preResponse->getStatusCode());
        $this->response->withHeaders($this->preResponse->getHeaders());
        $this->response->withBody($this->preResponse->getBody());
    }
}

?>