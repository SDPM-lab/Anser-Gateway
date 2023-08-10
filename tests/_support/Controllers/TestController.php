<?php

namespace Test\Support\Controllers;

use App\Controllers\BaseController;
use Workerman\Protocols\Http\Response;

class TestController extends BaseController
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
            "msg" => "index method"
        ]);

        return $this->response->withStatus(200)->withBody($res);
    }

    /**
     * method for Post
     *
     * @return Response
     */
    public function show()
    {
        $res = json_encode([
            "status" => 200,
            "msg" => "show method"
        ]);

        return $this->response->withStatus(200)->withBody($res);
    }

    /**
     * method for put
     *
     * @return Response
     */
    public function update()
    {
        $res = json_encode([
            "status" => 200,
            "msg" => "put method"
        ]);

        return $this->response->withStatus(200)->withBody($res);
    }

    /**
     * method for delete
     *
     * @return Response
     */
    public function delete()
    {
        $res = json_encode([
            "status" => 200,
            "msg" => "delete method"
        ]);

        return $this->response->withStatus(200)->withBody($res);
    }

    public function unWork()
    {

    }

}
