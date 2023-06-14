<?php

namespace App\Controllers\V1;

use App\Controllers\BaseController;

class TestController extends BaseController
{
    public function index()
    {
        $body = json_encode(
            [
                "asd"=>"V1 Controller"
            ]
        );
        return $this->response->withStatus(200)->withBody($body);
    }
}
