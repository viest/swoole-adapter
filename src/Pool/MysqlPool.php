<?php

namespace Vtiful\Pool;

use Swoole\Coroutine\MySQL;
use Swoole\Coroutine\Channel;
use Vtiful\Framework\Lumen\Exception\ConnectionException;

/**
 * Class MysqlPool
 *
 * @package Vtiful\Pool
 */
class MysqlPool
{
    /**
     * @var MysqlPool
     */
    protected static $instance;

    protected $config;

    protected $pool;

    /**
     * Get Pool Instance
     *
     * @param array $config
     *
     * @return MysqlPool
     * @throws ConnectionException
     */
    public static function init(array $config)
    {
        self::$instance = new self($config);
    }

    /**
     * Get Pool Instance
     *
     * @return MysqlPool
     * @throws \Exception
     */
    public static function getInstance()
    {
        if (self::$instance === NULL) {
            //throw new \Exception('Pool not init');
        }

        return self::$instance;
    }

    /**
     * Recovery Connection
     *
     * @param MySQL $connection
     */
    public function recovery(MySQL $connection)
    {
        if ($connection->connected) {
            $this->pool->push($connection);
        }
    }

    /**
     * Get Connection
     *
     * @return MySQL
     */
    public function connection()
    {
        $connect = $this->pool->pop();

        defer(function () use ($connect) {
            MysqlPool::recovery($connect);
        });

        return $connect;
    }

    /**
     * MysqlPool constructor.
     *
     * @param array $config
     *
     * @throws ConnectionException
     */
    private function __construct(array $config)
    {
        $this->config = $config;
        $poolDepth    = $config['pool_depth'] ?? 50;

        if ($this->pool === NULL) {
            $this->pool = new Channel($poolDepth);

            for ($count = 0; $count < $poolDepth; $count++) {
                $this->pool->push($this->createConnection());
            }
        }
    }

    /**
     * Create Connection
     *
     * @param array $config
     *
     * @return MySQL
     * @throws ConnectionException
     */
    public function createConnection($custom = false)
    {
        $connection = new MySQL();

        $connection->connect($this->config);

        if (!object_get($connection, 'connected') || $connection === NULL) {
            $message = sprintf(
                'Cannot connect to the database: %s', object_get($connection, 'connect_error')
            );

            throw new ConnectionException($message, object_get($connection, 'connect_errno'));
        }

        if ($custom) {
            defer(function () use ($connection) {
                MysqlPool::recovery($connection);
            });
        }

        return $connection;
    }
}