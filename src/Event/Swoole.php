<?php

namespace Vtiful\Event;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Debug\HtmlDumper;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Vtiful\Request\RequestFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Trait Swoole
 *
 * @package Vtiful\Event
 */
trait Swoole
{
    /**
     * Set Process Name
     *
     * @param string $processName
     *
     * @return void
     */
    protected function setProcessName(string $processName): void
    {
        if (PHP_OS === 'Darwin') {
            return;
        }

        $processName = implode(':', ['PHP_VTIFUL', $processName]);

        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($processName);
        }

        if (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($processName);
        }
    }

    /**
     * Event Start
     *
     * @param Server $server
     *
     * @author viest <wjx@php.net>
     */
    public function onStart(Server $server)
    {
        foreach (spl_autoload_functions() as $function) {
            spl_autoload_unregister($function);
        }

        $this->setProcessName('Master');

        if (version_compare(swoole_version(), '1.9.5', '<')) {
            file_put_contents($this->config['swoole']['pid_file'], $server->master_pid);
        }
    }

    /**
     * Event Shutdown
     *
     * @param Server $server
     *
     * @return void
     */
    public function onShutdown(Server $server): void
    {
        // return;
    }

    /**
     * Event Manager Starty
     *
     * @param Server $server
     *
     * @return void
     */
    public function onManagerStart(Server $server): void
    {
        $this->setProcessName('Manager');
    }

    /**
     * Event Manager Stop
     *
     * @param Server $server
     *
     * @author viest <wjx@php.net>
     */
    public function onManagerStop(Server $server): void
    {
        // return;
    }

    /**
     * Event Work Start
     *
     * @param Server $server
     * @param int    $workerId
     *
     * @return void
     */
    public function onWorkerStart(Server $server, int $workerId): void
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        clearstatcache();
    }

    /**
     * Event Work Stop
     *
     * @param Server $server
     * @param int    $workerId
     *
     * @return void
     */
    public function onWorkerStop(Server $server, int $workerId): void
    {
        // return;
    }

    /**
     * Event Work Error
     *
     * @param Server $server
     * @param int    $workerId
     * @param int    $workerPId
     * @param int    $exitCode
     * @param int    $signal
     *
     * @return void
     */
    public function onWorkerError(Server $server, int $workerId, int $workerPId, int $exitCode, int $signal): void
    {
        $this->log(sprintf('worker[%d] error: exitCode=%s, signal=%s', $workerId, $exitCode, $signal), 'ERROR');
    }

    /**
     * Event Work Exit
     *
     * @param Server $server
     * @param int    $workerId
     *
     * @return void
     */
    public function onWorkerExit(Server $server, int $workerId): void
    {
        $corcutineStats        = Coroutine::stats();
        $corcutineOnlineNumber = $corcutineStats['coroutine_num'] ?? 0;

        // All corcutine go offline and manually exit the progress process
        if ($corcutineOnlineNumber === 0) {
            posix_kill(getmypid(), SIGKILL);
        }
    }

    /**
     * Event PIP Message
     *
     * @param Server $server
     * @param int    $srcWorkerId
     * @param string $message
     *
     * @return void
     */
    public function onPipeMessage(Server $server, int $srcWorkerId, string $message): void
    {
        //if ($message instanceof Task) {
        //    $this->onTask($server, uniqid('', true), $srcWorkerId, $message);
        //}
    }

    /**
     * Event Request
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return void
     */
    public function onRequest(Request $request, Response $response): void
    {
        $requestFactory    = new RequestFactory($request);
        $illuminateRequest = $requestFactory->illuminateRequest();

        $application = $this->applicationFactory->instance();
        $application->instance('request', $illuminateRequest);

        ob_start();

        $illuminateResponse = $application->dispatch($illuminateRequest);

        if ($illuminateResponse instanceof SymfonyResponse) {
            $content = $illuminateResponse->getContent();
        } else {
            $content = (string)$illuminateResponse;
        }

        $outputBuffer = ob_get_contents();

        ob_end_clean();

        if (isset($content[0])) {
            foreach ($illuminateResponse->headers->allPreserveCaseWithoutCookies() as $name => $values) {
                foreach ($values as $value) {
                    $response->header($name, $value);
                }
            }

            if (strlen($outputBuffer) > 0) {
                $response->header('Content-Type', 'text/html');
            }

            $response->end( $outputBuffer . $content);
        }
    }

    /**
     * Event Task
     *
     * @param Server $server
     * @param int    $taskId
     * @param int    $workerId
     * @param mixed  $data
     *
     * @return void
     */
    public function onTask(Server $server, int $taskId, int $workerId, $data)
    {
        // return;
    }

    /**
     * Event Finish
     *
     * @param Server $server
     * @param int    $taskId
     * @param mixed  $data
     *
     * @author void
     */
    public function onFinish(Server $server, int $taskId, $data)
    {
        // return;
    }
}