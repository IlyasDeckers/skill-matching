<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ody\Foundation\Http\Response;

class UserController
{
    /**
     * Get all users
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $params
     * @return ResponseInterface
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $params): ResponseInterface
    {
        // In a real app, fetch from database

        // Mock data for example
         $users = [
             [
                 'id' => 1,
                 'name' => 'John Doe',
             ],
             [
                 'id' => 2,
                 'name' => 'Jane Doe',
             ]
         ];

        return $this->jsonResponse($response, $users);
    }

    /**
     * Get a specific user by ID
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $params
     * @return ResponseInterface
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $params): ResponseInterface
    {
        $id = $params['id'] ?? null;

        logger()->info('Fetching user', ['id' => $id]);

        // In a real app, fetch from databaseS

        // Mock data for example
        $user = ['id' => (int)$id, 'name' => 'John Doe', 'email' => 'john@example.com'];

        return $this->jsonResponse($response, $user);
    }

    /**
     * Create a new user
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $params
     * @return ResponseInterface
     */
    public function store(ServerRequestInterface $request, ResponseInterface $response, array $params): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];

        logger()->info('Creating user', $data);

        // Validate input
        if (empty($data['name']) || empty($data['email'])) {
            return $this->jsonResponse($response->withStatus(422), [
                'error' => 'Name and email are required'
            ]);
        }

        // In a real app, save to databaseS

        // Mock data for example
        $id = 3;

        return $this->jsonResponse($response->withStatus(201), [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email']
        ]);
    }

    /**
     * Update an existing user
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $params
     * @return ResponseInterface
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $params): ResponseInterface
    {
        $id = $params['id'] ?? null;
        $data = $request->getParsedBody() ?? [];

        logger()->info('Updating user', ['id' => $id, 'data' => $data]);

        // In a real app, update in databaseDSS

        // Mock response for example
        return $this->jsonResponse($response, [
            'id' => (int)$id,
            'name' => $data['name'] ?? 'John Doe',
            'email' => $data['email'] ?? 'john@example.com',
            'updated' => true
        ]);
    }

    /**
     * Delete a user
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $params
     * @return ResponseInterface
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $params): ResponseInterface
    {
        $id = $params['id'] ?? null;

        logger()->info('Deleting user', ['id' => $id]);

        // In a real app, delete from database

        // Mock data for example
        $affected = 1;

        return $this->jsonResponse($response, [
            'deleted' => $affected > 0,
            'id' => (int)$id
        ]);
    }

    /**
     * Helper method to create JSON responses
     *
     * @param ResponseInterface $response
     * @param mixed $data
     * @return ResponseInterface
     */
    private function jsonResponse(ResponseInterface $response, $data): ResponseInterface
    {
        // Debug the response type
        logger()->info('Response object in jsonResponse', [
            'class' => get_class($response),
            'interfaces' => implode(', ', class_implements($response))
        ]);

        // Always set JSON content type
        $response = $response->withHeader('Content-Type', 'application/json');

        // If using our custom Response class
        if ($response instanceof Response) {
            // Instead of using withJson directly, we'll manually encode and set the body
            $jsonData = json_encode($data);
            if ($jsonData === false) {
                logger()->error('JSON encoding error', [
                    'error' => json_last_error_msg()
                ]);
                $jsonData = json_encode(['error' => 'JSON encoding error']);
            }

            // Create a stream factory
            $streamFactory = new \Nyholm\Psr7\Factory\Psr17Factory();
            // Create a stream with the JSON data
            $stream = $streamFactory->createStream($jsonData);
            // Set the stream as the response body
            return $response->withBody($stream);
        }

        // For other PSR-7 implementations
        $response->getBody()->write(json_encode($data));
        return $response;
    }
}