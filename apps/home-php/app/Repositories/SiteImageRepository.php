<?php

declare(strict_types=1);

final class SiteImageRepository
{
    /**
     * @return array<int, array{
     *     id: int,
     *     image_url: string,
     *     alt_text: string|null,
     *     image_type: string,
     *     sort_order: int,
     *     is_main: int,
     *     created_at: string
     * }>
     */
    public static function all(): array
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT
                id,
                image_url,
                alt_text,
                image_type,
                sort_order,
                is_main,
                created_at
            FROM site_images
            ORDER BY
                FIELD(image_type, "HERO", "GALLERY", "ATTRACTION", "GENERAL"),
                sort_order ASC,
                id DESC'
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
     * @return array<int, array{
     *     id: int,
     *     image_url: string,
     *     alt_text: string|null,
     *     image_type: string,
     *     sort_order: int,
     *     is_main: int,
     *     created_at: string
     * }>
     */
    public static function allByType(string $imageType): array
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                image_url,
                alt_text,
                image_type,
                sort_order,
                is_main,
                created_at
            FROM site_images
            WHERE image_type = :image_type
            ORDER BY sort_order ASC, id DESC'
        );

        $statement->execute([
            'image_type' => self::normalizeType($imageType),
        ]);

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
     *     image_url: string,
     *     alt_text: string|null,
     *     image_type: string,
     *     sort_order: int,
     *     is_main: int,
     *     created_at: string
     * }|null
     */
    public static function find(int $id): ?array
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                image_url,
                alt_text,
                image_type,
                sort_order,
                is_main,
                created_at
            FROM site_images
            WHERE id = :id
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
     * @return array{
     *     id: int,
     *     image_url: string,
     *     alt_text: string|null,
     *     image_type: string,
     *     sort_order: int,
     *     is_main: int,
     *     created_at: string
     * }|null
     */
    public static function mainByType(string $imageType): ?array
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                image_url,
                alt_text,
                image_type,
                sort_order,
                is_main,
                created_at
            FROM site_images
            WHERE image_type = :image_type
            ORDER BY is_main DESC, sort_order ASC, id DESC
            LIMIT 1'
        );

        $statement->execute([
            'image_type' => self::normalizeType($imageType),
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return self::mapRow($row);
    }

    /**
     * @param array{
     *     image_url: string,
     *     alt_text: string|null,
     *     image_type: string,
     *     sort_order: int,
     *     is_main: int
     * } $data
     */
    public static function create(array $data): int
    {
        self::ensureTable();

        $connection = Database::connection();

        $imageType = self::normalizeType($data['image_type']);

        if ((int) $data['is_main'] === 1) {
            self::clearMainForType($imageType);
        }

        $statement = $connection->prepare(
            'INSERT INTO site_images (
                image_url,
                alt_text,
                image_type,
                sort_order,
                is_main
            ) VALUES (
                :image_url,
                :alt_text,
                :image_type,
                :sort_order,
                :is_main
            )'
        );

        $statement->execute([
            'image_url' => $data['image_url'],
            'alt_text' => $data['alt_text'],
            'image_type' => $imageType,
            'sort_order' => $data['sort_order'],
            'is_main' => $data['is_main'],
        ]);

        return (int) $connection->lastInsertId();
    }

    public static function setMain(int $id): void
    {
        self::ensureTable();

        $image = self::find($id);

        if ($image === null) {
            return;
        }

        $imageType = self::normalizeType($image['image_type']);

        self::clearMainForType($imageType);

        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE site_images
            SET is_main = 1
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);
    }

    public static function delete(int $id): ?string
    {
        self::ensureTable();

        $image = self::find($id);

        if ($image === null) {
            return null;
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'DELETE FROM site_images
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);

        return $image['image_url'];
    }

    public static function ensureTable(): void
    {
        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS site_images (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                image_url VARCHAR(255) NOT NULL,
                alt_text VARCHAR(255) NULL,
                image_type VARCHAR(50) NOT NULL DEFAULT "GALLERY",
                sort_order INT NOT NULL DEFAULT 0,
                is_main TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX site_images_image_type_idx (image_type),
                INDEX site_images_is_main_idx (is_main),
                INDEX site_images_sort_order_idx (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            'HERO' => 'Zdjęcie główne strony',
            'GALLERY' => 'Galeria publiczna',
            'ATTRACTION' => 'Atrakcje i otoczenie',
            'GENERAL' => 'Ogólne zdjęcie strony',
        ];
    }

    public static function normalizeType(string $imageType): string
    {
        $imageType = strtoupper(trim($imageType));

        return match ($imageType) {
            'HERO', 'GALLERY', 'ATTRACTION', 'GENERAL' => $imageType,
            default => 'GALLERY',
        };
    }

    private static function clearMainForType(string $imageType): void
    {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE site_images
            SET is_main = 0
            WHERE image_type = :image_type'
        );

        $statement->execute([
            'image_type' => self::normalizeType($imageType),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{
     *     id: int,
     *     image_url: string,
     *     alt_text: string|null,
     *     image_type: string,
     *     sort_order: int,
     *     is_main: int,
     *     created_at: string
     * }
     */
    private static function mapRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'image_url' => (string) ($row['image_url'] ?? ''),
            'alt_text' => isset($row['alt_text']) ? (string) $row['alt_text'] : null,
            'image_type' => self::normalizeType((string) ($row['image_type'] ?? 'GALLERY')),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_main' => (int) ($row['is_main'] ?? 0),
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }
}