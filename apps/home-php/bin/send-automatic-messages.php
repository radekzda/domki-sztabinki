<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);

require $basePath . '/app/Core/Env.php';

Env::load($basePath . '/.env');

$config = require $basePath
    . '/app/Config/config.php';

date_default_timezone_set(
    (string) (
        $config['timezone']
        ?? 'Europe/Warsaw'
    )
);

$lockDirectory = $basePath
    . '/storage/logs';

if (
    !is_dir($lockDirectory)
    && !mkdir($lockDirectory, 0775, true)
    && !is_dir($lockDirectory)
) {
    fwrite(
        STDERR,
        'BŁĄD: nie udało się utworzyć katalogu blokady.'
        . PHP_EOL
    );

    exit(1);
}

$lockHandle = fopen(
    $lockDirectory . '/automatic-messages.lock',
    'c'
);

if ($lockHandle === false) {
    fwrite(
        STDERR,
        'BŁĄD: nie udało się otworzyć blokady procesu.'
        . PHP_EOL
    );

    exit(1);
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fclose($lockHandle);

    fwrite(
        STDOUT,
        'OK: automatyczne wiadomości są już przetwarzane.'
        . PHP_EOL
    );

    exit(0);
}

require $basePath . '/app/Core/Database.php';
require $basePath . '/app/Core/Mailer.php';
require $basePath . '/app/Repositories/InvoiceSellerRepository.php';
require $basePath . '/app/Repositories/ReservationRepository.php';
require $basePath . '/app/Repositories/ReservationHistoryRepository.php';
require $basePath . '/app/Repositories/SettingsRepository.php';
require $basePath . '/app/Repositories/MessageTemplateRepository.php';
require $basePath . '/app/Repositories/AutomaticMessageDeliveryRepository.php';
require $basePath . '/app/Services/MessageTemplateRenderer.php';
require $basePath . '/app/Services/MessageAutomationService.php';

$dryRun = in_array('--dry-run', $argv, true);
$now = new DateTimeImmutable('now');

foreach ($argv as $argument) {
    if (!str_starts_with($argument, '--now=')) {
        continue;
    }

    $value = trim(substr($argument, 6));

    try {
        $now = new DateTimeImmutable($value);
    } catch (Throwable $exception) {
        fwrite(
            STDERR,
            'BŁĄD: nieprawidłowa wartość --now.'
            . PHP_EOL
        );

        exit(1);
    }
}

try {
    if (!Database::canAttemptConnection()) {
        throw new RuntimeException(
            'Brak konfiguracji połączenia z bazą danych.'
        );
    }

    $result = MessageAutomationService::process(
        $now,
        $dryRun
    );

    fwrite(
        STDOUT,
        sprintf(
            "%s: szablony %d, znalezione rezerwacje %d, do wysłania %d, wysłane %d, pominięte %d, błędy %d.%s",
            $dryRun ? 'DRY-RUN' : 'OK',
            $result['templates'],
            $result['found'],
            $result['due'],
            $result['sent'],
            $result['skipped'],
            $result['failed'],
            PHP_EOL
        )
    );

    exit($result['failed'] > 0 ? 1 : 0);
} catch (Throwable $exception) {
    error_log(
        'Automatic messages cron error: '
        . $exception::class
        . ': '
        . $exception->getMessage()
    );

    fwrite(
        STDERR,
        'BŁĄD: '
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}
