<?php

declare(strict_types=1);

namespace App\Services;

final class BookService
{
    public function forUser(int $userId): array
    {
        return [];
    }

    public function find(int $bookId, int $requesterId): ?array
    {
        return null;
    }

    public function create(int $ownerId, string $title, ?string $text, ?array $file): array
    {
        return [];
    }

    public function update(int $bookId, int $ownerId, ?string $title, ?string $text): array
    {
        return [];
    }

    public function softDelete(int $bookId, int $ownerId): void
    {
    }

    public function restore(int $bookId, int $ownerId): void
    {
    }

    public function createFromExternal(int $ownerId, string $externalId): array
    {
        return [];
    }

    private function extractText(array $file): string
    {
        return '';
    }
}
