<?php

namespace App\Controllers;

use AnserGateway\Controller;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var Request
     */
    protected $request;

    /**
     * 注入Request 與 Response
     */
    /**
     * Constructor.
     */
    public function initController(Request $request, Response $response)
    {
        parent::initController($request, $response);
    }
}
