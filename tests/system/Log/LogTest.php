<?php

use PHPUnit\Framework\TestCase;
use AnserGateway\Log\Logger;
use AnserGateway\Log\Exception\LogException;

class LogTest extends TestCase
{
    protected $anserGatewayLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->anserGatewayLogger = new Logger();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * 測試 log 方法
     *
     * @return void
     */
    public function testLog()
    {
        $this->assertTrue($this->anserGatewayLogger->log('debug', "debug log test"));
        $this->assertTrue($this->anserGatewayLogger->log('info', "info log test"));
        $this->assertTrue($this->anserGatewayLogger->log('notice', "notice log test"));
        $this->assertTrue($this->anserGatewayLogger->log('warning', "warning log test"));
        $this->assertTrue($this->anserGatewayLogger->log('error', "error log test"));
        $this->assertTrue($this->anserGatewayLogger->log('critical', "critical log test"));
        $this->assertTrue($this->anserGatewayLogger->log('alert', "alert log test"));
        $this->assertTrue($this->anserGatewayLogger->log('emergency', "emergency log test"));
    }

    /**
     * 測試 log 方法拋出例外
     *
     * @return void
     */
    public function testLogException()
    {
        $unknownLogLevel = "unknownLogLevel";
        $this->expectException(LogException::class);
        $this->expectExceptionMessage("Log Level 錯誤 : {$unknownLogLevel} 不存在，請確認 Log Level 是否為以下其一，[debug, info, notice, warning, error, critical, alert, emergency]");
        $this->anserGatewayLogger->log($unknownLogLevel, "unknownLogLevel log test");
    }

    /**
     * 測試各種level log 方法
     *
     * @return void
     */
    public function testLogTypeMethod()
    {
        $this->assertTrue($this->anserGatewayLogger->debug("debug log test"));
        $this->assertTrue($this->anserGatewayLogger->info("info log test"));
        $this->assertTrue($this->anserGatewayLogger->notice("notice log test"));
        $this->assertTrue($this->anserGatewayLogger->warning("warning log test"));
        $this->assertTrue($this->anserGatewayLogger->error("error log test"));
        $this->assertTrue($this->anserGatewayLogger->critical("critical log test"));
        $this->assertTrue($this->anserGatewayLogger->alert("alert log test"));
        $this->assertTrue($this->anserGatewayLogger->emergency("emergency log test"));
    }

    /**
     * 測試 getMonoLogInstance 方法
     *
     * @return void
     */
    public function testGetMonologInstance()
    {
        $monoLogInstance = $this->anserGatewayLogger->getMonoLogInstance();
        $this->assertInstanceOf("\Monolog\Logger", $monoLogInstance);
    }
}
