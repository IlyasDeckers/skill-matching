<?php

use Psr\Log\LogLevel;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */
    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application.
    | Available drivers: "file", "stream", "null", "callable", "group"
    | Available formatters: "line", "json"
    |
    */
    'channels' => [
        // Default stacked channel (logs to multiple destinations)
        'stack' => [
            'driver' => 'group',
            'channels' => ['file', 'stdout'],
            'level' => env('LOG_LEVEL', LogLevel::DEBUG),
        ],

        // Main file logger
        'file' => [
            'driver' => 'file',
            'path' => storage_path(env('LOG_FILE', 'logs/app.log')),
            'level' => env('LOG_LEVEL', LogLevel::DEBUG),
            'formatter' => 'line',
            'format' => "[%datetime%] [%level%] %message% %context%", // Optional custom format
            'date_format' => 'Y-m-d H:i:s', // Optional custom date format
            'rotate' => true, // Whether to rotate log files when they get too big
            'max_file_size' => 10485760, // 10MB default
        ],

        // Daily rotating logs
        'daily' => [
            'driver' => 'file',
            'path' => storage_path('logs/daily-'), // Date will be appended automatically
            'level' => env('LOG_LEVEL', LogLevel::DEBUG),
            'formatter' => 'line',
            'rotate' => true,
            'max_file_size' => 5242880, // 5MB
        ],

        // Log to standard output (good for containerized environments)
        'stdout' => [
            'driver' => 'stream',
            'stream' => 'php://stdout',
            'level' => env('LOG_LEVEL', LogLevel::DEBUG),
            'formatter' => 'line',
        ],

        // Log errors to standard error
        'stderr' => [
            'driver' => 'stream',
            'stream' => 'php://stderr',
            'level' => LogLevel::ERROR,
            'formatter' => 'line',
        ],

        // JSON formatted logs (useful for log aggregation services)
        'json' => [
            'driver' => 'file',
            'path' => storage_path('logs/json.log'),
            'level' => env('LOG_LEVEL', LogLevel::DEBUG),
            'formatter' => 'json',
        ],

        // Null logger (discards all logs)
        'null' => [
            'driver' => 'null',
            'level' => LogLevel::DEBUG,
        ],

        // Error channel - for error logs only
        'error' => [
            'driver' => 'file',
            'path' => storage_path('logs/error.log'),
            'level' => LogLevel::ERROR,
            'formatter' => 'line',
        ],

        // Example of logger for specific component
        'api' => [
            'driver' => 'file',
            'path' => storage_path('logs/api.log'),
            'level' => env('LOG_LEVEL', LogLevel::INFO),
            'formatter' => 'line',
        ],

        'influxdb' => [
            'driver' => 'influxdb',
            'url' => env('INFLUXDB_URL', 'http://127.0.0.1:8086'),
            'token' => env('INFLUXDB_TOKEN', ''),
            'org' => env('INFLUXDB_ORG', 'organization'),
            'bucket' => env('INFLUXDB_BUCKET', 'logs'),
            'measurement' => env('INFLUXDB_MEASUREMENT', 'logs'),
            'level' => env('INFLUXDB_LOG_LEVEL', 'debug'),
            'use_coroutines' => env('INFLUXDB_USE_COROUTINES', false),
            'tags' => [
                'service' => env('APP_NAME', 'ody-service'),
                'environment' => env('APP_ENV', 'production'),
                'instance' => env('INSTANCE_ID', gethostname()),
            ],
        ],



        // Example of custom callable handler
        // 'custom' => [
        //     'driver' => 'callable',
        //     'handler' => function (string $level, string $message, array $context) {
        //         // Custom log handling code here
        //         // e.g., send to external service
        //     },
        //     'level' => LogLevel::DEBUG,
        //     'formatter' => 'line',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Routes
    |--------------------------------------------------------------------------
    |
    | Routes that should be excluded from logging.
    | You can use wildcards (*) to match paths.
    |
    */
    'exclude_routes' => [
        // Log viewer routes
        '/api/logs/recent',
        '/api/logs/services',
        '/api/logs/levels',
        '/api/logs/service/*',

        // Health check endpoints
        '/health',
        '/ping',

        // Add any other routes that you want to exclude from logging
        '/metrics',
        '/status',
    ],
];