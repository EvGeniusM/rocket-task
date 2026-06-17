<?php

declare(strict_types=1);

namespace Tests;

abstract class FeatureTestCase extends TestCase
{
    /**
     * @param array<string, mixed>|null $body
     * @return array{status:int, json:array<mixed>}
     */
    protected function request(string $method, string $path, ?array $body = null, ?string $token = null): array
    {
        $ch = curl_init(TEST_SERVER_URL . $path);
        $headers = ['Accept: application/json'];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
            $headers[] = 'Content-Type: application/json';
        }

        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $options[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $options);

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return ['status' => $status, 'json' => json_decode((string) $raw, true) ?? []];
    }

    protected function authToken(string $login = 'alice', string $password = 'secret123'): string
    {
        $res = $this->request('POST', '/register', [
            'login'                 => $login,
            'password'              => $password,
            'password_confirmation' => $password,
        ]);

        return (string) ($res['json']['token'] ?? '');
    }
}
