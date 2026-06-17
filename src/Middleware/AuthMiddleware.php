<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AuthMiddleware
{
    public static function handle(): void
    {
        $token = Request::bearerToken();

        if ($token === null) {
            Response::error('Unauthenticated.', 401);

            exit;
        }

        $user = (new AuthService())->userFromToken($token);

        if ($user === null) {
            Response::error('Unauthenticated.', 401);

            exit;
        }

        Auth::setUser($user);
    }
}
