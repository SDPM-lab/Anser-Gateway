<?php

namespace App\Filters;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use AnserGateway\Filters\FilterInterface;

class TestFilter implements FilterInterface
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
        //
        var_dump("Test before");
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
        //
        var_dump("Test after");
    }

}
