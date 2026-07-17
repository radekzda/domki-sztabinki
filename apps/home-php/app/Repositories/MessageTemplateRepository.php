<?php

declare(strict_types=1);

final class MessageTemplateRepository
{
    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     template_key: string|null,
     *     template_context: string,
     *     content: string,
     *     is_active: bool,
     *     sort_order: int,
     *     created_at: string|null,
     *     updated_at: string|null
     * }>
     */
    public static function all(): array
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT
                id,
                name,
                template_key,
                template_context,
                content,
                is_active,
                sort_order,
                created_at,
                updated_at
            FROM message_templates
            ORDER BY
                sort_order ASC,
                name ASC,
                id ASC'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        $templates = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $templates[] = self::mapRow($row);
        }

        return $templates;
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     template_key: string|null,
     *     template_context: string,
     *     content: string,
     *     is_active: bool,
     *     sort_order: int,
     *     created_at: string|null,
     *     updated_at: string|null
     * }>
     */
    public static function active(): array
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT
                id,
                name,
                template_key,
                template_context,
                content,
                is_active,
                sort_order,
                created_at,
                updated_at
            FROM message_templates
            WHERE is_active = 1
            ORDER BY
                sort_order ASC,
                name ASC,
                id ASC'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        $templates = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $templates[] = self::mapRow($row);
        }

        return $templates;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     template_key: string|null,
     *     template_context: string,
     *     content: string,
     *     is_active: bool,
     *     sort_order: int,
     *     created_at: string|null,
     *     updated_at: string|null
     * }|null
     */
    public static function find(int $id): ?array
    {
        self::ensureTable();

        if ($id < 1) {
            return null;
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                name,
                template_key,
                template_context,
                content,
                is_active,
                sort_order,
                created_at,
                updated_at
            FROM message_templates
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
     *     name: string,
     *     template_key: string|null,
     *     template_context: string,
     *     content: string,
     *     is_active: bool,
     *     sort_order: int,
     *     created_at: string|null,
     *     updated_at: string|null
     * }|null
     */
    public static function findByKey(string $templateKey): ?array
    {
        self::ensureTable();

        $templateKey = trim($templateKey);

        if ($templateKey === '') {
            return null;
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                name,
                template_key,
                template_context,
                content,
                is_active,
                sort_order,
                created_at,
                updated_at
            FROM message_templates
            WHERE template_key = :template_key
            LIMIT 1'
        );

        $statement->execute([
            'template_key' => $templateKey,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return self::mapRow($row);
    }

    /**
     * @param array{
     *     name: string,
     *     template_key?: string|null,
     *     template_context: string,
     *     content: string,
     *     is_active?: bool,
     *     sort_order?: int
     * } $data
     */
    public static function create(array $data): int
    {
        self::ensureTable();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'INSERT INTO message_templates (
                name,
                template_key,
                template_context,
                content,
                is_active,
                sort_order
            ) VALUES (
                :name,
                :template_key,
                :template_context,
                :content,
                :is_active,
                :sort_order
            )'
        );

        $templateKey = isset($data['template_key'])
            ? trim((string) $data['template_key'])
            : '';

        $statement->execute([
            'name' => trim($data['name']),
            'template_key' => $templateKey !== ''
                ? $templateKey
                : null,
            'template_context' => trim(
                $data['template_context']
            ),
            'content' => $data['content'],
            'is_active' => !isset($data['is_active'])
                || $data['is_active']
                    ? 1
                    : 0,
            'sort_order' => (int) (
                $data['sort_order']
                ?? 0
            ),
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * @param array{
     *     name: string,
     *     template_key?: string|null,
     *     template_context: string,
     *     content: string,
     *     is_active?: bool,
     *     sort_order?: int
     * } $data
     */
    public static function update(
        int $id,
        array $data
    ): bool {
        self::ensureTable();

        if ($id < 1) {
            return false;
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE message_templates
            SET
                name = :name,
                template_key = :template_key,
                template_context = :template_context,
                content = :content,
                is_active = :is_active,
                sort_order = :sort_order
            WHERE id = :id'
        );

        $templateKey = isset($data['template_key'])
            ? trim((string) $data['template_key'])
            : '';

        $statement->execute([
            'id' => $id,
            'name' => trim($data['name']),
            'template_key' => $templateKey !== ''
                ? $templateKey
                : null,
            'template_context' => trim(
                $data['template_context']
            ),
            'content' => $data['content'],
            'is_active' => !isset($data['is_active'])
                || $data['is_active']
                    ? 1
                    : 0,
            'sort_order' => (int) (
                $data['sort_order']
                ?? 0
            ),
        ]);

        return $statement->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        self::ensureTable();

        if ($id < 1) {
            return false;
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'DELETE FROM message_templates
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    public static function ensureTable(): void
    {
        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS message_templates (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                template_key VARCHAR(100) NULL,
                template_context VARCHAR(50) NOT NULL DEFAULT "GENERAL",
                content TEXT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_message_templates_template_key (template_key),
                KEY idx_message_templates_context (template_context),
                KEY idx_message_templates_active (is_active)
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array{
     *     id: int,
     *     name: string,
     *     template_key: string|null,
     *     template_context: string,
     *     content: string,
     *     is_active: bool,
     *     sort_order: int,
     *     created_at: string|null,
     *     updated_at: string|null
     * }
     */
    private static function mapRow(array $row): array
    {
        $templateKey = isset($row['template_key'])
            ? trim((string) $row['template_key'])
            : '';

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) (
                $row['name']
                ?? ''
            ),
            'template_key' => $templateKey !== ''
                ? $templateKey
                : null,
            'template_context' => (string) (
                $row['template_context']
                ?? 'GENERAL'
            ),
            'content' => (string) (
                $row['content']
                ?? ''
            ),
            'is_active' => (int) (
                $row['is_active']
                ?? 0
            ) === 1,
            'sort_order' => (int) (
                $row['sort_order']
                ?? 0
            ),
            'created_at' => isset($row['created_at'])
                ? (string) $row['created_at']
                : null,
            'updated_at' => isset($row['updated_at'])
                ? (string) $row['updated_at']
                : null,
        ];
    }
}
