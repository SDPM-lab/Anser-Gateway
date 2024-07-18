<?php

namespace AnserGateway;

use Swow\Psr7\Client\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP 連線管理器
 *
 * @version 1.0.0
 * @author MonkenWu <monkenwu@monken.tw>
 */
class HTTPConnectionManager
{
    /**
     * 目前沒有進行傳輸的連線
     *
     * @var array<string,array<Client>>
     */
    private static $idleConnections = [];

    /**
     * 所有的連線
     *
     * @var array<string,array<Client>>
     */
    private static $connections = [];

    private static $getConnectionQueue = [];

    /**
     * 每個 host 最多連線數
     *
     * @var integer
     */
    public static $hostMaxConnectionNum = 100;

    /**
     * 連線忙碌中時等待的時間
     *
     * @var integer microseconds
     */
    public static $waitConnectionTimeout = 100;

    /**
     * 使用 connection
     *
     * @param string $host
     * @param int $port
     * @param callable $callback
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function useConnection(string $host, int $port, callable $callback): ResponseInterface
    {
        $hostName = sprintf("%s:%s", $host, $port);
        $client = self::getConnection($host, $port);
        $response = $callback($client);
        //執行完畢後將 connection 放回 idle
        self::$idleConnections[$hostName][] = $client;
        return $response;
    }

    /**
     * 取得 connection
     *
     * @param string $host
     * @param int $port
     * @return Client
     */
    protected static function getConnection(string $host, int $port)
    {
        $hostName = sprintf("%s:%s", $host, $port);

        if(isset(self::$connections[$hostName]) == false) {
            self::$connections[$hostName] = [];
        }

        if(isset(self::$idleConnections[$hostName]) == false) {
            self::$idleConnections[$hostName] = [];
        }

        $client = self::addNewConnection($host, $port);

        //如果 connection 已經斷線，則重新連線
        if ($client->isAvailable() == false) {
            $client->connect($host, $port);
        }

        return $client;
    }

    /**
     * 新增 connection
     *
     * @param string $host
     * @param int $port
     * @return Client
     */
    protected static function addNewConnection(string $host, int $port): Client
    {
        $hostName = sprintf("%s:%s", $host, $port);

        if(count(self::$connections[$hostName]) >= self::$hostMaxConnectionNum) {
            $uniqueId = sha1(
                uniqid() .
                substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(10/strlen($x)))), 1, 10)
            );
            if(isset(self::$getConnectionQueue[$hostName]) == false) {
                self::$getConnectionQueue[$hostName] = [];
            }
            self::$getConnectionQueue[$hostName][] = $uniqueId;
            while (count(self::$idleConnections[$hostName]) == 0 || self::checkQueue($hostName, $uniqueId) == false) {
                usleep(self::$waitConnectionTimeout);
            }
            self::shiftQueue($hostName);
            return array_pop(self::$idleConnections[$hostName]);
        }

        $client = new \Swow\Psr7\Client\Client();
        $client->connect($host, $port);
        self::$connections[$hostName][] = $client;
        return $client;
    }

    /**
     * 檢查 queue 中是否有此 uniqueId
     *
     * @param string $uniqueId
     * @return integer index
     */
    protected static function checkQueue(string $hostName, string $uniqueId): bool
    {
        if(self::$getConnectionQueue[$hostName][0] == $uniqueId) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 從 queue 中移除 uniqueId
     *
     * @param string $uniqueId
     * @return void
     */
    protected static function shiftQueue(string $hostName)
    {
        array_shift(self::$getConnectionQueue[$hostName]);
    }

    public static function getConnectionInfo()
    {
        $info = [];
        foreach (self::$connections as $hostName => $connections) {
            $info[$hostName] = [
                'total' => count(self::$connections[$hostName]),
                'idle' => count(self::$idleConnections[$hostName]),
                'busy' => count(self::$connections[$hostName]) - count(self::$idleConnections[$hostName]),
                'queue' => count(self::$getConnectionQueue[$hostName] ?? []),
            ];
        }
        return $info;
    }

    public static function closeAllConnections()
    {
        foreach (self::$connections as $hostName => $connections) {
            foreach ($connections as $connection) {
                try {
                    $connection->close();
                } catch (\Exception $e) {
                    //throw $th;
                }
            }
        }
    }

    /**
     * 使用Connection pool 進行請求管理
     *
     * @return callable
     */
    public static function connectionMiddleware()
    {   
        return static function (\GuzzleHttp\Psr7\Request $request, array $options) {
            
            if ($request->getUri()->getPort() === null) {
                $prot = $request->getUri()->getScheme() == 'http' ? 80 : 443;
            }
            try {
                
                $swowResponse = HTTPConnectionManager::useConnection(
                    $request->getUri()->getHost(),
                    $prot ?? $request->getUri()->getPort(),
                    // 443,
                    static function (\Swow\Psr7\Client\Client $client) use ($request, $options): \Psr\Http\Message\ResponseInterface {

                        $swowResponse = $client->setTimeout(10000)->sendRequest($request);
   
                        dump("use HTTP Connection manager!");
                        return $swowResponse;
                    }
                );

            } catch (\Exception $exception) {
                error_log($exception . PHP_EOL, 3, "./error.log");
                // dump($exception);
                throw $exception;
            }

            $response = new \GuzzleHttp\Psr7\Response(
                $swowResponse->getStatusCode(),
                $swowResponse->getHeaders(),
                $swowResponse->getBody()->getContents(),
                $swowResponse->getProtocolVersion(),
                $swowResponse->getReasonPhrase()
            );

            return \GuzzleHttp\Promise\Create::promiseFor($response);
        };
    }
}