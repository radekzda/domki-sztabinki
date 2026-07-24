<?php

declare(strict_types=1);

final class Url
{
    private static ?string $basePath = null;

    public static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        $appUrl = trim(
            (string) Env::get(
                'APP_URL',
                ''
            )
        );

        if ($appUrl === '') {
            self::$basePath = '';

            return self::$basePath;
        }

        $path = parse_url(
            $appUrl,
            PHP_URL_PATH
        );

        if (
            !is_string($path)
            || $path === ''
            || $path === '/'
        ) {
            self::$basePath = '';

            return self::$basePath;
        }

        self::$basePath =
            '/'
            . trim(
                $path,
                '/'
            );

        return self::$basePath;
    }

    public static function to(string $path): string
    {
        if ($path === '') {
            $basePath = self::basePath();

            return $basePath !== ''
                ? $basePath . '/'
                : '/';
        }

        if (
            !str_starts_with(
                $path,
                '/'
            )
            || str_starts_with(
                $path,
                '//'
            )
        ) {
            return $path;
        }

        $basePath = self::basePath();

        if ($basePath === '') {
            return $path;
        }

        if (
            $path === $basePath
            || str_starts_with(
                $path,
                $basePath . '/'
            )
            || str_starts_with(
                $path,
                $basePath . '?'
            )
            || str_starts_with(
                $path,
                $basePath . '#'
            )
        ) {
            return $path;
        }

        if ($path === '/') {
            return $basePath . '/';
        }

        return $basePath . $path;
    }

    public static function stripBasePath(
        string $path
    ): string {
        $basePath = self::basePath();

        if ($basePath === '') {
            return $path;
        }

        if ($path === $basePath) {
            return '/';
        }

        if (
            str_starts_with(
                $path,
                $basePath . '/'
            )
        ) {
            $strippedPath = substr(
                $path,
                strlen($basePath)
            );

            return $strippedPath !== ''
                ? $strippedPath
                : '/';
        }

        return $path;
    }

    public static function rewriteHtml(
        string $html
    ): string {
        if (
            $html === ''
            || self::basePath() === ''
        ) {
            return $html;
        }

        $rewritten = preg_replace_callback(
            '~\b(href|src|action|formaction)\s*=\s*(["\'])(/(?!/)[^"\']*)\2~i',
            static function (
                array $matches
            ): string {
                return $matches[1]
                    . '='
                    . $matches[2]
                    . self::to(
                        $matches[3]
                    )
                    . $matches[2];
            },
            $html
        );

        return is_string($rewritten)
            ? $rewritten
            : $html;
    }
}
