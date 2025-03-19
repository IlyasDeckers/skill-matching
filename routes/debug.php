<?php
//
///**
// * Debug routes for testing middleware
// */
//


use Ody\Foundation\Facades\Route;

Route::get('/debug-middleware', function ($request, $response) {
    $logger = isset($this) && $this->container && $this->container->has('logger')
        ? $this->container->get('logger')
        : null;

    if ($logger) {
        $logger->info('Debug middleware endpoint hit');
    }

    return $response->withJson([
        'message' => 'Debug middleware endpoint',
        'request_attributes' => array_keys($request->getAttributes()),
        'path' => $request->getUri()->getPath(),
        'middleware_list' => [
            'global_middleware' => [
                'Ody\Foundation\Middleware\JsonBodyParserMiddleware',
                'Ody\Foundation\Middleware\CorsMiddleware',
                'Ody\Foundation\Middleware\LoggingMiddleware',
                'Ody\Auth\Middleware\AttachUserToRequest'
            ],
            'named_middleware' => [
                'auth' => 'Ody\Auth\Middleware\Authenticate',
                'auth.sanctum' => 'Ody\Auth\Middleware\Authenticate',
                'auth.token' => 'Ody\Auth\Middleware\Authenticate'
            ]
        ]
    ]);
});