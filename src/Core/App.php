<?php

declare(strict_types=1);

namespace App\Core;

use Bramus\Router\Router;
use Dotenv\Dotenv;

final class App
{
    private Router $router;

    public function __construct()
    {
        $this->loadEnv();
        $this->router = new Router();
        $this->registerRoutes();
    }

    public function run(): void
    {
        $this->router->set404(static function (): void {
            Response::error('Not Found', 404);
        });

        $this->router->run();
    }

    private function loadEnv(): void
    {
        $root = dirname(__DIR__, 2);

        $appEnv = $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: null;
        $file   = $appEnv === 'testing' ? '.env.testing' : '.env';

        if (file_exists($root . '/' . $file)) {
            Dotenv::createImmutable($root, $file)->load();
        }
    }

    private function registerRoutes(): void
    {
        /** @var Router $router */
        $router = $this->router;

        require dirname(__DIR__, 2) . '/routes/api.php';
    }
}
