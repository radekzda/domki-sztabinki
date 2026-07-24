<?php

declare(strict_types=1);

require_once __DIR__ . '/Url.php';

final class Response
{
    public static function html(string $content, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');
        echo Url::rewriteHtml($content);
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . Url::to($path));
        exit;
    }
}
