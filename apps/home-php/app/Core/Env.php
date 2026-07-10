<?php

declare(strict_types=1);

final class Env
{
    /**
     * @var array<string, string>
     */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $separatorPosition = strpos($line, '=');

            if ($separatorPosition === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separatorPosition));
            $value = trim(substr($line, $separatorPosition + 1));

            if ($key === '') {
                continue;
            }

            $value = self::unquote($value);

            self::$values[$key] = $value;

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }

            $_ENV[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        if (array_key_exists($key, $_ENV)) {
            $envValue = $_ENV[$key];

            if (is_string($envValue)) {
                return $envValue;
            }
        }

        return $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    private static function unquote(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $firstCharacter = $value[0];
        $lastCharacter = $value[strlen($value) - 1];

        if (
            ($firstCharacter === '"' && $lastCharacter === '"') ||
            ($firstCharacter === "'" && $lastCharacter === "'")
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}