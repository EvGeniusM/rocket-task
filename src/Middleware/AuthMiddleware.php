<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

final class AuthMiddleware
{
    public static function handle(): void
    {
        $token = Request::bearerToken();

        unset($token);
    }
}
