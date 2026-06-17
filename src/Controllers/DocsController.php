<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use OpenApi\Generator;
use Psr\Log\NullLogger;

final class DocsController extends Controller
{
    public function ui(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mobile Library API</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
    SwaggerUIBundle({
        url: '/docs/openapi.json',
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
        layout: 'BaseLayout',
    });
</script>
</body>
</html>
HTML;
    }

    public function spec(): void
    {
        $openapi = Generator::scan([dirname(__DIR__)], ['logger' => new NullLogger()]);
        $data = json_decode($openapi->toJson(), true);
        // swagger-php omits 'paths' when no routes are annotated; ensure it is always present.
        if (!isset($data['paths'])) {
            $data['paths'] = (object) [];
        }
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
