<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Http\CurlHttpClient;
use App\Core\Http\HttpClient;
use App\Exceptions\ValidationException;

final class SearchService
{
    private const GOOGLE_BOOKS_URL = 'https://www.googleapis.com/books/v1/volumes';
    private const MIF_URL = 'https://www.mann-ivanov-ferber.ru/book/search.ajax';

    public function __construct(
        private readonly HttpClient $http = new CurlHttpClient(),
    ) {
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            throw new ValidationException('Search query is required.');
        }

        return array_merge($this->googleBooks($query), $this->mif($query));
    }

    public function resolveGoogleVolume(string $volumeId): ?array
    {
        $data = $this->http->get(self::GOOGLE_BOOKS_URL . '/' . rawurlencode($volumeId));

        if ($data === null) {
            return null;
        }

        $info = $data['volumeInfo'] ?? [];

        return [
            'title'       => $info['title'] ?? null,
            'description' => $info['description'] ?? null,
        ];
    }

    private function googleBooks(string $query): array
    {
        $data = $this->http->get(self::GOOGLE_BOOKS_URL, ['q' => $query, 'maxResults' => 10]);

        if ($data === null || !isset($data['items']) || !is_array($data['items'])) {
            return [];
        }

        $results = [];

        foreach ($data['items'] as $item) {
            if (empty($item['id'])) {
                continue;
            }

            $info = $item['volumeInfo'] ?? [];

            $results[] = [
                'source'      => 'google',
                'external_id' => 'google:' . $item['id'],
                'title'       => $info['title'] ?? '(untitled)',
                'authors'     => $info['authors'] ?? [],
                'description' => $info['description'] ?? null,
            ];
        }

        return $results;
    }

    private function mif(string $query): array
    {
        $data = $this->http->get(self::MIF_URL, ['q' => $query]);

        if ($data === null) {
            return [];
        }

        $results = [];

        foreach ($data as $section) {
            foreach ($section['items'] ?? [] as $item) {
                if (empty($item['id'])) {
                    continue;
                }

                $results[] = [
                    'source'      => 'mif',
                    'external_id' => 'mif:' . $item['id'],
                    'title'       => $item['title'] ?? '(untitled)',
                    'url'         => $item['url'] ?? null,
                ];
            }
        }

        return $results;
    }
}
