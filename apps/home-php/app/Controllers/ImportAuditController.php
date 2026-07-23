<?php

declare(strict_types=1);

final class ImportAuditController
{
    public static function show(): void
    {
        if (
            !Database::canAttemptConnection()
        ) {
            self::render(
                'Baza danych nie jest jeszcze skonfigurowana.',
                []
            );

            return;
        }

        try {
            $pdo = Database::connection();

            self::render(
                null,
                [
                    'summary' =>
                        self::summary(
                            $pdo
                        ),
                    'reservationStatuses' =>
                        self::groupCount(
                            $pdo,
                            'status'
                        ),
                    'paymentStatuses' =>
                        self::groupCount(
                            $pdo,
                            'payment_status'
                        ),
                    'reservationSources' =>
                        self::groupCount(
                            $pdo,
                            'source'
                        ),
                    'reservationsWithoutGuest' =>
                        self::reservationsWithoutGuest(
                            $pdo
                        ),
                    'duplicateGuestEmails' =>
                        self::duplicateGuestEmails(
                            $pdo
                        ),
                    'duplicateCabinShortNames' =>
                        self::duplicateCabinShortNames(
                            $pdo
                        ),
                ]
            );
        } catch (Throwable $exception) {
            self::render(
                'Nie udało się wykonać kontroli danych: '
                . AppErrorHandler::safeMessage(
                    $exception
                ),
                []
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function render(
        ?string $databaseMessage,
        array $data
    ): void {
        Response::html(
            View::render(
                'pages/admin_import_audit',
                [
                    'title' =>
                        'Kontrola danych i importów',
                    'databaseMessage' =>
                        $databaseMessage,
                    'summary' =>
                        $data['summary']
                        ?? [],
                    'reservationStatuses' =>
                        $data[
                            'reservationStatuses'
                        ]
                        ?? [],
                    'paymentStatuses' =>
                        $data[
                            'paymentStatuses'
                        ]
                        ?? [],
                    'reservationSources' =>
                        $data[
                            'reservationSources'
                        ]
                        ?? [],
                    'reservationsWithoutGuest' =>
                        $data[
                            'reservationsWithoutGuest'
                        ]
                        ?? [],
                    'duplicateGuestEmails' =>
                        $data[
                            'duplicateGuestEmails'
                        ]
                        ?? [],
                    'duplicateCabinShortNames' =>
                        $data[
                            'duplicateCabinShortNames'
                        ]
                        ?? [],
                ]
            )
        );
    }

    /**
     * @return array<string, int>
     */
    private static function summary(
        PDO $pdo
    ): array {
        return [
            'guests_count' =>
                self::singleCount(
                    $pdo,
                    'SELECT COUNT(*)
                    FROM guests'
                ),
            'cabins_count' =>
                self::singleCount(
                    $pdo,
                    'SELECT COUNT(*)
                    FROM cabins'
                ),
            'reservations_count' =>
                self::singleCount(
                    $pdo,
                    'SELECT COUNT(*)
                    FROM reservations'
                ),
            'reservations_without_guest' =>
                self::singleCount(
                    $pdo,
                    'SELECT COUNT(*)
                    FROM reservations
                    WHERE guest_id IS NULL'
                ),
            'guests_incomplete_address' =>
                self::singleCount(
                    $pdo,
                    'SELECT COUNT(*)
                    FROM guests
                    WHERE street IS NULL
                    OR TRIM(street) = \'\'
                    OR postal_code IS NULL
                    OR TRIM(postal_code) = \'\'
                    OR city IS NULL
                    OR TRIM(city) = \'\''
                ),
            'legacy_placeholder_emails' =>
                self::singleCount(
                    $pdo,
                    'SELECT COUNT(*)
                    FROM guests
                    WHERE LOWER(email)
                        LIKE \'%@base44.local\''
                ),
            'invalid_reservation_sources' =>
                self::singleCount(
                    $pdo,
                    'SELECT COUNT(*)
                    FROM reservations
                    WHERE source NOT IN (
                        \'MANUAL\',
                        \'DIRECT\',
                        \'WWW\',
                        \'BOOKING\',
                        \'PHONE\',
                        \'AIRBNB\',
                        \'ICAL_OTHER\'
                    )'
                ),
            'invalid_guest_sources' =>
                self::singleCount(
                    $pdo,
                    'SELECT COUNT(*)
                    FROM guests
                    WHERE source NOT IN (
                        \'MANUAL\',
                        \'DIRECT\',
                        \'WWW\',
                        \'BOOKING\',
                        \'PHONE\',
                        \'AIRBNB\',
                        \'ICAL_OTHER\'
                    )'
                ),
        ];
    }

    private static function singleCount(
        PDO $pdo,
        string $sql
    ): int {
        $statement =
            $pdo->query(
                $sql
            );

        if ($statement === false) {
            return 0;
        }

        return (int) $statement
            ->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function groupCount(
        PDO $pdo,
        string $column
    ): array {
        if (
            !in_array(
                $column,
                [
                    'status',
                    'payment_status',
                    'source',
                ],
                true
            )
        ) {
            return [];
        }

        $statement = $pdo->query(
            'SELECT '
            . $column
            . ' AS item_value,
                COUNT(*) AS item_count
            FROM reservations
            GROUP BY '
            . $column
            . '
            ORDER BY '
            . $column
            . ' ASC'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array(
            $rows
        )
            ? $rows
            : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function reservationsWithoutGuest(
        PDO $pdo
    ): array {
        $statement = $pdo->query(
            'SELECT
                reservations.id,
                reservations.guest_name,
                reservations.email,
                reservations.start_date,
                reservations.end_date,
                cabins.name AS cabin_name
            FROM reservations
            LEFT JOIN cabins
                ON cabins.id
                    = reservations.cabin_id
            WHERE reservations.guest_id IS NULL
            ORDER BY
                reservations.start_date ASC,
                reservations.id ASC
            LIMIT 20'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array(
            $rows
        )
            ? $rows
            : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function duplicateGuestEmails(
        PDO $pdo
    ): array {
        $statement = $pdo->query(
            'SELECT
                LOWER(email) AS email,
                COUNT(*) AS item_count
            FROM guests
            GROUP BY LOWER(email)
            HAVING COUNT(*) > 1
            ORDER BY item_count DESC,
                email ASC
            LIMIT 20'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array(
            $rows
        )
            ? $rows
            : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function duplicateCabinShortNames(
        PDO $pdo
    ): array {
        $statement = $pdo->query(
            'SELECT
                UPPER(short_name)
                    AS short_name,
                COUNT(*) AS item_count
            FROM cabins
            WHERE short_name IS NOT NULL
            AND TRIM(short_name) <> \'\'
            GROUP BY UPPER(short_name)
            HAVING COUNT(*) > 1
            ORDER BY item_count DESC,
                short_name ASC
            LIMIT 20'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array(
            $rows
        )
            ? $rows
            : [];
    }


}
