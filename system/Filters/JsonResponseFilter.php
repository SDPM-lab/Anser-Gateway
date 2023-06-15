<?php

namespace AnserGateway\Filters;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

class JsonResponseFilter implements FilterInterface
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
        # 將response header 加上Content-Type => application/json charset=utf-8
        $response->withHeader('Content-Type', 'application/json charset=utf-8');

        return $response;
    }
}
