<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Auth;
use App\Core\Database;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $pdo = Database::connection();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['auth_tokens', 'library_access', 'books', 'users'] as $table) {
            $pdo->exec("TRUNCATE TABLE {$table}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        Auth::setUser(null);
    }
}
