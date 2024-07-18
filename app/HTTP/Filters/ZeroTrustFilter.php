<?php

namespace App\Filters;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use AnserGateway\Filters\FilterInterface;
use AnserGateway\ZeroTrust\ZeroTrust;

class ZeroTrustFilter implements FilterInterface
{
    /**
     *
     * @param Request    $request
     * @param array|null $arguments
     *
     * @return void
     */
    public function before(Request $request, $arguments = null)
    {
        $postData = $request->post();
      
        $data = ZeroTrust::verifyProcess($postData['username'], $postData['password'], $postData['serviceName'], $request, $postData['requestAction']);
        $request->accessToken = $data["customClientAccessToken"];
        $request->ticket      = $data["ticket"];
        $request->username    = $postData['username'];
        $request->password    = $postData['password'];
    }

    /**
     *
     * @param Request    $request
     * @param Response   $response
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function after(Request $request, Response $response, $arguments = null)
    {
        // $result = $response->rawBody();
        // $decode = json_decode($result);
        // $decode->asd = "TestFilter2 after";
        // return $response->withBody(json_encode($decode));
        // var_dump("TestFilter2 after");
    }

}