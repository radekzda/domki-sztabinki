<?php

declare(strict_types=1);

final class Router
{
    /**
     * @var array<string, callable>
     */
    private array $getRoutes = [];

    /**
     * @var array<string, callable>
     */
    private array $postRoutes = [];

    public function get(string $path, callable $handler): void
    {
        $this->getRoutes[$this->normalizePath($path)] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->postRoutes[$this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        $path = $this->normalizePath($path);
        $method = strtoupper($method);

        $routes = match ($method) {
            'GET' => $this->getRoutes,
            'POST' => $this->postRoutes,
            default => [],
        };

        if ($routes === []) {
            Response::html(View::render('pages/error', [
                'title' => 'Błąd',
                'message' => 'Nieobsługiwana metoda żądania.',
            ]), 405);

            return;
        }

        if (!array_key_exists($path, $routes)) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono strony',
                'message' => 'Podana strona nie istnieje.',
            ]), 404);

            return;
        }

        $handler = $routes[$path];
        $handler();
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        if ($path === '//') {
            return '/';
        }

        return $path;
    }
}