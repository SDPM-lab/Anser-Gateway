<?php

namespace AnserGateway\Router;

interface RouteCollectionInterface
{
    public static function loadRoutes();
    public function addRoute($httpMethod, $route, $remixHandler);

    public function group(string $prefix, ...$params);

    public function get(string $route, mixed $handler, array $options = []);

    public function post(string $route, mixed $handler, array $options = []);

    public function put(string $route, mixed $handler, array $options = []);

    public function delete(string $route, mixed $handler, array $options = []);

    public function patch(string $route, mixed $handler, array $options = []);

    public function head(string $route, mixed $handler, array $options = []);

    public function options(string $route, mixed $handler, array $options = []);

    public function getData();
}
