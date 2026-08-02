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
                started_at
            ) VALUES (
                :template_id,
                :reservation_id,
                :scheduled_for,
                "PROCESSING",
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

        self::$structureEnsured = true;
    }
}
