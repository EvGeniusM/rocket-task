<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\AuthService;
use OpenApi\Attributes as OA;

final class AuthController extends Controller
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    #[OA\Post(
        path: '/register',
        operationId: 'register',
        summary: 'Register a new user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['login', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'login', type: 'string', example: 'alice'),
                    new OA\Property(property: 'password', type: 'string', example: 'secret123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'secret123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'a1b2c3...'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'login', type: 'string', example: 'alice'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function register(): void
    {
        $body = $this->body();

        try {
            $result = $this->service->register(
                (string) ($body['login'] ?? ''),
                (string) ($body['password'] ?? ''),
                (string) ($body['password_confirmation'] ?? ''),
            );
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422);

            return;
        }

        Response::json($result, 201);
    }

    #[OA\Post(
        path: '/login',
        operationId: 'login',
        summary: 'Log in and receive a bearer token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['login', 'password'],
                properties: [
                    new OA\Property(property: 'login', type: 'string', example: 'alice'),
                    new OA\Property(property: 'password', type: 'string', example: 'secret123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'a1b2c3...'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'login', type: 'string', example: 'alice'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ],
    )]
    public function login(): void
    {
        $body = $this->body();

        try {
            $result = $this->service->login(
                (string) ($body['login'] ?? ''),
                (string) ($body['password'] ?? ''),
            );
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 401);

            return;
        }

        Response::json($result);
    }
}
