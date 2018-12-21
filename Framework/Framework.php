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
     * @return void
     */
    abstract static function reset();

    /**
     * Clean Instance
     *
     * @return void
     */
    abstract static function clean();

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