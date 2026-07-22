<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$basePath = dirname(__DIR__);

require $basePath . '/app/Core/Env.php';

Env::load(
    $basePath . '/.env'
);

$config = require $basePath
    . '/app/Config/config.php';

date_default_timezone_set(
    (string) (
        $config['timezone']
        ?? 'Europe/Warsaw'
    )
);

require $basePath . '/app/Core/Database.php';
require $basePath . '/app/Services/BackupService.php';

try {
    $result = BackupService::create();

    echo 'OK: backup utworzony.'
        . PHP_EOL;

    echo 'Ścieżka: '
        . $result['path']
        . PHP_EOL;

    echo 'Tabele: '
        . $result['tables']
        . PHP_EOL;

    echo 'Wiersze: '
        . $result['rows']
        . PHP_EOL;

    echo 'Pliki: '
        . $result['files_count']
        . PHP_EOL;

    echo 'Rozmiar plików: '
        . $result['files_total_bytes']
        . ' B'
        . PHP_EOL;

    if (
        is_string(
            $result['external_copy_path']
        )
        && $result['external_copy_path'] !== ''
    ) {
        echo 'Kopia zewnętrzna: '
            . $result['external_copy_path']
            . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'ERROR: '
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}
