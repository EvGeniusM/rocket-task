<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\AuthService;
use App\Services\BookService;
use App\Services\SearchService;
use Tests\Support\FakeHttpClient;
use Tests\TestCase;

final class BookServiceTest extends TestCase
{
    private BookService $books;
    private int $aliceId;
    private int $bobId;

    protected function setUp(): void
    {
        parent::setUp();
        $auth = new AuthService();
        $this->aliceId = $auth->register('alice', 'secret123', 'secret123')['user']['id'];
        $this->bobId   = $auth->register('bob', 'secret123', 'secret123')['user']['id'];
        $this->books   = new BookService();
    }

    public function testCreateWithTextReturnsStoredBook(): void
    {
        $book = $this->books->create($this->aliceId, 'Dune', 'Spice must flow', null);

        $this->assertSame('Dune', $book['title']);
        $this->assertSame('Spice must flow', $book['content']);
        $this->assertIsInt($book['id']);
    }

    public function testCreateRequiresTitle(): void
    {
        $this->expectException(ValidationException::class);
        $this->books->create($this->aliceId, '  ', 'text', null);
    }

    public function testCreateRequiresTextOrFile(): void
    {
        $this->expectException(ValidationException::class);
        $this->books->create($this->aliceId, 'Title', null, null);
    }

    public function testCreateExtractsContentFromTxtFile(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'bk');
        file_put_contents($tmp, "Chapter one.\nIt was a dark night.");

        $file = [
            'name'     => 'book.txt',
            'type'     => 'text/plain',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmp),
        ];

        $book = $this->books->create($this->aliceId, 'From File', null, $file);
        $this->assertStringContainsString('dark night', $book['content']);

        unlink($tmp);
    }

    public function testCreateRejectsFailedUpload(): void
    {
        $file = ['name' => 'book.txt', 'type' => 'text/plain', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0];

        $this->expectException(ValidationException::class);
        $this->books->create($this->aliceId, 'Bad', null, $file);
    }

    public function testForUserExcludesSoftDeleted(): void
    {
        $book = $this->books->create($this->aliceId, 'A', 'x', null);
        $this->books->create($this->aliceId, 'B', 'y', null);
        $this->books->softDelete($book['id'], $this->aliceId);

        $list = $this->books->forUser($this->aliceId);
        $this->assertCount(1, $list);
        $this->assertSame('B', $list[0]['title']);
    }

    public function testOwnerCanFindOwnBook(): void
    {
        $id   = $this->books->create($this->aliceId, 'A', 'x', null)['id'];
        $found = $this->books->find($id, $this->aliceId);

        $this->assertNotNull($found);
        $this->assertSame('A', $found['title']);
    }

    public function testNonOwnerWithoutAccessCannotFind(): void
    {
        $id = $this->books->create($this->aliceId, 'A', 'x', null)['id'];

        $this->assertNull($this->books->find($id, $this->bobId));
    }

    public function testUpdateChangesTitleAndContent(): void
    {
        $id     = $this->books->create($this->aliceId, 'A', 'x', null)['id'];
        $updated = $this->books->update($id, $this->aliceId, 'A2', 'y');

        $this->assertSame('A2', $updated['title']);
        $this->assertSame('y', $updated['content']);
    }

    public function testUpdateRejectsNonOwner(): void
    {
        $id = $this->books->create($this->aliceId, 'A', 'x', null)['id'];

        $this->expectException(NotFoundException::class);
        $this->books->update($id, $this->bobId, 'hax', null);
    }

    public function testSoftDeleteHidesBook(): void
    {
        $id = $this->books->create($this->aliceId, 'A', 'x', null)['id'];
        $this->books->softDelete($id, $this->aliceId);

        $this->assertNull($this->books->find($id, $this->aliceId));
    }

    public function testRestoreUnhidesBook(): void
    {
        $id = $this->books->create($this->aliceId, 'A', 'x', null)['id'];
        $this->books->softDelete($id, $this->aliceId);
        $this->books->restore($id, $this->aliceId);

        $this->assertNotNull($this->books->find($id, $this->aliceId));
    }

    public function testRestoreFailsWhenNotDeleted(): void
    {
        $id = $this->books->create($this->aliceId, 'A', 'x', null)['id'];

        $this->expectException(NotFoundException::class);
        $this->books->restore($id, $this->aliceId);
    }

    public function testSoftDeleteFailsForNonOwner(): void
    {
        $id = $this->books->create($this->aliceId, 'A', 'x', null)['id'];

        $this->expectException(NotFoundException::class);
        $this->books->softDelete($id, $this->bobId);
    }

    public function testCreateFromExternalResolvesGoogleVolume(): void
    {
        $http = new FakeHttpClient();
        $http->on('https://www.googleapis.com/books/v1/volumes/xyz123', [
            'volumeInfo' => ['title' => 'Clean Code', 'description' => 'About clean code.'],
        ]);

        $books = new BookService(search: new SearchService($http));
        $book  = $books->createFromExternal($this->aliceId, 'google:xyz123', null, null);

        $this->assertSame('Clean Code', $book['title']);
        $this->assertSame('About clean code.', $book['content']);
    }

    public function testCreateFromExternalAllowsClientSuppliedTitle(): void
    {
        $books = new BookService(search: new SearchService(new FakeHttpClient()));
        $book  = $books->createFromExternal($this->aliceId, 'mif:99', 'My Title', 'Some text');

        $this->assertSame('My Title', $book['title']);
        $this->assertSame('Some text', $book['content']);
    }

    public function testCreateFromExternalRejectsInvalidFormat(): void
    {
        $this->expectException(ValidationException::class);
        $this->books->createFromExternal($this->aliceId, 'nocolon', null, null);
    }

    public function testCreateFromExternalRejectsUnresolvableWithoutTitle(): void
    {
        $books = new BookService(search: new SearchService(new FakeHttpClient()));

        $this->expectException(ValidationException::class);
        $books->createFromExternal($this->aliceId, 'google:missing', null, null);
    }
}
