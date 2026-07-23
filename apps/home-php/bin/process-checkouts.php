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
    . '/app/Repositories/CabinRepository.php';

require
    $basePath
    . '/app/Repositories/ReservationHistoryRepository.php';

require
    $basePath
    . '/app/Repositories/ReservationRepository.php';

require
    $basePath
    . '/app/Repositories/InvoiceSellerRepository.php';

require
    $basePath
    . '/app/Repositories/InvoiceRepository.php';

require
    $basePath
    . '/app/Repositories/SettingsRepository.php';

require
    $basePath
    . '/app/Services/InvoiceReminderService.php';

require
    $basePath
    . '/app/Services/CheckoutAutomationService.php';

try {
    if (
        !Database::canAttemptConnection()
    ) {
        throw new RuntimeException(
            'Brak konfiguracji połączenia '
            . 'z bazą danych.'
        );
    }

    $now =
        new DateTimeImmutable(
            'now'
        );

    $result =
        CheckoutAutomationService::process(
            $now
        );

    fwrite(
        STDOUT,
        sprintf(
            "OK: znaleziono %d rezerwacji do automatycznego wymeldowania, wymeldowano %d.%s",
            $result['found'],
            $result['checked_out'],
            PHP_EOL
        )
    );

    if (
        $result[
            'reminder_sent'
        ] > 0
    ) {
        fwrite(
            STDOUT,
            sprintf(
                "OK: wysłano przypomnienie o %d fakturach na %s.%s",
                $result[
                    'reminder_sent'
                ],
                (string) $result[
                    'reminder_recipient'
                ],
                PHP_EOL
            )
        );
    } elseif (
        $result[
            'reminder_found'
        ] > 0
    ) {
        fwrite(
            STDOUT,
            "OK: dzisiejsze rezerwacje bez faktury "
            . "zostały już wcześniej zgłoszone."
            . PHP_EOL
        );
    } else {
        fwrite(
            STDOUT,
            "OK: brak dzisiejszych wymeldowanych "
            . "rezerwacji bez faktury."
            . PHP_EOL
        );
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'ERROR: '
        . $exception->getMessage()
        . PHP_EOL
    );

    error_log(
        'Checkout automation error: '
        . $exception->getMessage()
    );

    exit(1);
}
