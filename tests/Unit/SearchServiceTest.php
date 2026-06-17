<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Services\SearchService;
use Tests\Support\FakeHttpClient;
use Tests\TestCase;

final class SearchServiceTest extends TestCase
{
    public function testSearchRejectsEmptyQuery(): void
    {
        $this->expectException(ValidationException::class);
        (new SearchService(new FakeHttpClient()))->search('   ');
    }

    public function testSearchReturnsGoogleResults(): void
    {
        $http = new FakeHttpClient();
        $http->on('https://www.googleapis.com/books/v1/volumes', [
            'items' => [
                ['id' => 'g1', 'volumeInfo' => ['title' => 'Clean Code', 'authors' => ['Robert Martin']]],
                ['id' => 'g2', 'volumeInfo' => ['title' => 'The Pragmatic Programmer']],
            ],
        ]);

        $results = (new SearchService($http))->search('code');

        $googleItems = array_filter($results, fn (array $r) => $r['source'] === 'google');
        $this->assertCount(2, $googleItems);

        $first = array_values($googleItems)[0];
        $this->assertSame('google:g1', $first['external_id']);
        $this->assertSame('Clean Code', $first['title']);
    }

    public function testSearchMergesMifResults(): void
    {
        $http = new FakeHttpClient();
        $http->on('https://www.googleapis.com/books/v1/volumes', null);
        $http->on('https://www.mann-ivanov-ferber.ru/book/search.ajax', [
            [
                'items' => [
                    ['id' => 'm1', 'title' => 'Книга про мышление', 'url' => '/book/m1'],
                ],
            ],
        ]);

        $results = (new SearchService($http))->search('мышление');

        $mifItems = array_filter($results, fn (array $r) => $r['source'] === 'mif');
        $this->assertCount(1, $mifItems);
        $this->assertSame('mif:m1', array_values($mifItems)[0]['external_id']);
    }

    public function testSearchDegracesGracefullyOnUpstreamNull(): void
    {
        $http = new FakeHttpClient();

        $results = (new SearchService($http))->search('anything');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testResolveGoogleVolumeReturnsData(): void
    {
        $http = new FakeHttpClient();
        $http->on('https://www.googleapis.com/books/v1/volumes/abc123', [
            'volumeInfo' => ['title' => 'Dune', 'description' => 'Sci-fi classic.'],
        ]);

        $volume = (new SearchService($http))->resolveGoogleVolume('abc123');

        $this->assertSame('Dune', $volume['title']);
        $this->assertSame('Sci-fi classic.', $volume['description']);
    }

    public function testResolveGoogleVolumeReturnsNullOnMiss(): void
    {
        $volume = (new SearchService(new FakeHttpClient()))->resolveGoogleVolume('missing');

        $this->assertNull($volume);
    }
}
