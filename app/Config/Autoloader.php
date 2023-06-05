<?php
namespace App\Config;

class Autoloader
{
    /**
     * Autoload at after worker start
     */
    public array $appNamespaces = [
        'App' => PROJECT_APP,
    ];

    /**
     * Autoload at before worker start
     *
     * @var array
     */
    public array $systemNamespaces = [
        'AnserGateway' => PROJECT_SYSTEM,
        'App\\Config' => PROJECT_CONFIG,
        'Workerman' => PROJECT_VENDOR . 'workerman' . DIRECTORY_SEPARATOR . 'workerman' . DIRECTORY_SEPARATOR ,
        'Swow\\Utils' => PROJECT_VENDOR . 'swow' . DIRECTORY_SEPARATOR . 'swow' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'swow-utils' . DIRECTORY_SEPARATOR . 'src',
        'Swow' => PROJECT_VENDOR . 'swow' . DIRECTORY_SEPARATOR . 'swow' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'swow-library' . DIRECTORY_SEPARATOR . 'src',
    ];

}
