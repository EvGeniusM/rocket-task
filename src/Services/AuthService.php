<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\ValidationException;
use App\Models\AuthToken;
use App\Models\User;

final class AuthService
{
    public function register(string $login, string $password, string $confirmPassword): array
    {
        $login = trim($login);

        if ($login === '') {
            throw new ValidationException('Login is required.');
        }

        if (mb_strlen($login) > 190) {
            throw new ValidationException('Login must not exceed 190 characters.');
        }

        if (mb_strlen($password) < 6) {
            throw new ValidationException('Password must be at least 6 characters long.');
        }

        if ($password !== $confirmPassword) {
            throw new ValidationException('Password confirmation does not match.');
        }

        if ($this->findByLogin($login) !== null) {
            throw new ValidationException('Login is already taken.');
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO ' . User::TABLE . ' (login, password_hash) VALUES (:login, :hash)'
        );
        $stmt->execute([
            'login' => $login,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $userId = (int) $pdo->lastInsertId();

        return [
            'token' => $this->issueToken($userId),
            'user' => ['id' => $userId, 'login' => $login],
        ];
    }

    public function login(string $login, string $password): array
    {
        $user = $this->findByLogin(trim($login));

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            throw new ValidationException('Invalid login or password.');
        }

        return [
            'token' => $this->issueToken((int) $user['id']),
            'user' => ['id' => (int) $user['id'], 'login' => $user['login']],
        ];
    }

    public function userFromToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.login
               FROM ' . AuthToken::TABLE . ' t
               JOIN ' . User::TABLE . ' u ON u.id = t.user_id
              WHERE t.token = :token
                AND (t.expires_at IS NULL OR t.expires_at > NOW())
              LIMIT 1'
        );
        $stmt->execute(['token' => $token]);

        $user = $stmt->fetch();

        if ($user === false) {
            return null;
        }

        $user['id'] = (int) $user['id'];

        return $user;
    }

    private function findByLogin(string $login): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, login, password_hash FROM ' . User::TABLE . ' WHERE login = :login LIMIT 1'
        );
        $stmt->execute(['login' => $login]);

        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    private function issueToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));

        $stmt = Database::connection()->prepare(
            'INSERT INTO ' . AuthToken::TABLE . ' (user_id, token) VALUES (:user_id, :token)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token' => $token,
        ]);

        return $token;
    }
}
