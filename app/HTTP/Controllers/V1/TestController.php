<?php

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use SDPMlab\Anser\Service\Action;
use Psr\Http\Message\ResponseInterface;

class TestController extends BaseController
{
    public function index()
    {
        // $body = json_encode(
        //     [
        //         "asd"=>"V1 Controller"
        //     ]
        // );
        // return $this->response->withStatus(200)->withBody($body);
        $action = (new Action(
            serviceName: "Product-Service1",
            method: "GET",
            path: "/api/v1/products/1"
        ))->doneHandler(static function(
            ResponseInterface $response,
            Action $runtimeAction
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data['data']);
        });
        $data = $action->do()->getMeaningData();
        return $this->response->withStatus(200)->withBody(json_encode($data));
    }

    public function order()
    {
        $action = (new Action(
            serviceName: "Order-Service1",
            method: "GET",
            path: "/api/v1/order"
        ))->addOption("headers", [
            "X-User-key" => 1
        ])->doneHandler(static function(
            ResponseInterface $response,
            Action $runtimeAction
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data['data']);
        });
        $data = $action->do()->getMeaningData();
        return $this->response->withStatus(200)->withBody(json_encode($data));
    }
    
}
