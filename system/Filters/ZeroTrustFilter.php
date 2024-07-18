<?php

namespace AnserGateway\Filters;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

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
        // ToDo
        // 1. When request in, it will verify account and pwd to login or get token. (call login api)
        // 2. Get introspect data after get token . (call introspect api)
        // 3. 組裝token , (typ + jti)
        // 4. (typ + jti) 裝進header
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
       
    }
}
