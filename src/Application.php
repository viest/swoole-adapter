<?php

namespace Vtiful;

use Swoole\Http\Server;
use Vtiful\Event\Swoole;
use Vtiful\Event\Timer;
use Vtiful\Framework\Factory;
use Vtiful\Framework\Framework;

/**
 * Class Application
 *
 * @package Vtiful
 */
class Application extends Swoole
{
    /**
     * @var Server
     */
    protected $server;

    /**
     * @var Framework
     */
    protected $applicationFactory;

    /**
     * Create Http Server
     *
     * @return void
     */
    protected function createServer(): void
    {
        $this->server = new Server($this->config['bind'], $this->config['port']);

        // set swoole config
        $this->server->set($this->config['swoole']);
    }

    /**
     * Bind Event
     *
     * @return void
     */
    protected function bindEvent(): void
    {
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('Shutdown', [$this, 'onShutdown']);
        $this->server->on('ManagerStart', [$this, 'onManagerStart']);
        $this->server->on('ManagerStop', [$this, 'onManagerStop']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->server->on('WorkerError', [$this, 'onWorkerError']);
        $this->server->on('WorkerExit', [$this, 'onWorkerExit']);
        $this->server->on('PipeMessage', [$this, 'onPipeMessage']);
        $this->server->on('Request', [$this, 'onRequest']);
    }

    /**
     * Bind Timer
     *
     * @return void
     */
    protected function bindTimer(): void
    {
        $timerManager = new Timer();
        $timerManager->init($this->server, $this->config['common']['timer']);
    }

    /**
     * Server Start
     *
     * @return void
     */
    protected function start(): void
    {
        $this->server->start();
    }

    /**
     * Init Framework
     *
     * @throws \Exception
     *
     * @author viest <wjx@php.net>
     */
    protected function initializationFramework()
    {
        $this->applicationFactory = Factory::init(
            $this->config['framework'],
            $this->config['base_path'],
            $this->config['common']
        );
    }

    /**
     * Log
     *
     * @param string $msg
     * @param string $type
     *
     * @author viest <wjx@php.net>
     */
    public function log($msg, $type = 'INFO')
    {
        echo sprintf('[%s] [%s] Swoole: %s', date('Y-m-d H:i:s'), $type, $msg), PHP_EOL;
    }

    /**
     * Start Server
     *
     * @throws \Exception
     *
     * @return void
     */
    public function run(): void
    {
        $this->initializationFramework();

        $this->createServer();
        $this->bindEvent();
        $this->bindTimer();

        $this->start();
    }
}