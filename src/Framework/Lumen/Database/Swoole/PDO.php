<?php

namespace Vtiful\Framework\Lumen\Database\Swoole;

use PDO as CorePDO;
use \Swoole\Coroutine\Channel;
use Illuminate\Database\QueryException;
use Swoole\Coroutine\MySQL as SwooleMySQL;
use Vtiful\Framework\Lumen\Exception\ConnectionException;
use Vtiful\Framework\Lumen\Exception\StatementException;

class PDO extends CorePDO
{
    /**
     * @var Channel
     */
    protected static $pool;

    /**
     * @var array
     */
    protected static $config;

    /**
     * @var SwooleMySQL
     */
    protected $currentConnect;

    /**
     * @var bool
     */
    protected $isInTransaction = false;

    /**
     * PDO constructor.
     */
    public function __construct()
    {
        // Empty
    }

    /**
     * PDO destruct
     */
    public function __destruct()
    {
        if (self::$pool->isFull()) {
            $this->currentConnect->close();

            return;
        }

        self::$pool->push($this->currentConnect);
    }

    /**
     * Connect
     *
     * @param array $config
     *
     * @throws ConnectionException
     */
    public function connect(array $config)
    {
        if (self::$pool === NULL) {
            $this->initPool($config);
        }

        self::$config = $config;

        $this->currentConnect = $this->getConnect();
    }

    /**
     * Init Pool
     *
     * @param array $config
     *
     * @throws ConnectionException
     */
    protected function initPool(array $config)
    {
        self::$pool = new Channel(100);

        for ($count = 0; $count < 100; $count++) {
            self::$pool->push($this->createConnection($config));
        }
    }

    /**
     * Create Connection
     *
     * @param array $config
     *
     * @return SwooleMySQL
     * @throws ConnectionException
     */
    protected function createConnection(array $config)
    {
        $connection = new SwooleMySQL();

        $connection->connect($config);

        if (!object_get($connection, 'connected') || $connection === NULL) {
            $msg = sprintf(
                'Cannot connect to the database: %s',
                object_get($connection, 'connect_error')
            );

            throw new ConnectionException(
                $msg, object_get($connection, 'connect_errno')
            );
        }

        return $connection;
    }

    /**
     * Get Connection
     *
     * @return SwooleMySQL
     * @throws ConnectionException
     */
    protected function getConnect()
    {
        if (self::$pool->isEmpty()) {
            return $this->createConnection(self::$config);
        }

        return self::$pool->pop();
    }

    /**
     * Prepare
     *
     * @param string $statement
     * @param array  $options
     *
     * @return bool|\PDOStatement|PDOStatement
     * @throws ConnectionException
     */
    public function prepare($statement, $options = NULL)
    {
        $swStatement = $this->currentConnect->prepare($statement);

        if (!$swStatement) {
            $errorCode = $this->currentConnect->errno;
            $errorMess = $this->currentConnect->error;

            if (!$this->currentConnect->connected) {
                $this->currentConnect = $this->createConnection(self::$config);
            }

            throw new QueryException(
                $statement, [], new StatementException($errorMess, $errorCode)
            );
        }

        return new PDOStatement($swStatement);
    }

    /**
     * Begin Transaction
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->isInTransaction = true;
        $this->currentConnect->begin();
    }

    /**
     * Commit Transaction
     *
     * @return void
     */
    public function commit()
    {
        $this->currentConnect->commit();
        $this->isInTransaction = false;
    }

    /**
     * Rollback Transaction
     *
     * @return bool|void
     */
    public function rollBack()
    {
        $this->currentConnect->rollback();
        $this->isInTransaction = false;
    }

    /**
     * Query
     *
     * @param string $statement
     * @param int    $mode
     * @param null   $arg3
     * @param array  $ctorargs
     *
     * @return array|bool|\PDOStatement
     * @throws ConnectionException
     */
    public function query($statement, $mode = \PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = [])
    {
        $result = $this->currentConnect->query(
            $statement, array_get($ctorargs, 'timeout', 0.0)
        );

        if (!$result) {
            $errorCode = $this->currentConnect->errno;
            $errorMess = $this->currentConnect->error;

            if (!$this->currentConnect->connected) {
                $this->currentConnect = $this->createConnection(self::$config);
            }

            throw new QueryException(
                $statement, [], new \Exception($errorMess, $errorCode)
            );
        }

        return $result;
    }

    /**
     * Exec
     *
     * @param string $statement
     *
     * @return array|bool|int|\PDOStatement
     * @throws ConnectionException
     */
    public function exec($statement)
    {
        return $this->query($statement);
    }

    /**
     * Last Insert Id
     *
     * @param null $name
     *
     * @return int|string
     */
    public function lastInsertId($name = null)
    {
        return $this->currentConnect->insert_id;
    }

    /**
     * Row Count
     *
     * @return int
     */
    public function rowCount()
    {
        return $this->currentConnect->affected_rows;
    }

    /**
     * Quote
     *
     * @param string $string
     * @param int    $parameter_type
     *
     * @return string
     */
    public function quote($string, $parameter_type = \PDO::PARAM_STR)
    {
        return $string;
    }

    /**
     * Error Code
     *
     * @return int|mixed
     */
    public function errorCode()
    {
        return $this->currentConnect->errno;
    }

    /**
     * Error Info
     *
     * @return array
     */
    public function errorInfo()
    {
        return [
            $this->currentConnect->errno,
            $this->currentConnect->errno,
            $this->currentConnect->error,
        ];
    }

    /**
     * In Transaction
     *
     * @return bool
     */
    public function inTransaction()
    {
        return $this->isInTransaction;
    }

    /**
     * Get Attribute
     *
     * @param int $attribute
     *
     * @return mixed|null
     */
    public function getAttribute($attribute)
    {
        return $this->currentConnect->serverInfo[$attribute] ?? NULL;
    }

    /**
     * Set Attribute
     *
     * @param int   $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function setAttribute($attribute, $value)
    {
        return false;
    }

    /**
     * Get Available Drivers
     *
     * @return array
     */
    public static function getAvailableDrivers()
    {
        return ['mysql'];
    }
}