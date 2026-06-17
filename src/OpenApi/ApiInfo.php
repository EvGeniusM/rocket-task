<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'REST API for a personal book library with shared access and external catalog search.',
    title: 'Mobile Library API',
)]
#[OA\Server(url: 'http://localhost:8000', description: 'Local development')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'opaque 64-char hex token',
)]
final class ApiInfo
{
}
