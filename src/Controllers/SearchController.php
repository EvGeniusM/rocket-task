<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\SearchService;

final class SearchController extends Controller
{
    private SearchService $service;

    public function __construct()
    {
        $this->service = new SearchService();
    }

    public function search(): void
    {
        Response::notImplemented('GET /search');
    }
}
