<?php

declare(strict_types=1);

final class InquiryRepository
{
    /**
     * @return array<int, array{
     *     id: int,
     *     full_name: string,
     *     first_name: string|null,
     *     last_name: string|null,
     *     phone: string,
     *     email: string|null,
     *     cabin_id: int|null,
     *     cabin_name: string|null,
     *     linked_cabin_name: string|null,
     *     date_from: string,
     *     date_to: string,
     *     guests: int,
     *     adults: int,
     *     children: int,
     *     city: string|null,
     *     country: string|null,
     *     notes: string|null,
     *     status: string,
     *     source: string,
     *     created_at: string
     * }>
     */
    public static function all(): array
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT
                inquiries.id,
                inquiries.full_name,
                inquiries.first_name,
                inquiries.last_name,
                inquiries.phone,
                inquiries.email,
                inquiries.cabin_id,
                inquiries.cabin_name,
                cabins.name AS linked_cabin_name,
                inquiries.date_from,
                inquiries.date_to,
                inquiries.guests,
                inquiries.adults,
                inquiries.children,
                inquiries.city,
                inquiries.country,
                inquiries.notes,
                inquiries.status,
                inquiries.source,
                inquiries.created_at
            FROM inquiries
            LEFT JOIN cabins ON cabins.id = inquiries.cabin_id
            ORDER BY inquiries.created_at DESC, inquiries.id DESC'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return self::mapRow($row);
        }, $rows);
    }

    /**
     * @return array{
     *     id: int,
     *     full_name: string,
     *     first_name: string|null,
     *     last_name: string|null,
     *     phone: string,
     *     email: string|null,
     *     cabin_id: int|null,
     *     cabin_name: string|null,
     *     linked_cabin_name: string|null,
     *     date_from: string,
     *     date_to: string,
     *     guests: int,
     *     adults: int,
     *     children: int,
     *     city: string|null,
     *     country: string|null,
     *     notes: string|null,
     *     status: string,
     *     source: string,
     *     created_at: string
     * }|null
     */
    public static function find(int $id): ?array
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                inquiries.id,
                inquiries.full_name,
                inquiries.first_name,
                inquiries.last_name,
                inquiries.phone,
                inquiries.email,
                inquiries.cabin_id,
                inquiries.cabin_name,
                cabins.name AS linked_cabin_name,
                inquiries.date_from,
                inquiries.date_to,
                inquiries.guests,
                inquiries.adults,
                inquiries.children,
                inquiries.city,
                inquiries.country,
                inquiries.notes,
                inquiries.status,
                inquiries.source,
                inquiries.created_at
            FROM inquiries
            LEFT JOIN cabins ON cabins.id = inquiries.cabin_id
            WHERE inquiries.id = :id
            LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return self::mapRow($row);
    }

    /**
     * @param array{
     *     full_name: string,
     *     first_name: string|null,
     *     last_name: string|null,
     *     phone: string,
     *     email: string|null,
     *     cabin_id: int|null,
     *     cabin_name: string|null,
     *     date_from: string,
     *     date_to: string,
     *     guests: int,
     *     adults: int,
     *     children: int,
     *     city: string|null,
     *     country: string|null,
     *     notes: string|null,
     *     status: string,
     *     source: string
     * } $data
     */
    public static function create(array $data): int
    {
        self::ensureTable();

        if ($data['cabin_id'] !== null) {
            $hasOverlap = ReservationRepository::hasBlockingOverlap(
                (int) $data['cabin_id'],
                $data['date_from'],
                $data['date_to']
            );

            if ($hasOverlap) {
                throw new RuntimeException(
                    'Wybrany domek jest już zajęty w tym terminie. Wybierz inny termin albo wyślij zapytanie bez wskazywania konkretnego domku.'
                );
            }
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'INSERT INTO inquiries (
                full_name,
                first_name,
                last_name,
                phone,
                email,
                cabin_id,
                cabin_name,
                date_from,
                date_to,
                guests,
                adults,
                children,
                city,
                country,
                notes,
                status,
                source
            ) VALUES (
                :full_name,
                :first_name,
                :last_name,
                :phone,
                :email,
                :cabin_id,
                :cabin_name,
                :date_from,
                :date_to,
                :guests,
                :adults,
                :children,
                :city,
                :country,
                :notes,
                :status,
                :source
            )'
        );

        $statement->execute([
            'full_name' => $data['full_name'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'cabin_id' => $data['cabin_id'],
            'cabin_name' => $data['cabin_name'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'guests' => $data['guests'],
            'adults' => $data['adults'],
            'children' => $data['children'],
            'city' => $data['city'],
            'country' => $data['country'],
            'notes' => $data['notes'],
            'status' => $data['status'],
            'source' => $data['source'],
        ]);

        return (int) $connection->lastInsertId();
    }

    public static function setStatus(int $id, string $status): void
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE inquiries
            SET status = :status
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'status' => $status,
        ]);
    }

    public static function delete(int $id): void
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'DELETE FROM inquiries
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);
    }

    public static function ensureTable(): void
    {
        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS inquiries (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                full_name VARCHAR(190) NOT NULL,
                first_name VARCHAR(100) NULL,
                last_name VARCHAR(100) NULL,
                phone VARCHAR(50) NOT NULL,
                email VARCHAR(190) NULL,
                cabin_id INT NULL,
                cabin_name VARCHAR(190) NULL,
                date_from DATE NOT NULL,
                date_to DATE NOT NULL,
                guests INT NOT NULL DEFAULT 1,
                adults INT NOT NULL DEFAULT 1,
                children INT NOT NULL DEFAULT 0,
                city VARCHAR(100) NULL,
                country VARCHAR(100) NULL,
                notes TEXT NULL,
                status VARCHAR(50) NOT NULL DEFAULT "NEW",
                source VARCHAR(50) NOT NULL DEFAULT "WWW",
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX inquiries_cabin_id_idx (cabin_id),
                INDEX inquiries_status_idx (status),
                INDEX inquiries_date_from_idx (date_from),
                INDEX inquiries_created_at_idx (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array{
     *     id: int,
     *     full_name: string,
     *     first_name: string|null,
     *     last_name: string|null,
     *     phone: string,
     *     email: string|null,
     *     cabin_id: int|null,
     *     cabin_name: string|null,
     *     linked_cabin_name: string|null,
     *     date_from: string,
     *     date_to: string,
     *     guests: int,
     *     adults: int,
     *     children: int,
     *     city: string|null,
     *     country: string|null,
     *     notes: string|null,
     *     status: string,
     *     source: string,
     *     created_at: string
     * }
     */
    private static function mapRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'full_name' => (string) ($row['full_name'] ?? ''),
            'first_name' => isset($row['first_name']) ? (string) $row['first_name'] : null,
            'last_name' => isset($row['last_name']) ? (string) $row['last_name'] : null,
            'phone' => (string) ($row['phone'] ?? ''),
            'email' => isset($row['email']) ? (string) $row['email'] : null,
            'cabin_id' => isset($row['cabin_id']) ? (int) $row['cabin_id'] : null,
            'cabin_name' => isset($row['cabin_name']) ? (string) $row['cabin_name'] : null,
            'linked_cabin_name' => isset($row['linked_cabin_name']) ? (string) $row['linked_cabin_name'] : null,
            'date_from' => (string) ($row['date_from'] ?? ''),
            'date_to' => (string) ($row['date_to'] ?? ''),
            'guests' => (int) ($row['guests'] ?? 0),
            'adults' => (int) ($row['adults'] ?? 0),
            'children' => (int) ($row['children'] ?? 0),
            'city' => isset($row['city']) ? (string) $row['city'] : null,
            'country' => isset($row['country']) ? (string) $row['country'] : null,
            'notes' => isset($row['notes']) ? (string) $row['notes'] : null,
            'status' => (string) ($row['status'] ?? 'NEW'),
            'source' => (string) ($row['source'] ?? 'WWW'),
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }
}