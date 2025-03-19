<?php

namespace App\Controllers;

use Ody\Auth\AuthManager;
use Ody\Foundation\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController
{
    protected AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->jsonResponse($response->withStatus(422), [
                'error' => 'email and password are required'
            ]);
        }

        $result = $this->authManager->login($data['email'], $data['password']);

        if (!$result) {
            return $this->jsonResponse($response->withStatus(401), [
                'error' => 'Invalid credentials'
            ]);
        }

        return $this->jsonResponse($response, [
            'message' => 'Login successful',
            'token' => $result['token'],
            'refreshToken' => $result['refreshToken'],
            'expiresIn' => $result['expiresIn'] ?? 3600,
            'user' => [
                'id' => $result['id'],
                'email' => $result['email'] ?? null,
            ]
        ]);
    }

    private function jsonResponse(ResponseInterface $response, $data): ResponseInterface
    {
        // Always set JSON content type
        $response = $response->withHeader('Content-Type', 'application/json');

        // If using our custom Response class
        if ($response instanceof Response) {
            return $response->withJson($data);
        }

        // For other PSR-7 implementations
        $response->getBody()->write(json_encode($data));
        return $response;
    }

    public function refresh(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (!isset($data['refreshToken'])) {
            return $this->jsonResponse($response->withStatus(422), [
                'error' => 'Refresh token is required'
            ]);
        }

        $result = $this->authManager->refreshToken($data['refreshToken']);

        if (!$result) {
            return $this->jsonResponse($response->withStatus(401), [
                'error' => 'Invalid refresh token'
            ]);
        }

        return $this->jsonResponse($response, [
            'token' => $result['token'],
            'refreshToken' => $result['refreshToken'],
            'expiresIn' => $result['expiresIn'] ?? 3600
        ]);
    }

    public function user(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // User should be attached to the request by the auth middleware
        $user = $request->getAttribute('user');

        if (!$user) {
            return $this->jsonResponse($response->withStatus(401), [
                'error' => 'User not authenticated'
            ]);
        }

        return $this->jsonResponse($response, [
            'user' => $user
        ]);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);

        $this->authManager->logout($token);

        return $this->jsonResponse($response, [
            'message' => 'Logged out successfully'
        ]);
    }
}