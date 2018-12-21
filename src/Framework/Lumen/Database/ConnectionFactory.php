<?php

namespace Vtiful\Framework\Lumen\Database;

use InvalidArgumentException;
use Illuminate\Database\Connection;
use Vtiful\Framework\Lumen\Database\Swoole\MySQLConnection;
use Vtiful\Framework\Lumen\Database\Swoole\CoroutineConnector;
use Illuminate\Database\Connectors\ConnectionFactory as IlluminateConnectionFactory;

class ConnectionFactory extends IlluminateConnectionFactory
{
    /**
     * Create Single Connection
     *
     * @param array $config
     *
     * @return Connection|mixed|MySQLConnection
     *
     * @throws \Vtiful\Framework\Lumen\Exception\ConnectionException
     */
    protected function createSingleConnection(array $config)
    {
        $pdo = $this->createConnector($config)->connect($config);

        return $this->createSwooleConnection(
            $config['driver'], $pdo, $config['database'], $config['prefix'], $config
        );
    }

    /**
     * Create Connector
     *
     * @param array $config
     *
     * @return \Illuminate\Database\Connectors\ConnectorInterface|mixed|CoroutineConnector
     */
    public function createConnector(array $config)
    {
        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        if ($this->container->bound($key = "db.connector.{$config['driver']}")) {
            return $this->container->make($key);
        }

        switch ($config['driver']) {
            case 'swoole':
                return new CoroutineConnector();
        }

        return parent::createConnector($config);
    }

    /**
     * Create Swoole Connection
     *
     * @param        $driver
     * @param        $connection
     * @param        $database
     * @param string $prefix
     * @param array  $config
     *
     * @return Connection|mixed|MySQLConnection
     */
    protected function createSwooleConnection(string $driver, $connection, $database, $prefix = '', array $config = [])
    {
        if (method_exists(Connection::class, 'getResolver')) {
            if ($resolver = Connection::getResolver($driver)) {
                return $resolver($connection, $database, $prefix, $config);
            }
        } else {
            if ($this->container->bound($key = "db.connection.{$driver}")) {
                return $this->container->make($key, [$connection, $database, $prefix, $config]);
            }
        }

        switch ($driver) {
            case 'swoole':
                return new MySQLConnection($connection, $database, $prefix, $config);
        }

        return parent::createConnection($driver, $connection, $database, $prefix, $config);
    }
}