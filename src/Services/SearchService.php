<?php

declare(strict_types=1);

namespace App\Services;

final class SearchService
{
    private const GOOGLE_BOOKS_URL = 'https://www.googleapis.com/books/v1/volumes';
    private const MIF_URL = 'https://www.mann-ivanov-ferber.ru/book/search.ajax';

    public function search(string $query): array
    {
        return [];
    }

    private function googleBooks(string $query): array
    {
        return [];
    }

    private function mif(string $query): array
    {
        return [];
    }

    private function get(string $url, array $query): array
    {
        return [];
    }
}
