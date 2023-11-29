<?php

namespace App\Controllers;

use App\Controllers\ProxyController;
use Workerman\Protocols\Http\Response;

class ProxyControllerExample extends ProxyController
{

    protected $serviceName = "ProductService";

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
    
    /**
     * method for Get
     *
     * @return  Response
     */
    public function index()
    {
        return $this->response;
    }
}