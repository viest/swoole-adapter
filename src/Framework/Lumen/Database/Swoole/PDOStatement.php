<?php

namespace Vtiful\Framework\Lumen\Database\Swoole;

use PDOStatement as CorePDOStatement;
use Swoole\Coroutine\MySQL\Statement;
use Vtiful\Framework\Lumen\Exception\StatementException;

/**
 * Class PDOStatement
 *
 * @package Vtiful\Framework\Lumen\Database\Swoole
 */
class PDOStatement extends CorePDOStatement
{
    protected $statement;
    protected $bindParams = [];
    protected $result;

    /**
     * PDOStatement constructor.
     *
     * @param Statement $statement
     */
    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * Row Count
     *
     * @return int
     */
    public function rowCount()
    {
        return $this->statement->affected_rows;
    }

    /**
     * Bind Value
     *
     * @param mixed $parameter
     * @param mixed $value
     * @param int   $data_type
     *
     * @return bool|void
     */
    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
    {
        $this->bindParams[$parameter] = $value;
    }

    /**
     * @param array $input_parameters
     *
     * @throws StatementException
     *
     * @return bool
     */
    public function execute($inputParameters = NULL)
    {
        if (empty($inputParameters) && !empty($this->bindParams)) {
            $inputParameters = $this->bindParams;
        }

        $inputParameters = (array)$inputParameters;
        $this->result    = $this->statement->execute(
            $inputParameters, array_get($inputParameters, '__timeout__', -1)
        );

        if ($this->statement->errno != 0) {
            throw new StatementException($this->statement->error, $this->statement->errno);
        }

        return true;
    }

    /**
     * Fetch All
     *
     * @param NULL $how
     * @param NULL $className
     * @param NULL $ctorArgs
     *
     * @return array
     */
    public function fetchAll($how = null, $className = null, $ctorArgs = null)
    {
        return $this->result;
    }
}