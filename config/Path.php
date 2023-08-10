<?php

namespace Config;

class Path
{
    /**
     * AnserGateway System directory
     *
     * @var string
     */
    public string $systemDirectory = PROJECT_ROOT . 'system';

    /**
     * AnserGateway App directory
     */
    public string $appDirectory = PROJECT_ROOT . 'app';

    /**
     * AnserGateway Test directory
     */
    public string $testDirectory = PROJECT_ROOT . 'tests';

}
