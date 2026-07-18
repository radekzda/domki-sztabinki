<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

require $projectRoot . '/app/Core/Env.php';

Env::load(
    $projectRoot . '/.env'
);

$config = require $projectRoot
    . '/app/Config/config.php';

date_default_timezone_set(
    $config['timezone']
    ?? 'Europe/Warsaw'
);

require $projectRoot
    . '/app/Core/Database.php';

require $projectRoot
    . '/app/Repositories/CabinRepository.php';

require $projectRoot
    . '/app/Repositories/IcalEventRepository.php';

require $projectRoot
    . '/app/Services/IcalParser.php';

require $projectRoot
    . '/app/Services/IcalCalendarClient.php';

require $projectRoot
    . '/app/Services/IcalSyncService.php';

$lockPath = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'domki-sztabinki-ical-sync.lock';

$lockHandle = fopen(
    $lockPath,
    'c'
);

if ($lockHandle === false) {
    fwrite(
        STDERR,
        "BLAD: nie mozna utworzyc blokady synchronizacji.\n"
    );

    exit(1);
}

if (
    !flock(
        $lockHandle,
        LOCK_EX | LOCK_NB
    )
) {
    echo
        "INFO: synchronizacja iCal jest juz uruchomiona.\n";

    fclose(
        $lockHandle
    );

    exit(0);
}

$startedAt = new DateTimeImmutable();

echo
    'START iCal: '
    . $startedAt->format(
        'Y-m-d H:i:s'
    )
    . PHP_EOL;

$totalCabins = 0;
$successfulCabins = 0;
$failedCabins = 0;

$totalEvents = 0;
$totalMatched = 0;
$totalNewBlocks = 0;
$totalConflicts = 0;
$totalExisting = 0;
$totalDeactivated = 0;

try {
    $cabins =
        CabinRepository::allIcalEnabled();

    $totalCabins = count(
        $cabins
    );

    if ($cabins === []) {
        echo
            "INFO: brak domkow z aktywna synchronizacja iCal.\n";
    }

    foreach ($cabins as $cabin) {
        $cabinId = (int) (
            $cabin['id']
            ?? 0
        );

        $cabinName = (string) (
            $cabin['name']
            ?? ''
        );

        echo PHP_EOL;

        echo
            'Domek #'
            . $cabinId
            . ' '
            . $cabinName
            . PHP_EOL;

        try {
            $result =
                IcalSyncService::syncCabin(
                    $cabin
                );

            $successfulCabins++;

            $totalEvents += (int) (
                $result['total']
                ?? 0
            );

            $totalMatched += (int) (
                $result['matched_reservations']
                ?? 0
            );

            $totalNewBlocks += (int) (
                $result['new_blocks']
                ?? 0
            );

            $totalConflicts += (int) (
                $result['conflicts']
                ?? 0
            );

            $totalExisting += (int) (
                $result['existing_ical']
                ?? 0
            );

            $totalDeactivated += (int) (
                $result['deactivated']
                ?? 0
            );

            echo
                '  OK'
                . ' | wydarzenia: '
                . (int) (
                    $result['total']
                    ?? 0
                )
                . ' | powiazane: '
                . (int) (
                    $result[
                        'matched_reservations'
                    ]
                    ?? 0
                )
                . ' | nowe blokady: '
                . (int) (
                    $result['new_blocks']
                    ?? 0
                )
                . ' | konflikty: '
                . (int) (
                    $result['conflicts']
                    ?? 0
                )
                . ' | znane: '
                . (int) (
                    $result['existing_ical']
                    ?? 0
                )
                . ' | dezaktywowane: '
                . (int) (
                    $result['deactivated']
                    ?? 0
                )
                . PHP_EOL;
        } catch (Throwable $exception) {
            $failedCabins++;

            fwrite(
                STDERR,
                '  BLAD: '
                . $exception->getMessage()
                . PHP_EOL
            );
        }
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        PHP_EOL
        . 'BLAD KRYTYCZNY: '
        . $exception->getMessage()
        . PHP_EOL
    );

    flock(
        $lockHandle,
        LOCK_UN
    );

    fclose(
        $lockHandle
    );

    exit(1);
}

$finishedAt = new DateTimeImmutable();

echo PHP_EOL;

echo
    'KONIEC iCal: '
    . $finishedAt->format(
        'Y-m-d H:i:s'
    )
    . PHP_EOL;

echo
    'Domki: '
    . $totalCabins
    . ' | OK: '
    . $successfulCabins
    . ' | bledy: '
    . $failedCabins
    . PHP_EOL;

echo
    'Wydarzenia: '
    . $totalEvents
    . ' | powiazane: '
    . $totalMatched
    . ' | nowe blokady: '
    . $totalNewBlocks
    . ' | konflikty: '
    . $totalConflicts
    . ' | znane: '
    . $totalExisting
    . ' | dezaktywowane: '
    . $totalDeactivated
    . PHP_EOL;

flock(
    $lockHandle,
    LOCK_UN
);

fclose(
    $lockHandle
);

exit(
    $failedCabins > 0
        ? 1
        : 0
);
