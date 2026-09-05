<?php

return [

    'server' => env('OCTANE_SERVER', 'frankenphp'),

    'host' => env('OCTANE_HOST', '0.0.0.0'),

    'port' => env('OCTANE_PORT', 8000),

    'workers' => env('OCTANE_WORKERS', 1),

    'task_workers' => env('OCTANE_TASK_WORKERS', 1),
    'task_worker_timeout' => env('OCTANE_TASK_WORKER_TIMEOUT', 60),

    'request_timeout' => env('OCTANE_REQUEST_TIMEOUT', 60),

    'max_requests' => env('OCTANE_MAX_REQUESTS', 500),

    'reload' => [
        'paths' => [
            base_path(),
            app_path(),
            config_path(),
            database_path(),
            public_path(),
            resource_path(),
            base_path('routes'),
        ],
        'interval' => 1000,
    ],

    'warmup' => [
        'enabled' => env('OCTANE_WARMUP_ENABLED', true),
        'urls' => [
            '/',
            '/api/v1/health',
        ],
    ],

    'watch' => [
        'enabled' => env('OCTANE_WATCH_ENABLED', false),
        'paths' => [
            app_path(),
            config_path(),
            database_path(),
            base_path('routes'),
            resource_path(),
            public_path(),
        ],
    ],

    'frankenphp' => [
        'config_file' => base_path('Caddyfile'),
        'worker' => base_path('frankenphp-worker.php'),
        'max_requests' => env('OCTANE_FRANKENPHP_MAX_REQUESTS', 500),
        'num_threads' => env('OCTANE_FRANKENPHP_NUM_THREADS', 1),
        'max_threads' => env('OCTANE_FRANKENPHP_MAX_THREADS', 10),
    ],

];