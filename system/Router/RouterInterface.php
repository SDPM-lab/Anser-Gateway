<?php

namespace AnserGateway\Router;

use Workerman\Protocols\Http\Request;

interface RouterInterface
{
    /**
     * 儲存對RouteCollection物件的引用
     *
     * @param RouteCollectionInterface $routes
     * @param Request|null $request
     */
    public function __construct(RouteCollectionInterface $routes);

    /**
     * 找尋與uri對應的Controller方法
     *
     * @param string      $httpMethod 請求方法
     * @param string $uri 相對於baseURL的URI路徑
     *
     */
    public function handle($httpMethod, $uri);

    /**
     * 回傳所有Filter
     *
     * @return array
     */
    public function getFilters();

    /**
     * 回傳配對到的Controller名稱
     *
     * @return Closure|string 回傳一個Controller的名稱或是閉包
     */
    public function getController();

    /**
     * 回傳Controller中要執行的method名稱
     *
     * @return string
     */
    public function getMethod();

    /**
     * 回傳在解析過程中match成功和收集的相關變數，以陣列變數方式傳送至方法中
     * instance->method(...$params)
     *
     * @return array
     */
    public function getParams();

}
