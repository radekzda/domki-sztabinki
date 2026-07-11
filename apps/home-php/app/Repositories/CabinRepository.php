<?php

declare(strict_types=1);

final class CabinRepository
{
    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     short_name: string|null,
     *     max_guests: int,
     *     bedrooms: int,
     *     bathrooms: int,
     *     price_per_night: int,
     *     price_one_night: int,
     *     price_two_nights: int,
     *     price_three_nights: int,
     *     price_four_nights: int,
     *     price_five_nights: int,
     *     price_six_nights: int,
     *     price_seven_plus_nights: int,
     *     is_active: int,
     *     sort_order: int,
     *     created_at: string
     * }>
     */
    public static function all(): array
    {
        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT
                id,
                name,
                short_name,
                max_guests,
                bedrooms,
                bathrooms,
                price_per_night,
                price_one_night,
                price_two_nights,
                price_three_nights,
                price_four_nights,
                price_five_nights,
                price_six_nights,
                price_seven_plus_nights,
                is_active,
                sort_order,
                created_at
            FROM cabins
            ORDER BY sort_order ASC, id ASC'
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
                'name' => (string) ($row['name'] ?? ''),
                'short_name' => isset($row['short_name']) ? (string) $row['short_name'] : null,
                'max_guests' => (int) ($row['max_guests'] ?? 0),
                'bedrooms' => (int) ($row['bedrooms'] ?? 0),
                'bathrooms' => (int) ($row['bathrooms'] ?? 0),
                'price_per_night' => (int) ($row['price_per_night'] ?? 0),
                'price_one_night' => (int) ($row['price_one_night'] ?? 0),
                'price_two_nights' => (int) ($row['price_two_nights'] ?? 0),
                'price_three_nights' => (int) ($row['price_three_nights'] ?? 0),
                'price_four_nights' => (int) ($row['price_four_nights'] ?? 0),
                'price_five_nights' => (int) ($row['price_five_nights'] ?? 0),
                'price_six_nights' => (int) ($row['price_six_nights'] ?? 0),
                'price_seven_plus_nights' => (int) ($row['price_seven_plus_nights'] ?? 0),
                'is_active' => (int) ($row['is_active'] ?? 0),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }, $rows);
    }

    /**
     * @param array{
     *     name: string,
     *     short_name: string|null,
     *     description: string,
     *     max_guests: int,
     *     bedrooms: int,
     *     bathrooms: int,
     *     price_per_night: int,
     *     price_one_night: int,
     *     price_two_nights: int,
     *     price_three_nights: int,
     *     price_four_nights: int,
     *     price_five_nights: int,
     *     price_six_nights: int,
     *     price_seven_plus_nights: int,
     *     is_active: int,
     *     sort_order: int
     * } $data
     */
    public static function create(array $data): int
    {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'INSERT INTO cabins (
                name,
                short_name,
                description,
                max_guests,
                bedrooms,
                bathrooms,
                price_per_night,
                price_one_night,
                price_two_nights,
                price_three_nights,
                price_four_nights,
                price_five_nights,
                price_six_nights,
                price_seven_plus_nights,
                is_active,
                sort_order
            ) VALUES (
                :name,
                :short_name,
                :description,
                :max_guests,
                :bedrooms,
                :bathrooms,
                :price_per_night,
                :price_one_night,
                :price_two_nights,
                :price_three_nights,
                :price_four_nights,
                :price_five_nights,
                :price_six_nights,
                :price_seven_plus_nights,
                :is_active,
                :sort_order
            )'
        );

        $statement->execute([
            'name' => $data['name'],
            'short_name' => $data['short_name'],
            'description' => $data['description'],
            'max_guests' => $data['max_guests'],
            'bedrooms' => $data['bedrooms'],
            'bathrooms' => $data['bathrooms'],
            'price_per_night' => $data['price_per_night'],
            'price_one_night' => $data['price_one_night'],
            'price_two_nights' => $data['price_two_nights'],
            'price_three_nights' => $data['price_three_nights'],
            'price_four_nights' => $data['price_four_nights'],
            'price_five_nights' => $data['price_five_nights'],
            'price_six_nights' => $data['price_six_nights'],
            'price_seven_plus_nights' => $data['price_seven_plus_nights'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ]);

        return (int) $connection->lastInsertId();
    }
}