<?php

namespace Vtiful\Framework;

abstract class Framework
{
    /**
     * Init Application
     *
     * @param string $basePath
     * @param array  $config
     *
     * @return mixed
     */
    abstract static function initialization(string $basePath, array $config);

    /**
     * @param mixed $instance
     *
     * @return void
     */
    abstract static function reset($instance);

    /**
     * Clean Instance
     *
     * @return void
     */
    abstract static function clean($instance);

    /**
     * Replace Framework Module
     *
     * @return mixed
     */
    abstract static function replace();

    /**
     * Application Instance
     *
     * @return mixed
     */
    abstract function instance();
}