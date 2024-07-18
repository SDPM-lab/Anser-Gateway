<?php

namespace AnserGateway\Log;

use Monolog\Level;
use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Formatter\LineFormatter;
use AnserGateway\Log\Exception\LogException;

class Logger
{
    /**
     * LOG Level
     */
    private const LOG_LEVEL = [
        "debug"     => Level::Debug,
        "info"      => Level::Info,
        "notice"    => Level::Notice,
        "warning"   => Level::Warning,
        "error"     => Level::Error,
        "critical"  => Level::Critical,
        "alert"     => Level::Alert,
        "emergency" => Level::Emergency,
    ];

    /**
     * monolog 實體
     *
     * @var \Monolog\Logger
     */
    protected $monoLogger;

    /**
     * 初始化log元件
     */
    public function __construct()
    {
        $logFile    = PROJECT_WRITABLE . "logs" . DIRECTORY_SEPARATOR . "log-" .date("Y-m-d") . ".log";
        $dateFormat = "Y-m-d H:i:s";
        $output     = "%datetime% %channel%[%level_name%]  %message% %context% %extra%\n";

        $formatter = new LineFormatter($output, $dateFormat);
        $stream    = new StreamHandler($logFile);
        $stream->setFormatter($formatter);

        $this->monoLogger = new MonoLogger('AnserGateway');
        $this->monoLogger->pushHandler($stream);
        $this->monoLogger->pushHandler(new FirePHPHandler());
    }

    /**
     * 回傳monolog實體
     *
     * @return \Monolog\Logger
     */
    public function getMonoLogInstance(): \Monolog\Logger
    {
        return $this->monoLogger;
    }

    /**
     * log_message對應之方法
     *
     * 該方法亦可被全域使用
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function log(string $level, string|\Stringable $message, array $context = []): bool
    {
        if (array_key_exists($level, self::LOG_LEVEL)) {
            $level = self::LOG_LEVEL[$level];
        } else {
            throw LogException::forLogLevelInvalid($level);
        }

        return $this->monoLogger->addRecord($level, (string) $message, $context);
    }

    /**
     * DEBUG level.
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function debug(string|\Stringable $message, array $context = []): bool
    {
        return $this->log("debug", (string) $message, $context);
    }

    /**
     * INFO level.
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function info(string|\Stringable $message, array $context = []): bool
    {
        return $this->log("info", (string) $message, $context);
    }

    /**
     * Adds a log record at the NOTICE level.
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function notice(string|\Stringable $message, array $context = []): bool
    {
        return $this->log("notice", (string) $message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function warning(string|\Stringable $message, array $context = []): bool
    {
        return $this->log("warning", (string) $message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function error(string|\Stringable $message, array $context = []): bool
    {
        return $this->log("error", (string) $message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function critical(string|\Stringable $message, array $context = []): bool
    {
        return $this->log("critical", (string) $message, $context);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function alert(string|\Stringable $message, array $context = []): bool
    {
        return $this->log("alert", (string) $message, $context);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function emergency(string|\Stringable $message, array $context = []): bool
    {
        return $this->log("emergency", (string) $message, $context);
    }
}
