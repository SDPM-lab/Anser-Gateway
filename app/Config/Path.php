<?php
namespace App\Config;

class Path
{
    /**
     * AnserGateway System directory
     *
     * @var string
     */
    public string $systemDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR;

    /**
     * AnserGateway App directory
     */
    public string $appDirectory = __DIR__ . DIRECTORY_SEPARATOR . '/..';

}
