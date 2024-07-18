<?php

namespace Test\Support\Filters;

use AnserGateway\Filters\JsonResponseFilter;
use Test\Support\Filters\Filters\TestFilter;
use Test\Support\Filters\Filters\TestFilter2;
use Test\Support\Filters\Filters\GlobalFilter;
use Test\Support\Filters\Filters\UnImplementFilter;
use Test\Support\Filters\Filters\LetResponse;

class Filters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     */
    public array $aliases = [
        'jsonResponse' => JsonResponseFilter::class,
        'test' => TestFilter::class,
        'test2' => TestFilter2::class,
        'global' => GlobalFilter::class,
        'unImplementFilter' => UnImplementFilter::class,
        'letResponse' => LetResponse::class,
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     */
    public array $globals = [
        'before' => [
            'global',
            'test' => ['except' => 'unExcept/*'],
        ],
        'after' => [
            'jsonResponse'
        ],
    ];

}
