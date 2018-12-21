<?php

namespace Vtiful\Framework\Lumen\Database;

use Illuminate\Database\DatabaseManager as IlluminateDatabaseManager;

/**
 * Class DatabaseManager
 *
 * @package Vtiful\Framework\Lumen\Database
 */
class DatabaseManager extends IlluminateDatabaseManager
{
    /**
     * DatabaseManager constructor.
     *
     * @param                   $app
     * @param ConnectionFactory $factory
     */
    public function __construct($app, ConnectionFactory $factory)
    {
        parent::__construct($app, $factory);
    }
}