<?php

declare(strict_types=1);

final class IcalEventRepository
{
    private static bool $tableEnsured = false;

    public static function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }

        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS ical_events (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                cabin_id INT UNSIGNED NOT NULL,
                matched_reservation_id INT UNSIGNED NULL,
                ical_uid VARCHAR(191) NOT NULL,
                source VARCHAR(40) NOT NULL DEFAULT "BOOKING",
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                summary VARCHAR(255) NULL,
                description TEXT NULL,
                event_status VARCHAR(40) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_seen_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY ical_events_cabin_uid_unique (
                    cabin_id,
                    ical_uid
                ),
                INDEX ical_events_cabin_index (
                    cabin_id
                ),
                INDEX ical_events_reservation_index (
                    matched_reservation_id
                ),
                INDEX ical_events_source_index (
                    source
                ),
                INDEX ical_events_start_date_index (
                    start_date
                ),
                INDEX ical_events_end_date_index (
                    end_date
                ),
                CONSTRAINT ical_events_cabin_foreign
                    FOREIGN KEY (cabin_id)
                    REFERENCES cabins(id)
                    ON DELETE CASCADE,
                CONSTRAINT ical_events_reservation_foreign
                    FOREIGN KEY (matched_reservation_id)
                    REFERENCES reservations(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );

        self::$tableEnsured = true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByUid(
        int $cabinId,
        string $uid
    ): ?array {
        self::ensureTable();

        $uid = trim(
            $uid
        );

        if (
            $cabinId < 1
            || $uid === ''
        ) {
            return null;
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                cabin_id,
                matched_reservation_id,
                ical_uid,
                source,
                start_date,
                end_date,
                summary,
                description,
                event_status,
                is_active,
                last_seen_at,
                created_at,
                updated_at
            FROM ical_events
            WHERE cabin_id = :cabin_id
            AND ical_uid = :ical_uid
            LIMIT 1'
        );

        $statement->execute([
            'cabin_id' => $cabinId,
            'ical_uid' => $uid,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findExactReservation(
        int $cabinId,
        string $startDate,
        string $endDate
    ): ?array {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                external_id,
                cabin_id,
                guest_name,
                start_date,
                end_date,
                status,
                source
            FROM reservations
            WHERE cabin_id = :cabin_id
            AND start_date = :start_date
            AND end_date = :end_date
            AND status <> "CANCELLED"
            ORDER BY id ASC
            LIMIT 1'
        );

        $statement->execute([
            'cabin_id' => $cabinId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findBlockingConflict(
        int $cabinId,
        string $startDate,
        string $endDate
    ): ?array {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                external_id,
                cabin_id,
                guest_name,
                start_date,
                end_date,
                status,
                source
            FROM reservations
            WHERE cabin_id = :cabin_id
            AND status IN (
                "PENDING",
                "CONFIRMED",
                "CHECKED_IN"
            )
            AND start_date < :end_date
            AND end_date > :start_date
            ORDER BY start_date ASC, id ASC
            LIMIT 1'
        );

        $statement->execute([
            'cabin_id' => $cabinId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    /**
     * Sprawdza, jak należy potraktować wydarzenie iCal.
     *
     * Zwracane typy:
     *
     * EXISTING_ICAL
     * - wydarzenie o tym UID jest już znane.
     *
     * MATCH_RESERVATION
     * - istnieje rezerwacja tego samego domku
     *   z dokładnie takim samym terminem.
     *
     * CONFLICT
     * - istnieje inna rezerwacja nachodząca
     *   na termin wydarzenia.
     *
     * NEW_BLOCK
     * - wydarzenie nie jest jeszcze znane
     *   i nie odpowiada istniejącej rezerwacji.
     *
     * @param array{
     *     uid: string,
     *     start_date: string,
     *     end_date: string
     * } $event
     *
     * @return array{
     *     action: string,
     *     existing_ical_event: array<string, mixed>|null,
     *     matched_reservation: array<string, mixed>|null,
     *     conflicting_reservation: array<string, mixed>|null
     * }
     */
    public static function classifyEvent(
        int $cabinId,
        array $event
    ): array {
        $uid = trim(
            (string) (
                $event['uid']
                ?? ''
            )
        );

        $startDate = trim(
            (string) (
                $event['start_date']
                ?? ''
            )
        );

        $endDate = trim(
            (string) (
                $event['end_date']
                ?? ''
            )
        );

        self::validateEventData(
            $cabinId,
            $uid,
            $startDate,
            $endDate
        );

        $existingIcalEvent = self::findByUid(
            $cabinId,
            $uid
        );

        if ($existingIcalEvent !== null) {
            return [
                'action' => 'EXISTING_ICAL',
                'existing_ical_event' =>
                    $existingIcalEvent,
                'matched_reservation' =>
                    null,
                'conflicting_reservation' =>
                    null,
            ];
        }

        $matchedReservation =
            self::findExactReservation(
                $cabinId,
                $startDate,
                $endDate
            );

        if ($matchedReservation !== null) {
            return [
                'action' => 'MATCH_RESERVATION',
                'existing_ical_event' =>
                    null,
                'matched_reservation' =>
                    $matchedReservation,
                'conflicting_reservation' =>
                    null,
            ];
        }

        $conflictingReservation =
            self::findBlockingConflict(
                $cabinId,
                $startDate,
                $endDate
            );

        if ($conflictingReservation !== null) {
            return [
                'action' => 'CONFLICT',
                'existing_ical_event' =>
                    null,
                'matched_reservation' =>
                    null,
                'conflicting_reservation' =>
                    $conflictingReservation,
            ];
        }

        return [
            'action' => 'NEW_BLOCK',
            'existing_ical_event' =>
                null,
            'matched_reservation' =>
                null,
            'conflicting_reservation' =>
                null,
        ];
    }

    private static function validateEventData(
        int $cabinId,
        string $uid,
        string $startDate,
        string $endDate
    ): void {
        if ($cabinId < 1) {
            throw new InvalidArgumentException(
                'Nieprawidłowy identyfikator domku.'
            );
        }

        if ($uid === '') {
            throw new InvalidArgumentException(
                'Wydarzenie iCal nie ma identyfikatora UID.'
            );
        }

        if (
            !self::isValidDate(
                $startDate
            )
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowa data rozpoczęcia wydarzenia iCal.'
            );
        }

        if (
            !self::isValidDate(
                $endDate
            )
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowa data zakończenia wydarzenia iCal.'
            );
        }

        if ($endDate <= $startDate) {
            throw new InvalidArgumentException(
                'Data zakończenia wydarzenia iCal musi być późniejsza od daty rozpoczęcia.'
            );
        }
    }

    private static function isValidDate(
        string $date
    ): bool {
        $parsed = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        if ($parsed === false) {
            return false;
        }

        $errors = DateTimeImmutable::getLastErrors();

        if (
            is_array($errors)
            && (
                ($errors['warning_count'] ?? 0) > 0
                || ($errors['error_count'] ?? 0) > 0
            )
        ) {
            return false;
        }

        return $parsed->format(
            'Y-m-d'
        ) === $date;
    }
}