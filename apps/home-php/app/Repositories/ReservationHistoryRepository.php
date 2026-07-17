<?php

declare(strict_types=1);

final class ReservationHistoryRepository
{
    /**
     * @return array<int, array{
     *     id: int,
     *     reservation_id: int,
     *     event_type: string,
     *     title: string,
     *     details: string|null,
     *     old_value: string|null,
     *     new_value: string|null,
     *     amount: string|null,
     *     created_at: string|null
     * }>
     */
    public static function forReservation(
        int $reservationId
    ): array {
        self::ensureTable();

        if ($reservationId < 1) {
            return [];
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                reservation_id,
                event_type,
                title,
                details,
                old_value,
                new_value,
                amount,
                created_at
            FROM reservation_history
            WHERE reservation_id = :reservation_id
            ORDER BY
                created_at DESC,
                id DESC'
        );

        $statement->execute([
            'reservation_id' => $reservationId,
        ]);

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        $history = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $history[] = self::mapRow(
                $row
            );
        }

        return $history;
    }

    public static function add(
        int $reservationId,
        string $eventType,
        string $title,
        ?string $details = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?float $amount = null
    ): int {
        self::ensureTable();

        if ($reservationId < 1) {
            throw new InvalidArgumentException(
                'Nieprawidłowe ID rezerwacji.'
            );
        }

        $eventType = strtoupper(
            trim($eventType)
        );

        $title = trim(
            $title
        );

        if ($eventType === '') {
            throw new InvalidArgumentException(
                'Typ zdarzenia nie może być pusty.'
            );
        }

        if ($title === '') {
            throw new InvalidArgumentException(
                'Tytuł zdarzenia nie może być pusty.'
            );
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'INSERT INTO reservation_history (
                reservation_id,
                event_type,
                title,
                details,
                old_value,
                new_value,
                amount
            ) VALUES (
                :reservation_id,
                :event_type,
                :title,
                :details,
                :old_value,
                :new_value,
                :amount
            )'
        );

        $statement->execute([
            'reservation_id' => $reservationId,
            'event_type' => $eventType,
            'title' => $title,
            'details' => self::nullableText(
                $details
            ),
            'old_value' => self::nullableText(
                $oldValue
            ),
            'new_value' => self::nullableText(
                $newValue
            ),
            'amount' => $amount,
        ]);

        return (int) $connection->lastInsertId();
    }

    public static function ensureTable(): void
    {
        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS reservation_history (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                reservation_id INT UNSIGNED NOT NULL,
                event_type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                details TEXT NULL,
                old_value TEXT NULL,
                new_value TEXT NULL,
                amount DECIMAL(10, 2) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_reservation_history_reservation_id (
                    reservation_id
                ),
                KEY idx_reservation_history_created_at (
                    created_at
                ),
                KEY idx_reservation_history_event_type (
                    event_type
                )
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array{
     *     id: int,
     *     reservation_id: int,
     *     event_type: string,
     *     title: string,
     *     details: string|null,
     *     old_value: string|null,
     *     new_value: string|null,
     *     amount: string|null,
     *     created_at: string|null
     * }
     */
    private static function mapRow(
        array $row
    ): array {
        return [
            'id' => (int) (
                $row['id']
                ?? 0
            ),
            'reservation_id' => (int) (
                $row['reservation_id']
                ?? 0
            ),
            'event_type' => (string) (
                $row['event_type']
                ?? ''
            ),
            'title' => (string) (
                $row['title']
                ?? ''
            ),
            'details' => self::nullableText(
                isset($row['details'])
                    ? (string) $row['details']
                    : null
            ),
            'old_value' => self::nullableText(
                isset($row['old_value'])
                    ? (string) $row['old_value']
                    : null
            ),
            'new_value' => self::nullableText(
                isset($row['new_value'])
                    ? (string) $row['new_value']
                    : null
            ),
            'amount' => isset($row['amount'])
                && $row['amount'] !== null
                    ? (string) $row['amount']
                    : null,
            'created_at' => isset(
                $row['created_at']
            )
                ? (string) $row['created_at']
                : null,
        ];
    }

    private static function nullableText(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            $value
        );

        return $value !== ''
            ? $value
            : null;
    }
}
