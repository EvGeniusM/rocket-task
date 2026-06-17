<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Http\HttpClient;

final class FakeHttpClient implements HttpClient
{
    /** @var array<string, array<mixed>|null> */
    private array $responses = [];

    /** @param array<mixed>|null $response */
    public function on(string $url, ?array $response): void
    {
        $this->responses[$url] = $response;
    }

    public function get(string $url, array $query = []): ?array
    {
        return $this->responses[$url] ?? null;
    }
}
