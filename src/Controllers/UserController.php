<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\UserService;

final class UserController extends Controller
{
    private UserService $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    public function index(): void
    {
        Response::notImplemented('GET /users');
    }

    public function grantAccess(): void
    {
        Response::notImplemented('POST /users/grant-access');
    }

    public function books(string $id): void
    {
        Response::notImplemented("GET /users/{$id}/books");
    }
}
