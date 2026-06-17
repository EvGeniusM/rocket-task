<?php

declare(strict_types=1);

namespace App\Core\Http;

final class CurlHttpClient implements HttpClient
{
    public function get(string $url, array $query = []): ?array
    {
        $target = $url . ($query !== [] ? '?' . http_build_query($query) : '');

        $ch = curl_init($target);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_USERAGENT      => 'MobileLibrary/1.0',
        ]);

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($body) || $status >= 400) {
            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
