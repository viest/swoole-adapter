<?php

namespace Vtiful\Framework\Lumen\Database\Swoole;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;

/**
 * Class CoroutineMySQLConnector
 *
 * @package Vtiful\Framework\Lumen\Database
 */
class CoroutineConnector extends Connector implements ConnectorInterface
{
    /**
     * Create Connection
     *
     * @param string $dsn
     * @param array  $config
     * @param array  $options
     *
     * @throws \Throwable
     *
     * @return \PDO|PDO
     */
    public function createConnection($dsn, array $config, array $options)
    {
        try {
            $mysql = $this->connect($config);
        } catch (Exception $exception) {
            $mysql = $this->tryAgainIfCausedByLostConnectionForCoroutineMySQL($exception, $config);
        }

        return $mysql;
    }

    /**
     * Try Again If Caused By Lost Connection For Coroutine MySQL
     *
     * @param Exception $exception
     * @param array     $config
     *
     * @return PDO
     *
     * @throws \Throwable
     */
    protected function tryAgainIfCausedByLostConnectionForCoroutineMySQL(Exception $exception, array $config)
    {
        if (parent::causedByLostConnection($exception) || Str::contains($exception->getMessage(), ['is closed', 'is not established'])) {
            return $this->connect($config);
        }

        throw $exception;
    }

    /**
     * Connection
     *
     * @param array $config
     *
     * @return PDO
     *
     * @throws \Vtiful\Framework\Lumen\Exception\ConnectionException
     */
    public function connect(array $config)
    {
        $connection = new PDO();

        $connection->connect([
            'host'        => Arr::get($config, 'host', '127.0.0.1'),
            'port'        => Arr::get($config, 'port', 3306),
            'user'        => Arr::get($config, 'username', 'root'),
            'password'    => Arr::get($config, 'password', ''),
            'database'    => Arr::get($config, 'database', ''),
            'timeout'     => Arr::get($config, 'timeout', 30),
            'charset'     => Arr::get($config, 'charset', 'utf8mb4'),
            'strict_type' => Arr::get($config, 'strict', false),
        ]);

        if (isset($config['timezone'])) {
            $connection->query('set time_zone="' . $config['timezone'] . '"');
        }

        if (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->query("set session sql_mode='STRICT_ALL_TABLES,ANSI_QUOTES'");
            } else {
                $connection->query("set session sql_mode='ANSI_QUOTES'");
            }
        }

        return $connection;
    }
}