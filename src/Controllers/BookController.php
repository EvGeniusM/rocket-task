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

final class BookController extends Controller
{
    private BookService $service;

    public function __construct()
    {
        $this->service = new BookService();
    }

    public function index(): void
    {
        Response::json(['books' => $this->service->forUser(Auth::id())]);
    }

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

    public function show(string $id): void
    {
        $book = $this->service->find((int) $id, Auth::id());

        if ($book === null) {
            Response::error('Book not found.', 404);

            return;
        }

        Response::json($book);
    }

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

    public function destroy(string $id): void
    {
        try {
            $this->service->softDelete((int) $id, Auth::id());
        } catch (NotFoundException $e) {
            Response::error($e->getMessage(), 404);

            return;
        }

        Response::json(['message' => 'Book deleted.']);
    }

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
