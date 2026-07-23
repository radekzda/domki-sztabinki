<?php

declare(strict_types=1);

$basePath =
    dirname(
        __DIR__
    );

require
    $basePath
    . '/app/Core/Env.php';

Env::load(
    $basePath
    . '/.env'
);

$config = require
    $basePath
    . '/app/Config/config.php';

date_default_timezone_set(
    (string) (
        $config['timezone']
        ?? 'Europe/Warsaw'
    )
);

require
    $basePath
    . '/app/Core/Database.php';

require
    $basePath
    . '/app/Core/Mailer.php';

require
    $basePath
    . '/app/Repositories/SettingsRepository.php';

require
    $basePath
    . '/app/Services/InvoiceReminderService.php';

try {
    if (
        !Database::canAttemptConnection()
    ) {
        throw new RuntimeException(
            'Brak konfiguracji połączenia '
            . 'z bazą danych.'
        );
    }

    $result =
        InvoiceReminderService::sendTestNotification();

    fwrite(
        STDOUT,
        sprintf(
            "OK: wysłano testowe powiadomienie "
            . "o fakturze na %s.%s",
            (string) $result[
                'recipient'
            ],
            PHP_EOL
        )
    );
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'ERROR: '
        . $exception->getMessage()
        . PHP_EOL
    );

    error_log(
        'Invoice reminder test error: '
        . $exception->getMessage()
    );

    exit(1);
}
