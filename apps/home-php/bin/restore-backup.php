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

$options = getopt(
    '',
    [
        'path:',
        'confirm:',
    ]
);

$path = isset($options['path'])
    && is_string($options['path'])
    ? trim($options['path'])
    : '';

$confirm = isset($options['confirm'])
    && is_string($options['confirm'])
    ? trim($options['confirm'])
    : '';

if (
    $path === ''
    || $confirm !== 'RESTORE'
) {
    fwrite(
        STDERR,
        'ERROR: Przywracanie wymaga parametrów: '
        . '--path="ścieżka_do_backupu" '
        . '--confirm=RESTORE'
        . PHP_EOL
    );

    exit(1);
}

try {
    $result = BackupService::restore(
        $path
    );

    echo 'OK: backup został przywrócony.'
        . PHP_EOL;

    echo 'Wykonane polecenia SQL: '
        . $result['statements']
        . PHP_EOL;

    echo 'Backup bezpieczeństwa stanu sprzed przywrócenia: '
        . $result['safety_backup_path']
        . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'ERROR: '
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}
