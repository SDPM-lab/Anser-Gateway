<?php

namespace AnserGateway\Exception;

use Throwable;
use AnserGateway\Exception\AnserGatewayExceptionInterface;

class AnserGatewayException extends \Exception implements AnserGatewayExceptionInterface
{
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
