<?php

namespace Vtiful\Framework\Lumen;

use Illuminate\Http\Request;
use Illuminate\Support\Debug\HtmlDumper;
use Illuminate\Support\Facades\Facade;
use Laravel\Lumen\Application as LumenApplication;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\VarDumper;
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
        $instance = clone static::$application;

        self::clean($instance);

        return $instance;
    }

    /**
     * @param LumenApplication $instance
     *
     * @return void
     */
    public static function reset($instance)
    {
        // return;
    }

    /**
     * @param LumenApplication $instance
     *
     * @return void
     */
    public static function clean($instance)
    {
        /* @var $request Request */
        $request = $instance->make('request');

        if ($request->hasSession()) {
            $session = $request->getSession();

            if (method_exists($session, 'clear')) {
                $session->clear();
            }

            if (method_exists($session, 'flush')) {
                $session->flush();
            }
        }

        $instance->forgetInstance('request');

        Facade::clearResolvedInstance('request');
    }

    /**
     * Replace Framework Module
     *
     * @return mixed
     */
    public static function replace()
    {
        $cloner = new VarCloner();
        $dumper = new HtmlDumper();

        VarDumper::setHandler(function ($var) use ($cloner, $dumper) {
            $data = $cloner->cloneVar($var)->withRefHandles(false);
            $dumper->dump($data);
        });

        static::$application->register(\Vtiful\Framework\Lumen\Database\ServiceProvider\Mysql::class);
    }
}