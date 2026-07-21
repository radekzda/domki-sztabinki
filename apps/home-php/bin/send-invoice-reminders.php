<?php

declare(strict_types=1);

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

$lockDirectory = $basePath
    . '/storage/logs';

if (
    !is_dir($lockDirectory)
    && !mkdir(
        $lockDirectory,
        0775,
        true
    )
    && !is_dir($lockDirectory)
) {
    fwrite(
        STDERR,
        'BŁĄD: nie udało się utworzyć katalogu blokady procesu.'
        . PHP_EOL
    );

    exit(1);
}

$lockPath = $lockDirectory
    . '/invoice-reminders.lock';

$lockHandle = fopen(
    $lockPath,
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

if (
    !flock(
        $lockHandle,
        LOCK_EX | LOCK_NB
    )
) {
    fclose($lockHandle);

    fwrite(
        STDOUT,
        'OK: przypomnienia o fakturach są już przetwarzane.'
        . PHP_EOL
    );

    exit(0);
}

require $basePath . '/app/Core/Database.php';
require $basePath . '/app/Core/Mailer.php';
require $basePath . '/app/Repositories/InvoiceSellerRepository.php';
require $basePath . '/app/Repositories/InvoiceRepository.php';
require $basePath . '/app/Repositories/ReservationRepository.php';
require $basePath . '/app/Repositories/SettingsRepository.php';
require $basePath . '/app/Services/InvoiceReminderService.php';

try {
    if (!Database::canAttemptConnection()) {
        throw new RuntimeException(
            'Brak konfiguracji połączenia z bazą danych.'
        );
    }

    $today = (new DateTimeImmutable('today'))
        ->format('Y-m-d');

    $result = InvoiceReminderService::sendForDate(
        $today
    );

    if ($result['sent'] > 0) {
        fwrite(
            STDOUT,
            sprintf(
                "OK: wysłano przypomnienie o %d fakturach na %s.%s",
                $result['sent'],
                (string) $result['recipient'],
                PHP_EOL
            )
        );
    } elseif ($result['found'] > 0) {
        fwrite(
            STDOUT,
            'OK: wszystkie dzisiejsze przypomnienia '
            . 'zostały już wcześniej wysłane.'
            . PHP_EOL
        );
    } else {
        fwrite(
            STDOUT,
            'OK: dziś nie ma wymeldowań bez faktury.'
            . PHP_EOL
        );
    }

    exit(0);
} catch (Throwable $exception) {
    error_log(
        'Invoice reminder cron error: '
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