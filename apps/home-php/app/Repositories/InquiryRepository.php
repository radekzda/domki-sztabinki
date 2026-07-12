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

    public static function setStatus(int $id, string $status): void
    {
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
        $connection = Database::connection();

        $statement = $connection->prepare(
            'DELETE FROM inquiries
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);
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