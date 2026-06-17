<?php

declare(strict_types=1);

namespace App\Core\Http;

interface HttpClient
{
    /** @param array<string, mixed> $query */
    public function get(string $url, array $query = []): ?array;
}
