<?php

namespace Test\Support\Controllers;

use App\Controllers\BaseController;
use Workerman\Protocols\Http\Response;

class InvalidController
{
    public function index()
    {
        $res = json_encode([
            "status" => 200,
            "msg" => "index method"
        ]);
    }
}
