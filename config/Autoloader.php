<?php

namespace Config;

class Autoloader
{
    /**
     * Autoload at after worker start
     */
    public array $appNamespaces = [
        'App' => PROJECT_APP,
        'Test' => PROJECT_TEST,
    ];

    /**
     * Autoload at before worker start
     *
     * @var array
     */
    public array $systemNamespaces = [
        'AnserGateway' => PROJECT_SYSTEM,
        'Config' => PROJECT_CONFIG,
        'Workerman' => PROJECT_VENDOR . 'workerman' . DIRECTORY_SEPARATOR . 'workerman' . DIRECTORY_SEPARATOR ,
        'Swow\\Utils' => PROJECT_VENDOR . 'swow' . DIRECTORY_SEPARATOR . 'swow' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'swow-utils' . DIRECTORY_SEPARATOR . 'src',
        'Swow' => PROJECT_VENDOR . 'swow' . DIRECTORY_SEPARATOR . 'swow' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'swow-library' . DIRECTORY_SEPARATOR . 'src',
        'App\\Controllers' => PROJECT_APP . DIRECTORY_SEPARATOR . 'HTTP' . DIRECTORY_SEPARATOR . 'Controllers',
        'App\\Filters' => PROJECT_APP . DIRECTORY_SEPARATOR . 'HTTP' . DIRECTORY_SEPARATOR . 'Filters',
        'Test' => PROJECT_TEST,
        'Test\\Support' => PROJECT_TEST . DIRECTORY_SEPARATOR . '_support',
    ];

}
