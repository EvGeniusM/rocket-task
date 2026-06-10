<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
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
        Response::notImplemented('GET /books');
    }

    public function store(): void
    {
        Response::notImplemented('POST /books');
    }

    public function show(string $id): void
    {
        Response::notImplemented("GET /books/{$id}");
    }

    public function update(string $id): void
    {
        Response::notImplemented("PUT /books/{$id}");
    }

    public function destroy(string $id): void
    {
        Response::notImplemented("DELETE /books/{$id}");
    }

    public function restore(string $id): void
    {
        Response::notImplemented("POST /books/{$id}/restore");
    }

    public function storeExternal(): void
    {
        Response::notImplemented('POST /books/external');
    }
}
