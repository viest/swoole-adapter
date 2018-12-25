<?php

namespace Vtiful\Event;

use Exception;
use Swoole\Process;
use Swoole\Http\Server;
use Vtiful\ExternalInterface\Timer as TimerInterface;

/**
 * Class Timer
 *
 * @package Vtiful\Event
 */
class Timer
{
    /**
     * Timer Process Init
     *
     * @param Server $server
     * @param array  $config
     */
    public function init(Server $server, array $config)
    {
        if (!is_array($config) || empty($config)) {
            return;
        }

        $timerProcess = new Process(function () use ($config) {
            foreach ($config as $timerTask) {
                ['class' => $taskClass, 'ms' => $taskMS] = $timerTask;

                if (!class_exists($taskClass)) {
                    throw new Exception('Class Not Found: ' . $taskClass);
                }

                $taskObject = new $taskClass();

                if (!$taskObject instanceof TimerInterface) {
                    throw new Exception('Does not inherit abstract classes: ' . $taskClass);
                }

                swoole_timer_tick($taskMS, function () use ($taskObject) {
                    call_user_func_array([$taskObject, 'run'], []);
                });
            }
        }, false, false);

        $server->addProcess($timerProcess);
    }
}