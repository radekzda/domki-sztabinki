<?php

declare(strict_types=1);

final class IcalSyncLogRepository
{
    private static bool $tableEnsured = false;

    public static function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }

        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS ical_sync_logs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                cabin_id INT UNSIGNED NOT NULL,
                source VARCHAR(40) NOT NULL DEFAULT "BOOKING",
                sync_status VARCHAR(40) NOT NULL,
                total_events INT UNSIGNED NOT NULL DEFAULT 0,
                matched_reservations INT UNSIGNED NOT NULL DEFAULT 0,
                conflicts INT UNSIGNED NOT NULL DEFAULT 0,
                new_blocks INT UNSIGNED NOT NULL DEFAULT 0,
                existing_ical INT UNSIGNED NOT NULL DEFAULT 0,
                deactivated INT UNSIGNED NOT NULL DEFAULT 0,
                error_message TEXT NULL,
                started_at DATETIME NOT NULL,
                finished_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX ical_sync_logs_cabin_index (
                    cabin_id
                ),
                INDEX ical_sync_logs_status_index (
                    sync_status
                ),
                INDEX ical_sync_logs_created_at_index (
                    created_at
                ),
                CONSTRAINT ical_sync_logs_cabin_foreign
                    FOREIGN KEY (cabin_id)
                    REFERENCES cabins(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );

        self::$tableEnsured = true;
    }

    /**
     * @param array{
     *     cabin_id: int,
     *     source: string,
     *     sync_status: string,
     *     total_events?: int,
     *     matched_reservations?: int,
     *     conflicts?: int,
     *     new_blocks?: int,
     *     existing_ical?: int,
     *     deactivated?: int,
     *     error_message?: string|null,
     *     started_at: string,
     *     finished_at: string
     * } $data
     */
    public static function create(
        array $data
    ): int {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'INSERT INTO ical_sync_logs (
                cabin_id,
                source,
                sync_status,
                total_events,
                matched_reservations,
                conflicts,
                new_blocks,
                existing_ical,
                deactivated,
                error_message,
                started_at,
                finished_at
            ) VALUES (
                :cabin_id,
                :source,
                :sync_status,
                :total_events,
                :matched_reservations,
                :conflicts,
                :new_blocks,
                :existing_ical,
                :deactivated,
                :error_message,
                :started_at,
                :finished_at
            )'
        );

        $statement->execute([
            'cabin_id' => $data['cabin_id'],
            'source' => $data['source'],
            'sync_status' => $data['sync_status'],
            'total_events' => $data['total_events'] ?? 0,
            'matched_reservations' =>
                $data['matched_reservations'] ?? 0,
            'conflicts' => $data['conflicts'] ?? 0,
            'new_blocks' => $data['new_blocks'] ?? 0,
            'existing_ical' => $data['existing_ical'] ?? 0,
            'deactivated' => $data['deactivated'] ?? 0,
            'error_message' =>
                $data['error_message'] ?? null,
            'started_at' => $data['started_at'],
            'finished_at' => $data['finished_at'],
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function recentForCabin(
        int $cabinId,
        int $limit = 20
    ): array {
        self::ensureTable();

        $limit = max(
            1,
            min(
                100,
                $limit
            )
        );

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                cabin_id,
                source,
                sync_status,
                total_events,
                matched_reservations,
                conflicts,
                new_blocks,
                existing_ical,
                deactivated,
                error_message,
                started_at,
                finished_at,
                created_at
            FROM ical_sync_logs
            WHERE cabin_id = :cabin_id
            ORDER BY id DESC
            LIMIT ' . $limit
        );

        $statement->execute([
            'cabin_id' => $cabinId,
        ]);

        $rows = $statement->fetchAll();

        return is_array($rows)
            ? $rows
            : [];
    }
}