<?php

namespace Vtiful\Framework\Lumen\Database\ServiceProvider;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\DatabaseServiceProvider;
use Vtiful\Framework\Lumen\Database\ConnectionFactory;
use Vtiful\Framework\Lumen\Database\DatabaseManager;

/**
 * Class Mysql
 *
 * @package Vtiful\Framework\Lumen\Database\ServiceProvider
 */
class Mysql extends DatabaseServiceProvider
{
    /**
     * Register Mysql Database Service Provider
     *
     * @return void
     */
    public function register(): void
    {
        Model::clearBootedModels();

        $this->registerConnectionServices();

        $this->registerEloquentFactory();

        $this->registerQueueableEntityResolver();
    }

    /**
     * Register Connection Services
     *
     * @return void
     */
    public function registerConnectionServices(): void
    {
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });
    }
}