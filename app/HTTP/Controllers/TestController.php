<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class TestController extends BaseController
{
    public function index()
    {
        // var_dump($this->request->method());

        $body = json_encode(
            [
                "asd"=>123
            ]
        );
        
        return $this->response->withStatus(200)->withBody($body);
    }

    public function show()
    {
        $data = $this->request->rawBody();
        return $this->response->withStatus(200)->withBody($data);
    }
}
