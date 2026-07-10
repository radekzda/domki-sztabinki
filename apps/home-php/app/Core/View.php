<?php

declare(strict_types=1);

final class View
{
    public static function render(string $view, array $data = []): string
    {
        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException('Widok nie istnieje: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if ($content === false) {
            $content = '';
        }

        ob_start();
        require dirname(__DIR__) . '/Views/layouts/main.php';

        $layout = ob_get_clean();

        if ($layout === false) {
            return $content;
        }

        return $layout;
    }
}