<?php

declare(strict_types=1);

use App\Core\Database;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (!file_exists($root . '/.env.testing')) {
    fwrite(STDERR, "Missing .env.testing — copy .env.testing.example and create the rocket_task_test DB.\n");
    exit(1);
}

Dotenv::createImmutable($root, '.env.testing')->load();

$schema = file_get_contents($root . '/database/schema.sql');
Database::connection()->exec($schema);

define('TEST_SERVER_HOST', '127.0.0.1');
define('TEST_SERVER_PORT', 8123);
define('TEST_SERVER_URL', 'http://' . TEST_SERVER_HOST . ':' . TEST_SERVER_PORT);

$serverStrings = array_filter($_SERVER, fn ($v) => is_string($v));
$env = array_merge($serverStrings, ['APP_ENV' => 'testing']);

$cmd = sprintf(
    '%s -S %s:%d -t %s',
    PHP_BINARY,
    TEST_SERVER_HOST,
    TEST_SERVER_PORT,
    escapeshellarg($root . '/public')
);

$server = proc_open(
    $cmd,
    [
        0 => ['pipe', 'r'],
        1 => ['file', tempnam(sys_get_temp_dir(), 'srv_out'), 'a'],
        2 => ['file', tempnam(sys_get_temp_dir(), 'srv_err'), 'a'],
    ],
    $pipes,
    $root,
    $env
);

if (!is_resource($server)) {
    fwrite(STDERR, "Failed to start test server.\n");
    exit(1);
}

register_shutdown_function(static function () use ($server): void {
    if (is_resource($server)) {
        proc_terminate($server);
        proc_close($server);
    }
});

for ($i = 0; $i < 50; $i++) {
    $conn = @fsockopen(TEST_SERVER_HOST, TEST_SERVER_PORT, $errno, $errstr, 0.2);
    if ($conn !== false) {
        fclose($conn);
        break;
    }
    usleep(100_000);
}
