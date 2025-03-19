<?php
// config/auth.php

return [
    'driver' => [
        'provider' => env('AUTH_PROVIDER', 'direct'),
        'jwt_key' => env('JWT_SECRET_KEY', 'your_secret_key_for_development'),
        'token_expiry' => 3600, // 1 hour
        'refresh_token_expiry' => 86400 * 30, // 30 days

        // Remote auth service config
        'service_host' => env('AUTH_SERVICE_HOST', 'localhost'),
        'service_port' => env('AUTH_SERVICE_PORT', 9501),
        'service_id' => env('SERVICE_ID', 'api_service'),
        'service_secret' => env('SERVICE_SECRET', 'service_secret')
    ],

    'middleware' => [
        'auth' => \Ody\Auth\Middleware\AuthMiddleware::class,
    ],
];