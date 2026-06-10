<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private const ENCODE_FLAGS = JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT;

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, self::ENCODE_FLAGS);
    }

    public static function error(string $message, int $status = 400): void
    {
        self::json(['status' => 'error', 'message' => $message], $status);
    }

    public static function notImplemented(string $endpoint): void
    {
        self::json(['status' => 'not_implemented', 'endpoint' => $endpoint], 501);
    }
}
