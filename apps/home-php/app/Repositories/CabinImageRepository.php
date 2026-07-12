<?php

declare(strict_types=1);

final class CabinImageRepository
{
    /**
     * @return array<int, array{
     *     id: int,
     *     cabin_id: int,
     *     image_path: string,
     *     alt_text: string|null,
     *     sort_order: int,
     *     is_main: int,
     *     created_at: string
     * }>
     */
    public static function allForCabin(int $cabinId): array
    {
        self::ensureTable();

        $connection = Database::connection();
        $imageColumn = self::imageColumn();

        $statement = $connection->prepare(
            'SELECT
                id,
                cabin_id,
                ' . $imageColumn . ' AS image_path,
                alt_text,
                sort_order,
                is_main,
                created_at
            FROM cabin_images
            WHERE cabin_id = :cabin_id
            ORDER BY is_main DESC, sort_order ASC, id ASC'
        );

        $statement->execute([
            'cabin_id' => $cabinId,
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
     *     cabin_id: int,
     *     image_path: string,
     *     alt_text: string|null,
     *     sort_order: int,
     *     is_main: int,
     *     created_at: string
     * }|null
     */
    public static function find(int $id): ?array
    {
        self::ensureTable();

        $connection = Database::connection();
        $imageColumn = self::imageColumn();

        $statement = $connection->prepare(
            'SELECT
                id,
                cabin_id,
                ' . $imageColumn . ' AS image_path,
                alt_text,
                sort_order,
                is_main,
                created_at
            FROM cabin_images
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

    public static function countForCabin(int $cabinId): int
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT COUNT(*) AS total
            FROM cabin_images
            WHERE cabin_id = :cabin_id'
        );

        $statement->execute([
            'cabin_id' => $cabinId,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return 0;
        }

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @param array{
     *     cabin_id: int,
     *     image_path: string,
     *     alt_text: string|null,
     *     is_main: int
     * } $data
     */
    public static function create(array $data): int
    {
        self::ensureTable();

        $connection = Database::connection();
        $imageColumn = self::imageColumn();

        $connection->beginTransaction();

        try {
            if ((int) $data['is_main'] === 1) {
                $resetStatement = $connection->prepare(
                    'UPDATE cabin_images
                    SET is_main = 0
                    WHERE cabin_id = :cabin_id'
                );

                $resetStatement->execute([
                    'cabin_id' => $data['cabin_id'],
                ]);
            }

            $sortOrderStatement = $connection->prepare(
                'SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order
                FROM cabin_images
                WHERE cabin_id = :cabin_id'
            );

            $sortOrderStatement->execute([
                'cabin_id' => $data['cabin_id'],
            ]);

            $sortOrderRow = $sortOrderStatement->fetch();
            $sortOrder = is_array($sortOrderRow) ? (int) ($sortOrderRow['next_order'] ?? 1) : 1;

            $statement = $connection->prepare(
                'INSERT INTO cabin_images (
                    cabin_id,
                    ' . $imageColumn . ',
                    alt_text,
                    sort_order,
                    is_main
                ) VALUES (
                    :cabin_id,
                    :image_path,
                    :alt_text,
                    :sort_order,
                    :is_main
                )'
            );

            $statement->execute([
                'cabin_id' => $data['cabin_id'],
                'image_path' => $data['image_path'],
                'alt_text' => $data['alt_text'],
                'sort_order' => $sortOrder,
                'is_main' => $data['is_main'],
            ]);

            $id = (int) $connection->lastInsertId();

            $connection->commit();

            return $id;
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    public static function setMain(int $id, int $cabinId): void
    {
        self::ensureTable();

        $connection = Database::connection();

        $connection->beginTransaction();

        try {
            $resetStatement = $connection->prepare(
                'UPDATE cabin_images
                SET is_main = 0
                WHERE cabin_id = :cabin_id'
            );

            $resetStatement->execute([
                'cabin_id' => $cabinId,
            ]);

            $mainStatement = $connection->prepare(
                'UPDATE cabin_images
                SET is_main = 1
                WHERE id = :id
                AND cabin_id = :cabin_id'
            );

            $mainStatement->execute([
                'id' => $id,
                'cabin_id' => $cabinId,
            ]);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    public static function delete(int $id, int $cabinId): void
    {
        self::ensureTable();

        $connection = Database::connection();

        $image = self::find($id);

        $connection->beginTransaction();

        try {
            $statement = $connection->prepare(
                'DELETE FROM cabin_images
                WHERE id = :id
                AND cabin_id = :cabin_id'
            );

            $statement->execute([
                'id' => $id,
                'cabin_id' => $cabinId,
            ]);

            if ($image !== null && $image['is_main'] === 1) {
                $nextStatement = $connection->prepare(
                    'SELECT id
                    FROM cabin_images
                    WHERE cabin_id = :cabin_id
                    ORDER BY sort_order ASC, id ASC
                    LIMIT 1'
                );

                $nextStatement->execute([
                    'cabin_id' => $cabinId,
                ]);

                $nextRow = $nextStatement->fetch();

                if (is_array($nextRow) && isset($nextRow['id'])) {
                    $mainStatement = $connection->prepare(
                        'UPDATE cabin_images
                        SET is_main = 1
                        WHERE id = :id
                        AND cabin_id = :cabin_id'
                    );

                    $mainStatement->execute([
                        'id' => (int) $nextRow['id'],
                        'cabin_id' => $cabinId,
                    ]);
                }
            }

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    public static function ensureTable(): void
    {
        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS cabin_images (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                cabin_id INT NOT NULL,
                image_url VARCHAR(255) NOT NULL,
                alt_text VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_main TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX cabin_images_cabin_id_idx (cabin_id),
                INDEX cabin_images_is_main_idx (is_main)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $columns = self::tableColumns();

        if (!in_array('image_url', $columns, true) && !in_array('image_path', $columns, true)) {
            $connection->exec(
                'ALTER TABLE cabin_images
                ADD COLUMN image_url VARCHAR(255) NOT NULL AFTER cabin_id'
            );
        }
    }

    private static function imageColumn(): string
    {
        $columns = self::tableColumns();

        if (in_array('image_url', $columns, true)) {
            return 'image_url';
        }

        if (in_array('image_path', $columns, true)) {
            return 'image_path';
        }

        return 'image_url';
    }

    /**
     * @return array<int, string>
     */
    private static function tableColumns(): array
    {
        $connection = Database::connection();

        $statement = $connection->query('SHOW COLUMNS FROM cabin_images');

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        $columns = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (isset($row['Field'])) {
                $columns[] = (string) $row['Field'];
            }
        }

        return $columns;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{
     *     id: int,
     *     cabin_id: int,
     *     image_path: string,
     *     alt_text: string|null,
     *     sort_order: int,
     *     is_main: int,
     *     created_at: string
     * }
     */
    private static function mapRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'cabin_id' => (int) ($row['cabin_id'] ?? 0),
            'image_path' => (string) ($row['image_path'] ?? ''),
            'alt_text' => isset($row['alt_text']) ? (string) $row['alt_text'] : null,
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_main' => (int) ($row['is_main'] ?? 0),
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }
}