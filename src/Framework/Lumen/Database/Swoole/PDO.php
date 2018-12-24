<?php

namespace Vtiful\Framework\Lumen\Database\Swoole;

use PDO as CorePDO;
use Vtiful\Pool\MysqlPool;
use Illuminate\Database\QueryException;
use Swoole\Coroutine\MySQL as SwooleMySQL;
use Vtiful\Framework\Lumen\Exception\StatementException;
use Vtiful\Framework\Lumen\Exception\ConnectionException;

class PDO extends CorePDO
{
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
     * Connect
     *
     * @param array $config
     *
     * @throws \Exception
     */
    public function connect(array $config)
    {
        $pool = MysqlPool::getInstance();

        $this->currentConnect = $pool->connection();
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
            if (!$this->currentConnect->connected) {
                $this->currentConnect = MysqlPool::getInstance()->createConnection(true);
                $this->prepare($statement, $options);
            }

            $errorCode = $this->currentConnect->errno;
            $errorMess = $this->currentConnect->error;

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
            if (!$this->currentConnect->connected) {
                $this->currentConnect = MysqlPool::getInstance()->createConnection(true);
                $this->query($statement, $mode, $arg3, $ctorargs);
            }

            $errorCode = $this->currentConnect->errno;
            $errorMess = $this->currentConnect->error;

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