<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\BookService;
use App\Services\UserService;

final class UserController extends Controller
{
    private UserService $service;
    private BookService $books;

    public function __construct()
    {
        $this->service = new UserService();
        $this->books = new BookService();
    }

    public function index(): void
    {
        Response::json(['users' => $this->service->all()]);
    }

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
