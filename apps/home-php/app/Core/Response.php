<?php

declare(strict_types=1);

final class Response
{
    public static function html(string $content, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}