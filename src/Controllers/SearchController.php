<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
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
        try {
            $results = $this->service->search((string) Request::query('q', ''));
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422);

            return;
        }

        Response::json(['results' => $results]);
    }
}
