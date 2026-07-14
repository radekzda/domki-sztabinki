<?php

declare(strict_types=1);

final class ImportAuditController
{
    public static function show(): void
    {
        if (!Database::canAttemptConnection()) {
            Response::html(View::render('pages/admin_import_audit', [
                'title' => 'Kontrola importu',
                'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana.',
                'summary' => [],
                'reservationStatuses' => [],
                'paymentStatuses' => [],
                'reservationSources' => [],
                'reservationsWithoutGuest' => [],
                'reservationsWithoutExternalId' => [],
                'guestsWithoutExternalId' => [],
                'cabinsWithoutExternalId' => [],
            ]));

            return;
        }

        try {
            $pdo = Database::connection();

            Response::html(View::render('pages/admin_import_audit', [
                'title' => 'Kontrola importu',
                'databaseMessage' => null,
                'summary' => self::summary($pdo),
                'reservationStatuses' => self::groupCount($pdo, 'reservations', 'status'),
                'paymentStatuses' => self::groupCount($pdo, 'reservations', 'payment_status'),
                'reservationSources' => self::groupCount($pdo, 'reservations', 'source'),
                'reservationsWithoutGuest' => self::reservationsWithoutGuest($pdo),
                'reservationsWithoutExternalId' => self::reservationsWithoutExternalId($pdo),
                'guestsWithoutExternalId' => self::guestsWithoutExternalId($pdo),
                'cabinsWithoutExternalId' => self::cabinsWithoutExternalId($pdo),
            ]));
        } catch (Throwable $exception) {
            Response::html(View::render('pages/admin_import_audit', [
                'title' => 'Kontrola importu',
                'databaseMessage' => 'Nie udało się wykonać kontroli importu: ' . $exception->getMessage(),
                'summary' => [],
                'reservationStatuses' => [],
                'paymentStatuses' => [],
                'reservationSources' => [],
                'reservationsWithoutGuest' => [],
                'reservationsWithoutExternalId' => [],
                'guestsWithoutExternalId' => [],
                'cabinsWithoutExternalId' => [],
            ]));
        }
    }

    private static function summary(PDO $pdo): array
    {
        return [
            'guests_count' => self::singleCount($pdo, 'SELECT COUNT(*) FROM guests'),
            'cabins_count' => self::singleCount($pdo, 'SELECT COUNT(*) FROM cabins'),
            'reservations_count' => self::singleCount($pdo, 'SELECT COUNT(*) FROM reservations'),
            'guests_base44_count' => self::singleCount($pdo, "SELECT COUNT(*) FROM guests WHERE source = 'BASE44'"),
            'cabins_with_external_id' => self::singleCount($pdo, 'SELECT COUNT(*) FROM cabins WHERE external_id IS NOT NULL AND external_id <> ""'),
            'reservations_with_external_id' => self::singleCount($pdo, 'SELECT COUNT(*) FROM reservations WHERE external_id IS NOT NULL AND external_id <> ""'),
            'reservations_without_guest' => self::singleCount($pdo, 'SELECT COUNT(*) FROM reservations WHERE guest_id IS NULL'),
            'reservations_without_external_id' => self::singleCount($pdo, 'SELECT COUNT(*) FROM reservations WHERE external_id IS NULL OR external_id = ""'),
            'guests_without_external_id' => self::singleCount($pdo, 'SELECT COUNT(*) FROM guests WHERE external_id IS NULL OR external_id = ""'),
            'cabins_without_external_id' => self::singleCount($pdo, 'SELECT COUNT(*) FROM cabins WHERE external_id IS NULL OR external_id = ""'),
        ];
    }

    private static function singleCount(PDO $pdo, string $sql): int
    {
        $statement = $pdo->query($sql);

        if ($statement === false) {
            return 0;
        }

        return (int) $statement->fetchColumn();
    }

    private static function groupCount(PDO $pdo, string $table, string $column): array
    {
        $statement = $pdo->query(
            'SELECT ' . $column . ' AS item_value, COUNT(*) AS item_count
            FROM ' . $table . '
            GROUP BY ' . $column . '
            ORDER BY ' . $column . ' ASC'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    private static function reservationsWithoutGuest(PDO $pdo): array
    {
        $statement = $pdo->query(
            'SELECT
                reservations.id,
                reservations.external_id,
                reservations.guest_name,
                reservations.email,
                reservations.start_date,
                reservations.end_date,
                cabins.name AS cabin_name
            FROM reservations
            LEFT JOIN cabins ON cabins.id = reservations.cabin_id
            WHERE reservations.guest_id IS NULL
            ORDER BY reservations.start_date ASC, reservations.id ASC
            LIMIT 20'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    private static function reservationsWithoutExternalId(PDO $pdo): array
    {
        $statement = $pdo->query(
            'SELECT
                reservations.id,
                reservations.guest_name,
                reservations.start_date,
                reservations.end_date,
                reservations.source,
                cabins.name AS cabin_name
            FROM reservations
            LEFT JOIN cabins ON cabins.id = reservations.cabin_id
            WHERE reservations.external_id IS NULL OR reservations.external_id = ""
            ORDER BY reservations.start_date ASC, reservations.id ASC
            LIMIT 20'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    private static function guestsWithoutExternalId(PDO $pdo): array
    {
        $statement = $pdo->query(
            'SELECT
                id,
                first_name,
                last_name,
                email,
                phone,
                source
            FROM guests
            WHERE external_id IS NULL OR external_id = ""
            ORDER BY source ASC, last_name ASC, first_name ASC
            LIMIT 20'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    private static function cabinsWithoutExternalId(PDO $pdo): array
    {
        $statement = $pdo->query(
            'SELECT
                id,
                name,
                short_name,
                sort_order,
                is_active
            FROM cabins
            WHERE external_id IS NULL OR external_id = ""
            ORDER BY sort_order ASC, id ASC
            LIMIT 20'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array($rows) ? $rows : [];
    }
}