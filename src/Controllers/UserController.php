<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\BookService;
use App\Services\UserService;
use OpenApi\Attributes as OA;

final class UserController extends Controller
{
    private UserService $service;
    private BookService $books;

    public function __construct()
    {
        $this->service = new UserService();
        $this->books = new BookService();
    }

    #[OA\Get(
        path: '/users',
        operationId: 'listUsers',
        summary: 'List all registered users (id and login only)',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'users',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'login', type: 'string', example: 'alice'),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(): void
    {
        Response::json(['users' => $this->service->all()]);
    }

    #[OA\Post(
        path: '/users/grant-access',
        operationId: 'grantAccess',
        summary: 'Grant another user read access to my library',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 2),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Access granted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error (self-grant, unknown user)'),
        ],
    )]
    public function grantAccess(): void
    {
        $granteeId = (int) ($this->body()['user_id'] ?? 0);

        try {
            $this->service->grantAccess(Auth::id(), $granteeId);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422);

            return;
        }

        Response::json(['message' => 'Access granted.']);
    }

    #[OA\Get(
        path: '/users/{id}/books',
        operationId: 'userBooks',
        summary: "List another user's books (requires granted access)",
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Book list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'books',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Dune'),
                                    new OA\Property(property: 'link', type: 'string', example: 'http://localhost:8000/books/1'),
                                    new OA\Property(property: 'created_at', type: 'string', example: '2024-01-01 00:00:00'),
                                    new OA\Property(property: 'updated_at', type: 'string', example: '2024-01-01 00:00:00'),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Access not granted'),
        ],
    )]
    public function books(string $id): void
    {
        $ownerId = (int) $id;
        $requesterId = Auth::id();

        if ($ownerId !== $requesterId && !$this->service->hasAccess($ownerId, $requesterId)) {
            Response::error('You do not have access to this library.', 403);

            return;
        }

        Response::json(['books' => $this->books->forUser($ownerId)]);
    }
}
