<?php

namespace Vtiful\Framework\Lumen;

use Laravel\Lumen\Application as LumenApplication;
use Vtiful\Framework\Framework as FrameworkAbstract;

class Framework extends FrameworkAbstract
{
    /**
     * @var LumenApplication
     */
    static $application;

    /**
     * Lumen constructor.
     *
     * @param string $applicationEntrancePath
     * @param array  $config
     *
     * @return Framework
     */
    public static function initialization(string $applicationEntrancePath, array $config)
    {
        static::$application = require $applicationEntrancePath . '/bootstrap/app.php';

        static::replace();

        return new self();
    }

    /**
     * Get Instance
     *
     * @return LumenApplication
     *
     * @author viest <wjx@php.net>
     */
    public function instance()
    {
        return clone static::$application;
    }

    /**
     * @return void
     */
    public static function reset()
    {
        // return;
    }

    /**
     * @return void
     */
    public static function clean()
    {
        // return;
    }

    /**
     * Replace Framework Module
     *
     * @return mixed
     */
    public static function replace()
    {
        static::$application->register(\Vtiful\Framework\Lumen\Database\ServiceProvider\Mysql::class);
    }
}