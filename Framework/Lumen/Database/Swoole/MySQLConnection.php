<?php

namespace Vtiful\Framework\Lumen\Database\Swoole;

use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Database\MySqlConnection as IlluminateMysqlConnection;

class MySQLConnection extends IlluminateMysqlConnection
{
    /**
     * The active swoole mysql connection.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * The active swoole mysql used for reads.
     *
     * @var PDO
     */
    protected $readPdo;

    /**
     * Get Driver Name
     *
     * @return string
     */
    public function getDriverName()
    {
        return 'Swoole Coroutine MySQL';
    }

    /**
     * Try Again If Caused By Lost Connection
     *
     * @param QueryException $e
     * @param string         $query
     * @param array          $bindings
     * @param \Closure       $callback
     *
     * @return mixed
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, \Closure $callback)
    {
        if ($this->causedByLostConnection($e->getPrevious()) || Str::contains($e->getMessage(), ['is closed', 'is not established'])) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }
}