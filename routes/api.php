<?php

declare(strict_types=1);

/** @var Router $router */

use App\Middleware\AuthMiddleware;
use Bramus\Router\Router;

$router->setNamespace('\App\Controllers');

$router->get('/docs', 'DocsController@ui');
$router->get('/docs/openapi.json', 'DocsController@spec');

$guard = [AuthMiddleware::class, 'handle'];
$router->before('GET|POST|PUT|DELETE', '/users.*', $guard);
$router->before('GET|POST|PUT|DELETE', '/books.*', $guard);
$router->before('GET', '/search.*', $guard);

$router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');

$router->get('/users', 'UserController@index');
$router->post('/users/grant-access', 'UserController@grantAccess');
$router->get('/users/(\d+)/books', 'UserController@books');

$router->get('/books', 'BookController@index');
$router->post('/books/external', 'BookController@storeExternal');
$router->post('/books', 'BookController@store');
$router->get('/books/(\d+)', 'BookController@show');
$router->put('/books/(\d+)', 'BookController@update');
$router->delete('/books/(\d+)', 'BookController@destroy');
$router->post('/books/(\d+)/restore', 'BookController@restore');

$router->get('/search', 'SearchController@search');
