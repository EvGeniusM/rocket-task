<?php

declare(strict_types=1);

namespace App\Services;

final class UserService
{
    public function all(): array
    {
        return [];
    }

    public function grantAccess(int $ownerId, int $granteeId): void
    {
    }

    public function hasAccess(int $ownerId, int $granteeId): bool
    {
        return false;
    }
}
