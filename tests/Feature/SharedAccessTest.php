<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

final class SharedAccessTest extends FeatureTestCase
{
    public function testGranteeCanListOwnerBooksAfterGrant(): void
    {
        $aliceToken = $this->authToken('alice', 'secret123');
        $bobReg     = $this->request('POST', '/register', [
            'login'                 => 'bob',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        $bobToken = (string) ($bobReg['json']['token'] ?? '');
        $bobId    = (int) ($bobReg['json']['user']['id'] ?? 0);

        $this->request('POST', '/books', ['title' => 'Shared', 'text' => 'hi'], $aliceToken);

        $aliceLogin = $this->request('POST', '/login', ['login' => 'alice', 'password' => 'secret123']);
        $aliceId    = (int) ($aliceLogin['json']['user']['id'] ?? 0);

        $grant = $this->request('POST', '/users/grant-access', ['user_id' => $bobId], $aliceToken);
        $this->assertSame(200, $grant['status']);

        $ok = $this->request('GET', "/users/{$aliceId}/books", null, $bobToken);
        $this->assertSame(200, $ok['status']);
        $this->assertCount(1, $ok['json']['books'] ?? []);
    }

    public function testWithoutGrantAccessIsForbidden(): void
    {
        $aliceToken = $this->authToken('alice', 'secret123');
        $aliceLogin = $this->request('POST', '/login', ['login' => 'alice', 'password' => 'secret123']);
        $aliceId    = (int) ($aliceLogin['json']['user']['id'] ?? 0);

        $this->request('POST', '/books', ['title' => 'Private', 'text' => 'x'], $aliceToken);

        $bobToken = $this->authToken('bob', 'secret123');

        $res = $this->request('GET', "/users/{$aliceId}/books", null, $bobToken);
        $this->assertSame(403, $res['status']);
    }

    public function testOwnerCanAlwaysAccessOwnLibrary(): void
    {
        $aliceToken = $this->authToken('alice', 'secret123');
        $aliceLogin = $this->request('POST', '/login', ['login' => 'alice', 'password' => 'secret123']);
        $aliceId    = (int) ($aliceLogin['json']['user']['id'] ?? 0);

        $this->request('POST', '/books', ['title' => 'Mine', 'text' => 'content'], $aliceToken);

        $res = $this->request('GET', "/users/{$aliceId}/books", null, $aliceToken);
        $this->assertSame(200, $res['status']);
        $this->assertCount(1, $res['json']['books'] ?? []);

        $book = $res['json']['books'][0];
        $this->assertSame(TEST_SERVER_URL . '/books/' . $book['id'], $book['link']);
    }

    public function testGrantAccessRejectsSelf(): void
    {
        $aliceToken = $this->authToken('alice', 'secret123');
        $aliceLogin = $this->request('POST', '/login', ['login' => 'alice', 'password' => 'secret123']);
        $aliceId    = (int) ($aliceLogin['json']['user']['id'] ?? 0);

        $res = $this->request('POST', '/users/grant-access', ['user_id' => $aliceId], $aliceToken);
        $this->assertSame(422, $res['status']);
    }
}
