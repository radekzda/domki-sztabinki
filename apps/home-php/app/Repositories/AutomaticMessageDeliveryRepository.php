<?php

declare(strict_types=1);

final class AutomaticMessageDeliveryRepository
{
    private static bool $structureEnsured = false;

    public static function claim(
        int $templateId,
        int $reservationId,
        string $scheduledFor,
        DateTimeImmutable $now
    ): ?int {
        self::ensureStructure();

        if ($templateId < 1 || $reservationId < 1) {
            return null;
        }

        $connection = Database::connection();

        if (
            self::wasSentForTemplateAndReservation(
                $templateId,
                $reservationId
            )
        ) {
            return null;
        }

        $statement = $connection->prepare(
            'SELECT
                id,
                status,
                updated_at
            FROM automatic_message_deliveries
            WHERE template_id = :template_id
            AND reservation_id = :reservation_id
            AND scheduled_for = :scheduled_for
            LIMIT 1'
        );

        $statement->execute([
            'template_id' => $templateId,
            'reservation_id' => $reservationId,
            'scheduled_for' => $scheduledFor,
        ]);

        $row = $statement->fetch();

        if (is_array($row)) {
            $status = strtoupper(
                trim((string) ($row['status'] ?? ''))
            );

            if ($status === 'SENT') {
                return null;
            }

            if ($status === 'PROCESSING') {
                $updatedAt = trim(
                    (string) ($row['updated_at'] ?? '')
                );

                try {
                    $updated = new DateTimeImmutable($updatedAt);

                    if ($updated > $now->modify('-30 minutes')) {
                        return null;
                    }
                } catch (Throwable $exception) {
                    // Nieprawidłowa data nie blokuje ponowienia.
                }
            }

            $id = (int) ($row['id'] ?? 0);

            if ($id < 1) {
                return null;
            }

            $update = $connection->prepare(
                'UPDATE automatic_message_deliveries
                SET
                    status = "PROCESSING",
                    delivery_source = "AUTOMATIC",
                    recipient = NULL,
                    subject = NULL,
                    error_message = NULL,
                    started_at = :started_at,
                    sent_at = NULL
                WHERE id = :id'
            );

            $update->execute([
                'id' => $id,
                'started_at' => $now->format('Y-m-d H:i:s'),
            ]);

            return $id;
        }

        $insert = $connection->prepare(
            'INSERT INTO automatic_message_deliveries (
                template_id,
                reservation_id,
                scheduled_for,
                status,
                delivery_source,
                started_at
            ) VALUES (
                :template_id,
                :reservation_id,
                :scheduled_for,
                "PROCESSING",
                "AUTOMATIC",
                :started_at
            )'
        );

        $insert->execute([
            'template_id' => $templateId,
            'reservation_id' => $reservationId,
            'scheduled_for' => $scheduledFor,
            'started_at' => $now->format('Y-m-d H:i:s'),
        ]);

        return (int) $connection->lastInsertId();
    }

    public static function wasSentForTemplateAndReservation(
        int $templateId,
        int $reservationId
    ): bool {
        self::ensureStructure();

        if ($templateId < 1 || $reservationId < 1) {
            return false;
        }

        $statement = Database::connection()->prepare(
            'SELECT 1
            FROM automatic_message_deliveries
            WHERE template_id = :template_id
            AND reservation_id = :reservation_id
            AND status = "SENT"
            LIMIT 1'
        );

        $statement->execute([
            'template_id' => $templateId,
            'reservation_id' => $reservationId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public static function recordManualSent(
        int $templateId,
        int $reservationId,
        string $recipient,
        string $subject,
        DateTimeImmutable $sentAt
    ): void {
        self::ensureStructure();

        if ($templateId < 1 || $reservationId < 1) {
            throw new InvalidArgumentException(
                'Nieprawidłowe dane ręcznej wysyłki.'
            );
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO automatic_message_deliveries (
                template_id,
                reservation_id,
                scheduled_for,
                status,
                delivery_source,
                recipient,
                subject,
                error_message,
                started_at,
                sent_at
            ) VALUES (
                :template_id,
                :reservation_id,
                :scheduled_for,
                "SENT",
                "MANUAL",
                :recipient,
                :subject,
                NULL,
                :started_at,
                :sent_at
            )'
        );

        $formattedDate =
            $sentAt->format('Y-m-d H:i:s');

        $statement->execute([
            'template_id' => $templateId,
            'reservation_id' => $reservationId,
            'scheduled_for' => $formattedDate,
            'recipient' => $recipient,
            'subject' => $subject,
            'started_at' => $formattedDate,
            'sent_at' => $formattedDate,
        ]);
    }

    public static function markSent(
        int $id,
        string $recipient,
        string $subject,
        DateTimeImmutable $sentAt
    ): void {
        self::ensureStructure();

        $statement = Database::connection()->prepare(
            'UPDATE automatic_message_deliveries
            SET
                status = "SENT",
                delivery_source = "AUTOMATIC",
                recipient = :recipient,
                subject = :subject,
                error_message = NULL,
                sent_at = :sent_at
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'recipient' => $recipient,
            'subject' => $subject,
            'sent_at' => $sentAt->format('Y-m-d H:i:s'),
        ]);
    }

    public static function markFailed(
        int $id,
        string $errorMessage
    ): void {
        self::ensureStructure();

        $statement = Database::connection()->prepare(
            'UPDATE automatic_message_deliveries
            SET
                status = "FAILED",
                error_message = :error_message
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'error_message' => mb_substr(
                trim($errorMessage),
                0,
                2000
            ),
        ]);
    }

    /**
     * @param array<int, int> $reservationIds
     *
     * @return array<int, array<string, mixed>>
     */
    public static function latestForTemplateAndReservations(
        int $templateId,
        array $reservationIds
    ): array {
        self::ensureStructure();

        if ($templateId < 1) {
            return [];
        }

        $normalizedIds = [];

        foreach ($reservationIds as $reservationId) {
            $reservationId = (int) $reservationId;

            if ($reservationId > 0) {
                $normalizedIds[$reservationId] =
                    $reservationId;
            }
        }

        if ($normalizedIds === []) {
            return [];
        }

        $parameters = [
            'template_id' => $templateId,
        ];

        $placeholders = [];

        foreach (
            array_values($normalizedIds)
            as $index => $reservationId
        ) {
            $parameterName =
                'reservation_id_' . $index;

            $placeholders[] =
                ':' . $parameterName;

            $parameters[$parameterName] =
                $reservationId;
        }

        $statement = Database::connection()->prepare(
            'SELECT
                id,
                template_id,
                reservation_id,
                scheduled_for,
                status,
                delivery_source,
                recipient,
                subject,
                error_message,
                started_at,
                sent_at,
                created_at,
                updated_at
            FROM automatic_message_deliveries
            WHERE template_id = :template_id
            AND reservation_id IN ('
                . implode(', ', $placeholders)
                . ')
            ORDER BY
                reservation_id ASC,
                COALESCE(
                    sent_at,
                    updated_at,
                    created_at
                ) DESC,
                id DESC'
        );

        $statement->execute($parameters);

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        $latest = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $reservationId = (int) (
                $row['reservation_id']
                ?? 0
            );

            if (
                $reservationId < 1
                || isset($latest[$reservationId])
            ) {
                continue;
            }

            $latest[$reservationId] = $row;
        }

        return $latest;
    }

    public static function ensureStructure(): void
    {
        if (self::$structureEnsured) {
            return;
        }

        MessageTemplateRepository::ensureTable();

        Database::connection()->exec(
            'CREATE TABLE IF NOT EXISTS automatic_message_deliveries (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                template_id INT UNSIGNED NOT NULL,
                reservation_id INT UNSIGNED NOT NULL,
                scheduled_for DATETIME NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT "PROCESSING",
                delivery_source VARCHAR(20) NOT NULL DEFAULT "AUTOMATIC",
                recipient VARCHAR(190) NULL,
                subject VARCHAR(255) NULL,
                error_message TEXT NULL,
                started_at DATETIME NOT NULL,
                sent_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY automatic_message_unique (
                    template_id,
                    reservation_id,
                    scheduled_for
                ),
                INDEX automatic_message_status_index (status),
                INDEX automatic_message_scheduled_index (scheduled_for),
                CONSTRAINT automatic_message_template_foreign
                    FOREIGN KEY (template_id)
                    REFERENCES message_templates(id)
                    ON DELETE CASCADE,
                CONSTRAINT automatic_message_reservation_foreign
                    FOREIGN KEY (reservation_id)
                    REFERENCES reservations(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );

        self::ensureDeliverySourceColumn();

        self::$structureEnsured = true;
    }

    private static function ensureDeliverySourceColumn(): void
    {
        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = "automatic_message_deliveries"
            AND COLUMN_NAME = "delivery_source"'
        );

        if (
            $statement !== false
            && (int) $statement->fetchColumn() > 0
        ) {
            return;
        }

        $connection->exec(
            'ALTER TABLE automatic_message_deliveries
            ADD COLUMN delivery_source VARCHAR(20)
            NOT NULL DEFAULT "AUTOMATIC"
            AFTER status'
        );
    }
}
