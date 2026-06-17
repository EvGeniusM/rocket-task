<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

final class GuardTest extends FeatureTestCase
{
    public function testGuardedRouteRejectsMissingToken(): void
    {
        $res = $this->request('GET', '/books');

        $this->assertSame(401, $res['status']);
    }

    public function testGuardedRouteRejectsBogusToken(): void
    {
        $res = $this->request('GET', '/books', null, 'not-a-real-token');

        $this->assertSame(401, $res['status']);
    }

    public function testGuardedUsersRouteRejectsMissingToken(): void
    {
        $res = $this->request('GET', '/users');

        $this->assertSame(401, $res['status']);
    }

    public function testGuardedSearchRouteRejectsMissingToken(): void
    {
        $res = $this->request('GET', '/search?q=test');

        $this->assertSame(401, $res['status']);
    }

    public function testPublicRegisterRouteIsAccessibleWithoutToken(): void
    {
        $res = $this->request('POST', '/register', [
            'login'                 => 'tester',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertSame(201, $res['status']);
    }
}
