<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\ValidationException;
use App\Models\LibraryAccess;
use App\Models\User;

final class UserService
{
    public function all(): array
    {
        $rows = Database::connection()
            ->query('SELECT id, login FROM ' . User::TABLE . ' ORDER BY id')
            ->fetchAll();

        return array_map(
            static fn (array $row): array => ['id' => (int) $row['id'], 'login' => $row['login']],
            $rows,
        );
    }

    public function grantAccess(int $ownerId, int $granteeId): void
    {
        if ($granteeId <= 0) {
            throw new ValidationException('A valid user_id is required.');
        }

        if ($granteeId === $ownerId) {
            throw new ValidationException('You cannot grant access to yourself.');
        }

        if (!$this->userExists($granteeId)) {
            throw new ValidationException('User not found.');
        }

        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO ' . LibraryAccess::TABLE
            . ' (owner_id, grantee_id) VALUES (:owner_id, :grantee_id)'
        );
        $stmt->execute([
            'owner_id' => $ownerId,
            'grantee_id' => $granteeId,
        ]);
    }

    public function hasAccess(int $ownerId, int $granteeId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM ' . LibraryAccess::TABLE
            . ' WHERE owner_id = :owner_id AND grantee_id = :grantee_id LIMIT 1'
        );
        $stmt->execute([
            'owner_id' => $ownerId,
            'grantee_id' => $granteeId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function userExists(int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM ' . User::TABLE . ' WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);

        return $stmt->fetchColumn() !== false;
    }
}
