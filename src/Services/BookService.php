<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Book;

final class BookService
{
    public function __construct(
        private readonly UserService $access = new UserService(),
        private readonly SearchService $search = new SearchService(),
    ) {
    }

    public function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, title, created_at, updated_at
               FROM ' . Book::TABLE . '
              WHERE owner_id = :owner_id AND deleted_at IS NULL
              ORDER BY id'
        );
        $stmt->execute(['owner_id' => $userId]);

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ],
            $stmt->fetchAll(),
        );
    }

    public function find(int $bookId, int $requesterId): ?array
    {
        $book = $this->fetch($bookId);

        if ($book === null) {
            return null;
        }

        $ownerId = (int) $book['owner_id'];

        if ($ownerId !== $requesterId && !$this->access->hasAccess($ownerId, $requesterId)) {
            return null;
        }

        return $this->present($book);
    }

    public function create(int $ownerId, string $title, ?string $text, ?array $file): array
    {
        $title = trim($title);

        if ($title === '') {
            throw new ValidationException('Title is required.');
        }

        $content = $file !== null ? $this->extractText($file) : $text;

        if ($content === null || $content === '') {
            throw new ValidationException('Either text or a file is required.');
        }

        return $this->store($ownerId, $title, $content);
    }

    public function update(int $bookId, int $ownerId, ?string $title, ?string $text): array
    {
        $book = $this->fetchOwned($bookId, $ownerId);

        $newTitle = $title !== null ? trim($title) : $book['title'];

        if ($newTitle === '') {
            throw new ValidationException('Title must not be empty.');
        }

        $newContent = $text ?? $book['content'];

        $stmt = Database::connection()->prepare(
            'UPDATE ' . Book::TABLE . '
                SET title = :title, content = :content
              WHERE id = :id AND owner_id = :owner_id'
        );
        $stmt->execute([
            'title' => $newTitle,
            'content' => $newContent,
            'id' => $bookId,
            'owner_id' => $ownerId,
        ]);

        return $this->present($this->fetch($bookId));
    }

    public function softDelete(int $bookId, int $ownerId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE ' . Book::TABLE . '
                SET deleted_at = NOW()
              WHERE id = :id AND owner_id = :owner_id AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $bookId, 'owner_id' => $ownerId]);

        if ($stmt->rowCount() === 0) {
            throw new NotFoundException('Book not found.');
        }
    }

    public function restore(int $bookId, int $ownerId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE ' . Book::TABLE . '
                SET deleted_at = NULL
              WHERE id = :id AND owner_id = :owner_id AND deleted_at IS NOT NULL'
        );
        $stmt->execute(['id' => $bookId, 'owner_id' => $ownerId]);

        if ($stmt->rowCount() === 0) {
            throw new NotFoundException('No deleted book to restore.');
        }
    }

    public function createFromExternal(int $ownerId, string $externalId, ?string $title, ?string $text): array
    {
        $externalId = trim($externalId);

        if (!str_contains($externalId, ':')) {
            throw new ValidationException('external_id must be in "source:id" format.');
        }

        [$source, $sourceId] = explode(':', $externalId, 2);

        if ($sourceId === '') {
            throw new ValidationException('external_id is missing the source identifier.');
        }

        $title = $title !== null ? trim($title) : null;
        $content = $text;

        // Google exposes a per-volume detail endpoint, so we can resolve the book
        // authoritatively by id. Other sources rely on the client-supplied metadata.
        if ($source === 'google') {
            $volume = $this->search->resolveGoogleVolume($sourceId);

            if ($volume !== null) {
                $title = $title !== null && $title !== '' ? $title : ($volume['title'] ?? null);
                $content ??= $volume['description'];
            }
        }

        if ($title === null || $title === '') {
            throw new ValidationException('Could not resolve a title; provide "title".');
        }

        // External entries may carry no body text; keep the row meaningful.
        if ($content === null || $content === '') {
            $content = $title;
        }

        return $this->store($ownerId, $title, $content);
    }

    private function extractText(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ValidationException('File upload failed.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

        if ($extension !== 'txt') {
            throw new ValidationException('Only .txt files are supported.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if (!is_uploaded_file($tmp)) {
            throw new ValidationException('Invalid uploaded file.');
        }

        $content = file_get_contents($tmp);

        if ($content === false) {
            throw new ValidationException('Could not read the uploaded file.');
        }

        return $content;
    }

    private function fetchOwned(int $bookId, int $ownerId): array
    {
        $book = $this->fetch($bookId);

        if ($book === null || (int) $book['owner_id'] !== $ownerId) {
            throw new NotFoundException('Book not found.');
        }

        return $book;
    }

    private function fetch(int $bookId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, owner_id, title, content, created_at, updated_at
               FROM ' . Book::TABLE . '
              WHERE id = :id AND deleted_at IS NULL
              LIMIT 1'
        );
        $stmt->execute(['id' => $bookId]);

        $book = $stmt->fetch();

        return $book === false ? null : $book;
    }

    private function store(int $ownerId, string $title, string $content): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO ' . Book::TABLE . ' (owner_id, title, content) VALUES (:owner_id, :title, :content)'
        );
        $stmt->execute([
            'owner_id' => $ownerId,
            'title' => $title,
            'content' => $content,
        ]);

        return $this->present($this->fetch((int) $pdo->lastInsertId()));
    }

    private function present(array $book): array
    {
        return [
            'id' => (int) $book['id'],
            'title' => $book['title'],
            'content' => $book['content'],
            'created_at' => $book['created_at'],
            'updated_at' => $book['updated_at'],
        ];
    }
}
