<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/app/Config/config.php';

date_default_timezone_set($config['timezone'] ?? 'Europe/Warsaw');

require dirname(__DIR__) . '/app/Core/Response.php';
require dirname(__DIR__) . '/app/Core/View.php';
require dirname(__DIR__) . '/app/Core/Router.php';

$router = new Router();

$router->get('/', function (): void {
    Response::html(View::render('pages/home', [
        'title' => 'Strona główna',
    ]));
});

$router->get('/admin', function (): void {
    Response::html(View::render('pages/admin', [
        'title' => 'Panel administratora',
    ]));
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');