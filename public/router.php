<?php

declare(strict_types=1);

// PHP built-in server router: serve real files directly, route everything else through index.php.
$filename = __DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

// Fix SCRIPT_NAME so bramus/router computes the correct base path (it uses SCRIPT_NAME to strip the
// subdirectory prefix from REQUEST_URI). When the built-in server runs with a router script it sets
// SCRIPT_NAME to the requested URI, which corrupts getBasePath(). Reset it to the real entry point.
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/index.php';
