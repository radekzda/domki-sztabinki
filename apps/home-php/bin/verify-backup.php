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

require $basePath . '/app/Services/BackupService.php';

$options = getopt(
    '',
    [
        'path::',
    ]
);

$path = isset($options['path'])
    && is_string($options['path'])
    ? trim($options['path'])
    : '';

if ($path === '') {
    $path = BackupService::latestBackupPath()
        ?? '';
}

if ($path === '') {
    fwrite(
        STDERR,
        'ERROR: Nie znaleziono żadnego backupu.'
        . PHP_EOL
    );

    exit(1);
}

$result = BackupService::verify(
    $path
);

echo 'Backup: '
    . $path
    . PHP_EOL;

echo 'Sprawdzone pliki: '
    . $result['files_checked']
    . PHP_EOL;

if (!$result['valid']) {
    foreach (
        $result['errors']
        as $error
    ) {
        fwrite(
            STDERR,
            'ERROR: '
            . $error
            . PHP_EOL
        );
    }

    exit(1);
}

echo 'OK: backup jest kompletny i sumy SHA-256 są poprawne.'
    . PHP_EOL;
