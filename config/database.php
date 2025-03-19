<?php

return [
    'migration_dirs' => [
        'migrations' => 'database/migrations',
    ],
    'charset' => 'utf8mb4',
    'environments' => [
        'local' => [
            'adapter' => 'mysql',
            'host' => env('DB_HOST' , '127.0.0.1'),
            'port' => env('DB_PORT' , 3306), // optional
            'username' => env('DB_USERNAME' , 'root'),
            'password' => env('DB_PASSWORD' , 'root'),
            'db_name' => env('DB_DATABASE' , 'ody'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci', // optional, if not set default collation for utf8mb4 is used
            'prefix'    => '',
            'options'   => [
                // PDO::ATTR_EMULATE_PREPARES => true
            ],
        ],
        'production' => [
            'adapter' => 'mysql',
            'host' => 'production_host',
            'port' => 3306, // optional
            'username' => 'user',
            'password' => 'pass',
            'db_name' => 'my_production_db',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci', // optional, if not set default collation for utf8mb4 is used
        ],
    ],
    'default_environment' => 'local',
    'log_table_name' => 'migrations_log',
    'connection_pool' => [
        'enabled' => false,
        'host' => '127.0.0.1',
        'port' => 9504,
        'additional' => [
            'enable_delay_receive' => true,
        ],
        'allowed_ips' => [
            '127.0.0.1'
        ],
    ]
];