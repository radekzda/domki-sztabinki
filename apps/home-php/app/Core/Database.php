<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $database = Env::get('DB_DATABASE', '');
        $username = Env::get('DB_USERNAME', '');
        $password = Env::get('DB_PASSWORD', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        if (!self::isConfigured()) {
            throw new RuntimeException('Brakuje konfiguracji bazy danych MySQL.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        self::$connection = new PDO($dsn, (string) $username, (string) $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }

    public static function isConfigured(): bool
    {
        $database = Env::get('DB_DATABASE', '');
        $username = Env::get('DB_USERNAME', '');

        if ($database === null || $username === null) {
            return false;
        }

        $database = trim($database);
        $username = trim($username);

        if ($database === '' || $username === '') {
            return false;
        }

        if ($database === 'CHANGE_ME_DATABASE' || $username === 'CHANGE_ME_USERNAME') {
            return false;
        }

        return true;
    }

    /**
     * @return array<int, array{label: string, status: string, value: string}>
     */
    public static function diagnostics(): array
    {
        $checks = [];

        $checks[] = [
            'label' => 'Konfiguracja DB',
            'status' => self::isConfigured() ? 'success' : 'warning',
            'value' => self::isConfigured()
                ? 'DB_DATABASE i DB_USERNAME są ustawione'
                : 'brak konfiguracji albo wartości CHANGE_ME',
        ];

        $checks[] = [
            'label' => 'Rozszerzenie PDO',
            'status' => extension_loaded('pdo') ? 'success' : 'danger',
            'value' => extension_loaded('pdo') ? 'dostępne' : 'brak',
        ];

        $checks[] = [
            'label' => 'Rozszerzenie pdo_mysql',
            'status' => extension_loaded('pdo_mysql') ? 'success' : 'danger',
            'value' => extension_loaded('pdo_mysql') ? 'dostępne' : 'brak',
        ];

        if (!self::isConfigured()) {
            $checks[] = [
                'label' => 'Połączenie z MySQL',
                'status' => 'warning',
                'value' => 'pominięte — najpierw ustaw dane w pliku .env',
            ];

            return $checks;
        }

        if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
            $checks[] = [
                'label' => 'Połączenie z MySQL',
                'status' => 'danger',
                'value' => 'niemożliwe — brakuje rozszerzenia PDO MySQL',
            ];

            return $checks;
        }

        try {
            $connection = self::connection();
            $version = $connection->query('SELECT VERSION()')->fetchColumn();

            $checks[] = [
                'label' => 'Połączenie z MySQL',
                'status' => 'success',
                'value' => 'połączono poprawnie',
            ];

            $checks[] = [
                'label' => 'Wersja MySQL',
                'status' => 'neutral',
                'value' => is_string($version) ? $version : 'nieznana',
            ];
        } catch (Throwable $exception) {
            $checks[] = [
                'label' => 'Połączenie z MySQL',
                'status' => 'danger',
                'value' => $exception->getMessage(),
            ];
        }

        return $checks;
    }
}