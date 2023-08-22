<?php

use PHPUnit\Framework\TestCase;

class CommonTest extends TestCase
{
    protected $anserGatewayLogger;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * 測試 log_message 方法
     *
     * @return void
     */
    public function testLogMessage()
    {
        $this->assertTrue(log_message("debug", "debug log test"));
        $this->assertTrue(log_message('info', "info log test"));
        $this->assertTrue(log_message('notice', "notice log test"));
        $this->assertTrue(log_message('warning', "warning log test"));
        $this->assertTrue(log_message('error', "error log test"));
        $this->assertTrue(log_message('critical', "critical log test"));
        $this->assertTrue(log_message('alert', "alert log test"));
        $this->assertTrue(log_message('emergency', "emergency log test"));
    }

}
