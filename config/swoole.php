<?php

return [
    'bind'      => '0.0.0.0',
    'port'      => 7090,
    'base_path' => realpath(__DIR__ . '/../'),
    'framework' => 'lumen',

    // 变量重置
    'reset' => [

    ],

    'swoole' => [
        'daemonize'          => env('DAEMONIZE', false),
        'dispatch_mode'      => 1,
        'reactor_num'        => 1,
        'worker_num'         => 1,
        //'task_worker_num'   => function_exists('\swoole_cpu_num') ? \swoole_cpu_num() * 2 : 8,
        'task_ipc_mode'      => 1,
        'task_max_request'   => 5000,
        'task_tmpdir'        => @is_writable('/dev/shm/') ? '/dev/shm' : '/tmp',
//        'message_queue_key'  => ftok(base_path('public/index.php'), 1),
        'max_request'        => 3000,
        'open_tcp_nodelay'   => true,
//        'pid_file'           => storage_path('laravels.pid'),
//        'log_file'           => storage_path(sprintf('logs/swoole-%s.log', date('Y-m'))),
        'log_level'          => 4,
//        'document_root'      => base_path('public'),
        'buffer_output_size' => 16 * 1024 * 1024,
        'socket_buffer_size' => 128 * 1024 * 1024,
        'package_max_length' => 4 * 1024 * 1024,
        'reload_async'       => true,
        'max_wait_time'      => 60,
        'enable_reuse_port'  => true,
        'enable_coroutine'   => true,
    ],
];