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

    public static function hasRequiredExtensions(): bool
    {
        return extension_loaded('pdo') && extension_loaded('pdo_mysql');
    }

    public static function canAttemptConnection(): bool
    {
        return self::isConfigured() && self::hasRequiredExtensions();
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

        if (!self::hasRequiredExtensions()) {
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

    public static function installSchema(string $schemaPath): int
    {
        return self::runSqlFile($schemaPath, 'schema.sql');
    }

    public static function seedDefaultData(string $seedPath): int
    {
        return self::runSqlFile($seedPath, 'seed.sql');
    }

    private static function runSqlFile(string $path, string $label): int
    {
        if (!self::canAttemptConnection()) {
            throw new RuntimeException('Nie można uruchomić pliku SQL — brakuje konfiguracji lub rozszerzenia pdo_mysql.');
        }

        if (!is_file($path)) {
            throw new RuntimeException('Nie znaleziono pliku ' . $label . '.');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('Plik ' . $label . ' jest pusty albo nie można go odczytać.');
        }

        $connection = self::connection();
        $statements = self::splitSqlStatements($sql);
        $executedStatements = 0;

        foreach ($statements as $statement) {
            $trimmedStatement = trim($statement);

            if ($trimmedStatement === '') {
                continue;
            }

            $connection->exec($trimmedStatement);
            $executedStatements++;
        }

        return $executedStatements;
    }

    /**
     * @return array<int, string>
     */
    private static function splitSqlStatements(string $schema): array
    {
        $schema = str_replace("\r\n", "\n", $schema);
        $schema = str_replace("\r", "\n", $schema);

        $statements = explode(';', $schema);

        return array_values(array_filter(array_map('trim', $statements), static function (string $statement): bool {
            return $statement !== '';
        }));
    }
}