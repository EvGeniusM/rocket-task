<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Services\AuthService;
use Tests\TestCase;

final class AuthServiceTest extends TestCase
{
    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService();
    }

    public function testRegisterReturnsTokenAndUser(): void
    {
        $result = $this->service->register('alice', 'secret123', 'secret123');

        $this->assertSame('alice', $result['user']['login']);
        $this->assertIsInt($result['user']['id']);
        $this->assertSame(64, strlen($result['token']));
    }

    public function testRegisterRejectsMismatchedConfirmation(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->register('alice', 'secret123', 'different');
    }

    public function testRegisterRejectsTooShortPassword(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->register('alice', 'abc', 'abc');
    }

    public function testRegisterRejectsDuplicateLogin(): void
    {
        $this->service->register('alice', 'secret123', 'secret123');

        $this->expectException(ValidationException::class);
        $this->service->register('alice', 'secret123', 'secret123');
    }

    public function testRegisterRejectsEmptyLogin(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->register('', 'secret123', 'secret123');
    }

    public function testLoginReturnsTokenForValidCredentials(): void
    {
        $this->service->register('alice', 'secret123', 'secret123');

        $result = $this->service->login('alice', 'secret123');
        $this->assertSame(64, strlen($result['token']));
        $this->assertSame('alice', $result['user']['login']);
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $this->service->register('alice', 'secret123', 'secret123');

        $this->expectException(ValidationException::class);
        $this->service->login('alice', 'wrong');
    }

    public function testLoginRejectsUnknownUser(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->login('nobody', 'secret123');
    }

    public function testUserFromTokenResolvesIssuedToken(): void
    {
        $token = $this->service->register('alice', 'secret123', 'secret123')['token'];

        $user = $this->service->userFromToken($token);
        $this->assertNotNull($user);
        $this->assertSame('alice', $user['login']);
        $this->assertIsInt($user['id']);
    }

    public function testUserFromTokenReturnsNullForUnknownToken(): void
    {
        $this->assertNull($this->service->userFromToken('deadbeef'));
    }
}
