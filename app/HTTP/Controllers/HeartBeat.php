<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Workerman\Protocols\Http\Response;

class HeartBeat extends BaseController
{
    /**
     * method for Get
     *
     * @return  Response
     */
    public function index()
    {
        $res = json_encode([
            "status" => 200,
            "msg" => "AnserGateway is alive."
        ]);

        return $this->response->withStatus(200)->withBody($res);
    }
}