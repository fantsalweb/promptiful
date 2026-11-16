<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('app', 'Home::tool');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function (RouteCollection $routes): void {
    $routes->post('generate', 'Generate::index');
    $routes->get('history', 'History::index');
    $routes->post('history', 'History::action');
});
