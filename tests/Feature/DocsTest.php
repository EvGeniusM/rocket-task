<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

final class DocsTest extends FeatureTestCase
{
    public function testDocsUiIsPublicAndReturnsHtml(): void
    {
        $ch = curl_init(TEST_SERVER_URL . '/docs');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true]);
        $raw    = (string) curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $this->assertSame(200, $status);
        $this->assertStringContainsStringIgnoringCase('text/html', $raw);
        $this->assertStringContainsString('swagger-ui', $raw);
    }

    public function testSpecIsPublicAndReturnsJson(): void
    {
        $res = $this->request('GET', '/docs/openapi.json');

        $this->assertSame(200, $res['status']);
        $this->assertArrayHasKey('paths', $res['json']);
        $this->assertArrayHasKey('openapi', $res['json']);
    }

    public function testDocsDoesNotRequireToken(): void
    {
        $this->assertSame(200, $this->request('GET', '/docs/openapi.json')['status']);
    }
}
