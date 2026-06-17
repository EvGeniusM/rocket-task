<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

final class BookCrudTest extends FeatureTestCase
{
    public function testFullLifecycle(): void
    {
        $token = $this->authToken('alice', 'secret123');

        $create = $this->request('POST', '/books', ['title' => 'Dune', 'text' => 'Spice must flow'], $token);
        $this->assertSame(201, $create['status']);
        $id = (int) ($create['json']['id'] ?? 0);
        $this->assertGreaterThan(0, $id);

        $list = $this->request('GET', '/books', null, $token);
        $this->assertSame(200, $list['status']);
        $this->assertCount(1, $list['json']['books'] ?? []);

        $show = $this->request('GET', "/books/{$id}", null, $token);
        $this->assertSame(200, $show['status']);
        $this->assertSame('Spice must flow', $show['json']['content'] ?? null);

        $update = $this->request('PUT', "/books/{$id}", ['title' => 'Dune II'], $token);
        $this->assertSame(200, $update['status']);
        $this->assertSame('Dune II', $update['json']['title'] ?? null);

        $delete = $this->request('DELETE', "/books/{$id}", null, $token);
        $this->assertSame(200, $delete['status']);

        $this->assertSame(404, $this->request('GET', "/books/{$id}", null, $token)['status']);

        $restore = $this->request('POST', "/books/{$id}/restore", null, $token);
        $this->assertSame(200, $restore['status']);
        $this->assertSame(200, $this->request('GET', "/books/{$id}", null, $token)['status']);
    }

    public function testCreateRequiresTitle(): void
    {
        $token = $this->authToken('alice', 'secret123');
        $res   = $this->request('POST', '/books', ['text' => 'orphan'], $token);

        $this->assertSame(422, $res['status']);
    }

    public function testCreateRequiresTextOrFile(): void
    {
        $token = $this->authToken('alice', 'secret123');
        $res   = $this->request('POST', '/books', ['title' => 'No content'], $token);

        $this->assertSame(422, $res['status']);
    }

    public function testShowReturns404ForUnknownBook(): void
    {
        $token = $this->authToken('alice', 'secret123');
        $res   = $this->request('GET', '/books/99999', null, $token);

        $this->assertSame(404, $res['status']);
    }

    public function testCannotAccessAnotherUsersBooksWithoutGrant(): void
    {
        $alice = $this->authToken('alice', 'secret123');
        $bob   = $this->authToken('bob', 'secret123');

        $create = $this->request('POST', '/books', ['title' => 'Alice Book', 'text' => 'private'], $alice);
        $id     = (int) ($create['json']['id'] ?? 0);

        $res = $this->request('GET', "/books/{$id}", null, $bob);
        $this->assertSame(404, $res['status']);
    }
}
