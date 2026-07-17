<?php

declare(strict_types=1);

final class MessageTemplateRepository
{
    /**
     * @return array<int, array{
     *     name: string,
     *     template_key: string,
     *     template_context: string,
     *     content: string,
     *     is_active: bool,
     *     sort_order: int
     * }>
     */
    public static function defaultTemplates(): array
    {
        return [
            [
                'name' => 'Odpowiedź na dostępne zapytanie',
                'template_key' => 'INQUIRY_AVAILABILITY',
                'template_context' => 'INQUIRY',
                'content' => <<<'TEXT'
Dzień dobry {{first_name}},

dziękujemy za zapytanie. Wybrany termin jest dostępny.

Cena pobytu wynosi {{total_price}} zł za {{nights}} {{night_label}}. Cena obejmuje pobyt do {{guests}} {{person_label}} oraz korzystanie z wyposażenia domku, grilla, łódki, kajaka i rowerków wodnych.

W celu potwierdzenia rezerwacji prosimy o informację zwrotną. Następnie prześlemy dane do wpłaty zadatku.

Pozdrawiamy serdecznie
{{property_name}}
TEXT,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Potwierdzenie rezerwacji',
                'template_key' => 'RESERVATION_CONFIRMATION',
                'template_context' => 'RESERVATION',
                'content' => <<<'TEXT'
Dzień dobry {{guest_name}},

dziękujemy. Potwierdzamy rezerwację.

Szczegóły rezerwacji:
Domek: {{cabin_name}}
Termin: {{start_date}} — {{end_date}}
Liczba nocy: {{nights}}
Liczba osób: {{guests}}
Cena pobytu: {{total_price}} zł

Zameldowanie od godz. {{check_in_time}}.
Wymeldowanie do godz. {{check_out_time}}.

Cena obejmuje korzystanie z wyposażenia domku, grilla, łódki, kajaka i rowerków wodnych.

W razie pytań prosimy o kontakt.

Pozdrawiamy serdecznie
{{property_name}}
TEXT,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Dane do wpłaty zadatku',
                'template_key' => 'DEPOSIT_PAYMENT',
                'template_context' => 'RESERVATION',
                'content' => <<<'TEXT'
Dzień dobry {{guest_name}},

w celu potwierdzenia rezerwacji prosimy o wpłatę zadatku.

Kwota zadatku: {{deposit_amount}} zł
Odbiorca: {{bank_account_holder}}
Numer rachunku: {{bank_account_number}}
Tytuł przelewu: {{payment_title}}

Domek: {{cabin_name}}
Data przyjazdu: {{start_date}}

Po zaksięgowaniu wpłaty rezerwacja zostanie oznaczona jako potwierdzona.

Pozdrawiamy serdecznie
{{property_name}}
TEXT,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'name' => 'Wiadomość przed przyjazdem',
                'template_key' => 'PRE_ARRIVAL',
                'template_context' => 'RESERVATION',
                'content' => <<<'TEXT'
Dzień dobry {{guest_name}},

przypominamy o zbliżającym się pobycie.

Szczegóły pobytu:
Domek: {{cabin_name}}
Termin: {{start_date}} — {{end_date}}
Zameldowanie od godz. {{check_in_time}}.
Wymeldowanie do godz. {{check_out_time}}.
Lokalizacja: {{location}}

Prosimy o kontakt około 30 minut przed przyjazdem.
Telefon kontaktowy: {{contact_phone}}

Życzymy spokojnej podróży i do zobaczenia!

Pozdrawiamy serdecznie
{{property_name}}
TEXT,
                'is_active' => true,
                'sort_order' => 40,
            ],
        ];
    }

    public static function ensureDefaultTemplates(): void
    {
        self::ensureTable();

        $connection = Database::connection();

        $countStatement = $connection->query(
            'SELECT COUNT(*) FROM message_templates'
        );

        $templateCount = $countStatement !== false
            ? (int) $countStatement->fetchColumn()
            : 0;

        if ($templateCount > 0) {
            return;
        }

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

        foreach (self::defaultTemplates() as $template) {
            $statement->execute([
                'name' => $template['name'],
                'template_key' => $template['template_key'],
                'template_context' => $template['template_context'],
                'content' => $template['content'],
                'is_active' => $template['is_active']
                    ? 1
                    : 0,
                'sort_order' => $template['sort_order'],
            ]);
        }
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
    public static function activeForContext(
        string $context
    ): array {
        self::ensureTable();

        $context = strtoupper(
            trim($context)
        );

        if ($context === '') {
            return [];
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
            WHERE is_active = 1
              AND (
                  template_context = :template_context
                  OR template_context = "GENERAL"
              )
            ORDER BY
                sort_order ASC,
                name ASC,
                id ASC'
        );

        $statement->execute([
            'template_context' => $context,
        ]);

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        $templates = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $templates[] = self::mapRow(
                $row
            );
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
