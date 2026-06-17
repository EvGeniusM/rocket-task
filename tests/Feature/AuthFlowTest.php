<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

final class AuthFlowTest extends FeatureTestCase
{
    public function testRegisterReturns201WithToken(): void
    {
        $res = $this->request('POST', '/register', [
            'login'                 => 'alice',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertSame(201, $res['status']);
        $this->assertSame(64, strlen((string) ($res['json']['token'] ?? '')));
        $this->assertSame('alice', $res['json']['user']['login'] ?? null);
    }

    public function testLoginReturnsTokenAfterRegister(): void
    {
        $this->authToken('alice', 'secret123');

        $res = $this->request('POST', '/login', ['login' => 'alice', 'password' => 'secret123']);

        $this->assertSame(200, $res['status']);
        $this->assertSame('alice', $res['json']['user']['login'] ?? null);
        $this->assertSame(64, strlen((string) ($res['json']['token'] ?? '')));
    }

    public function testRegisterRejectsMismatchedPasswords(): void
    {
        $res = $this->request('POST', '/register', [
            'login'                 => 'bob',
            'password'              => 'secret123',
            'password_confirmation' => 'nope',
        ]);

        $this->assertSame(422, $res['status']);
    }

    public function testRegisterRejectsDuplicateLogin(): void
    {
        $this->authToken('alice', 'secret123');

        $res = $this->request('POST', '/register', [
            'login'                 => 'alice',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertSame(422, $res['status']);
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $this->authToken('alice', 'secret123');

        $res = $this->request('POST', '/login', ['login' => 'alice', 'password' => 'wrong']);

        $this->assertSame(401, $res['status']);
    }
}
