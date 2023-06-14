<?php

namespace AnserGateway;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

class Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var Request
     */
    protected $request;

    /**
     * Instance of the main response object.
     *
     * @var Response
     */
    protected $response;

    /**
     *
     *
     * @var string|null
     */
    protected $responseContentType = null;
    /**
     * Instance of logger to use.
     *
     * @var
     */
    protected $logger;

    public function initController(Request $request, Response $response)
    {
        $this->request  = $request;
        $this->response = $response;
    }
}
