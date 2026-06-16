<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;

    public static function setUser(?array $user): void
    {
        self::$user = $user;
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): int
    {
        return (int) (self::$user['id'] ?? 0);
    }

    public static function check(): bool
    {
        return self::$user !== null;
    }
}
