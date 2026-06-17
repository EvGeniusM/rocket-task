<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\BookService;
use OpenApi\Attributes as OA;

final class BookController extends Controller
{
    private BookService $service;

    public function __construct()
    {
        $this->service = new BookService();
    }

    #[OA\Get(
        path: '/books',
        operationId: 'listBooks',
        summary: 'List my books (excluding soft-deleted)',
        security: [['bearerAuth' => []]],
        tags: ['Books'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of books',
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
        ],
    )]
    public function index(): void
    {
        Response::json(['books' => $this->service->forUser(Auth::id())]);
    }

    #[OA\Post(
        path: '/books',
        operationId: 'createBook',
        summary: 'Create a book (JSON body or multipart with file)',
        security: [['bearerAuth' => []]],
        tags: ['Books'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Provide a title plus either inline text (JSON) or an uploaded .txt file (multipart). One of text/file is required.',
            content: [
                new OA\JsonContent(
                    required: ['title', 'text'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', example: 'Dune'),
                        new OA\Property(property: 'text', type: 'string', example: 'Spice must flow.'),
                    ],
                ),
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        required: ['title', 'file'],
                        properties: [
                            new OA\Property(property: 'title', type: 'string', example: 'Dune'),
                            new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'A .txt file whose contents become the book body'),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Book created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'title', type: 'string', example: 'Dune'),
                        new OA\Property(property: 'content', type: 'string', example: 'Spice must flow.'),
                        new OA\Property(property: 'created_at', type: 'string', example: '2024-01-01 00:00:00'),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2024-01-01 00:00:00'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(): void
    {
        $body = $this->body();

        try {
            $book = $this->service->create(
                Auth::id(),
                (string) ($body['title'] ?? ''),
                array_key_exists('text', $body) ? (string) $body['text'] : null,
                Request::file('file'),
            );
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422);

            return;
        }

        Response::json($book, 201);
    }

    #[OA\Get(
        path: '/books/{id}',
        operationId: 'showBook',
        summary: 'Get a single book by ID',
        security: [['bearerAuth' => []]],
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Book detail',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'title', type: 'string', example: 'Dune'),
                        new OA\Property(property: 'content', type: 'string', example: 'Spice must flow.'),
                        new OA\Property(property: 'created_at', type: 'string', example: '2024-01-01 00:00:00'),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2024-01-01 00:00:00'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Book not found'),
        ],
    )]
    public function show(string $id): void
    {
        $book = $this->service->find((int) $id, Auth::id());

        if ($book === null) {
            Response::error('Book not found.', 404);

            return;
        }

        Response::json($book);
    }

    #[OA\Put(
        path: '/books/{id}',
        operationId: 'updateBook',
        summary: 'Update a book title or content',
        security: [['bearerAuth' => []]],
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Dune Messiah'),
                    new OA\Property(property: 'text', type: 'string', example: 'Updated content.'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated book'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Book not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function update(string $id): void
    {
        $body = $this->body();

        try {
            $book = $this->service->update(
                (int) $id,
                Auth::id(),
                array_key_exists('title', $body) ? (string) $body['title'] : null,
                array_key_exists('text', $body) ? (string) $body['text'] : null,
            );
        } catch (NotFoundException $e) {
            Response::error($e->getMessage(), 404);

            return;
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422);

            return;
        }

        Response::json($book);
    }

    #[OA\Delete(
        path: '/books/{id}',
        operationId: 'deleteBook',
        summary: 'Soft-delete a book',
        security: [['bearerAuth' => []]],
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Book deleted (no content)'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Book not found'),
        ],
    )]
    public function destroy(string $id): void
    {
        try {
            $this->service->softDelete((int) $id, Auth::id());
        } catch (NotFoundException $e) {
            Response::error($e->getMessage(), 404);

            return;
        }

        Response::noContent();
    }

    #[OA\Post(
        path: '/books/{id}/restore',
        operationId: 'restoreBook',
        summary: 'Restore a soft-deleted book',
        security: [['bearerAuth' => []]],
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Book restored'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'No deleted book found'),
        ],
    )]
    public function restore(string $id): void
    {
        try {
            $this->service->restore((int) $id, Auth::id());
        } catch (NotFoundException $e) {
            Response::error($e->getMessage(), 404);

            return;
        }

        Response::json(['message' => 'Book restored.']);
    }

    #[OA\Post(
        path: '/books/external',
        operationId: 'saveExternalBook',
        summary: 'Save a book from external search results (Google Books)',
        security: [['bearerAuth' => []]],
        tags: ['Books'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['external_id'],
                properties: [
                    new OA\Property(property: 'external_id', type: 'string', example: 'google:zyTCAlFPjgYC'),
                    new OA\Property(property: 'title', type: 'string', description: 'Override title (optional)', example: 'Clean Code'),
                    new OA\Property(property: 'text', type: 'string', description: 'Override content (optional)'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Book saved to library'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Could not resolve book'),
        ],
    )]
    public function storeExternal(): void
    {
        $body = $this->body();

        try {
            $book = $this->service->createFromExternal(
                Auth::id(),
                (string) ($body['external_id'] ?? ''),
                array_key_exists('title', $body) ? (string) $body['title'] : null,
                array_key_exists('text', $body) ? (string) $body['text'] : null,
            );
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422);

            return;
        }

        Response::json($book, 201);
    }
}
