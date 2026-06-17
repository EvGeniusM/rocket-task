<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\SearchService;
use OpenApi\Attributes as OA;

final class SearchController extends Controller
{
    private SearchService $service;

    public function __construct()
    {
        $this->service = new SearchService();
    }

    #[OA\Get(
        path: '/search',
        operationId: 'search',
        summary: 'Search external catalogs (Google Books + MIF)',
        security: [['bearerAuth' => []]],
        tags: ['Search'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: true,
                description: 'Search query string',
                schema: new OA\Schema(type: 'string'),
                example: 'clean code',
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results from all sources',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'results',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'source', type: 'string', example: 'google'),
                                    new OA\Property(property: 'external_id', type: 'string', example: 'google:zyTCAlFPjgYC'),
                                    new OA\Property(property: 'title', type: 'string', example: 'Clean Code'),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Missing or empty query'),
        ],
    )]
    public function search(): void
    {
        try {
            $results = $this->service->search((string) Request::query('q', ''));
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422);

            return;
        }

        Response::json(['results' => $results]);
    }
}
