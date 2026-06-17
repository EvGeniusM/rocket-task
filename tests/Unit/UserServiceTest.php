<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Services\AuthService;
use App\Services\UserService;
use Tests\TestCase;

final class UserServiceTest extends TestCase
{
    private UserService $users;
    private int $aliceId;
    private int $bobId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserService();
        $auth = new AuthService();
        $this->aliceId = $auth->register('alice', 'secret123', 'secret123')['user']['id'];
        $this->bobId   = $auth->register('bob', 'secret123', 'secret123')['user']['id'];
    }

    public function testAllReturnsIdAndLoginOnly(): void
    {
        $all = $this->users->all();

        $this->assertCount(2, $all);
        $this->assertSame(['id', 'login'], array_keys($all[0]));
    }

    public function testGrantAccessAllowsHasAccess(): void
    {
        $this->users->grantAccess($this->aliceId, $this->bobId);

        $this->assertTrue($this->users->hasAccess($this->aliceId, $this->bobId));
    }

    public function testGrantAccessIsDirectional(): void
    {
        $this->users->grantAccess($this->aliceId, $this->bobId);

        $this->assertFalse($this->users->hasAccess($this->bobId, $this->aliceId));
    }

    public function testGrantAccessIsIdempotent(): void
    {
        $this->users->grantAccess($this->aliceId, $this->bobId);
        $this->users->grantAccess($this->aliceId, $this->bobId);

        $this->assertTrue($this->users->hasAccess($this->aliceId, $this->bobId));
    }

    public function testGrantAccessRejectsSelf(): void
    {
        $this->expectException(ValidationException::class);
        $this->users->grantAccess($this->aliceId, $this->aliceId);
    }

    public function testGrantAccessRejectsUnknownUser(): void
    {
        $this->expectException(ValidationException::class);
        $this->users->grantAccess($this->aliceId, 99999);
    }

    public function testHasAccessReturnsFalseWithoutGrant(): void
    {
        $this->assertFalse($this->users->hasAccess($this->aliceId, $this->bobId));
    }
}
