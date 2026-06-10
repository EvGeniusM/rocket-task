<?php

declare(strict_types=1);

namespace App\Services;

final class AuthService
{
    public function register(string $login, string $password, string $confirmPassword): array
    {
        return [];
    }

    public function login(string $login, string $password): array
    {
        return [];
    }

    public function userFromToken(string $token): ?array
    {
        return null;
    }
}
