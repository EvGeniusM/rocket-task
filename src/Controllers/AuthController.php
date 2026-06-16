<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\AuthService;

final class AuthController extends Controller
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

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
