<?php

namespace Test\Support\Filters\Filters;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use AnserGateway\Filters\FilterInterface;

class LetResponse implements FilterInterface
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

        $response = new Response(
            200,
            [
                'Content-Type' => 'application/json charset=utf-8',
            ],
            json_encode([
                'code' => 200,
                'msg'  => "success",

            ])
        );

        return $response;

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
