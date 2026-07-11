<?php

declare(strict_types=1);

final class GuestRepository
{
    /**
     * @return array<int, array{
     *     id: int,
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone: string|null,
     *     country: string|null,
     *     city: string|null,
     *     is_vip: int,
     *     source: string,
     *     created_at: string
     * }>
     */
    public static function all(): array
    {
        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT
                id,
                first_name,
                last_name,
                email,
                phone,
                country,
                city,
                is_vip,
                source,
                created_at
            FROM guests
            ORDER BY last_name ASC, first_name ASC, id DESC'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'first_name' => (string) ($row['first_name'] ?? ''),
                'last_name' => (string) ($row['last_name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'phone' => isset($row['phone']) ? (string) $row['phone'] : null,
                'country' => isset($row['country']) ? (string) $row['country'] : null,
                'city' => isset($row['city']) ? (string) $row['city'] : null,
                'is_vip' => (int) ($row['is_vip'] ?? 0),
                'source' => (string) ($row['source'] ?? 'MANUAL'),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }, $rows);
    }
}