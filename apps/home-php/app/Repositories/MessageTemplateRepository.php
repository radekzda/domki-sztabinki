<?php

declare(strict_types=1);

final class MessageTemplateRepository
{
    private static bool $structureEnsured = false;

    /**
     * @return array<int, array{
     *     name: string,
     *     template_key: string,
     *     template_context: string,
     *     content: string,
     *     is_active: bool,
     *     sort_order: int,
     *     automation_enabled?: bool,
     *     automation_subject?: string|null,
     *     automation_reference?: string,
     *     automation_offset_days?: int,
     *     automation_send_time?: string
     * }>
     */
    public static function defaultTemplates(): array
    {
        return [
            [
                'name' => 'Automatyczne potwierdzenie otrzymania zapytania',
                'template_key' => 'INQUIRY_RECEIVED_CONFIRMATION',
                'template_context' => 'INQUIRY',
                'content' => <<<'TEXT'
{{greeting}}

dziękujemy za przesłanie zapytania.
Otrzymaliśmy je poprawnie i odpowiemy po sprawdzeniu dostępności oraz ceny pobytu.

SZCZEGÓŁY ZAPYTANIA
Numer zapytania: #{{inquiry_id}}
Domek: {{cabin_name}}
Przyjazd: {{start_date}}
Wyjazd: {{end_date}}
Dorośli: {{adults}}
Dzieci: {{children}}

To jest automatyczne potwierdzenie otrzymania zapytania.
Samo wysłanie zapytania nie oznacza jeszcze potwierdzenia rezerwacji.

Pozdrawiamy serdecznie
{{property_name}}
TEXT,
                'is_active' => true,
                'sort_order' => 5,
            ],
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
                'automation_enabled' => false,
                'automation_subject' =>
                    'Informacje przed przyjazdem — {{property_name}}',
                'automation_reference' => 'ARRIVAL',
                'automation_offset_days' => -2,
                'automation_send_time' => '18:00',
            ],
            [
                'name' => 'Podziękowanie po pobycie',
                'template_key' => 'POST_STAY_THANK_YOU',
                'template_context' => 'RESERVATION',
                'content' => <<<'TEXT'
Dzień dobry {{guest_name}},

serdecznie dziękujemy za pobyt w Domkach Sztabinki. Mamy nadzieję, że spędzili Państwo u nas miło czas, odpoczęli i zabrali ze sobą wiele dobrych wspomnień.

Będziemy bardzo wdzięczni za informację, czy jest coś, co moglibyśmy poprawić, aby pobyt naszych gości był jeszcze bardziej komfortowy.

Jeżeli są Państwo zadowoleni z pobytu, będzie nam również bardzo miło, jeśli zechcą Państwo podzielić się pozytywną opinią. Każda taka opinia jest dla nas bardzo ważna i pomaga innym gościom w wyborze miejsca na wypoczynek.

Dziękujemy również za pozostawienie domku w porządku. Mamy nadzieję, że jeszcze kiedyś będziemy mieli przyjemność Państwa gościć.

Życzymy dużo zdrowia, szczęśliwej podróży i wszystkiego dobrego.

Pozdrawiamy serdecznie
{{property_name}}
TEXT,
                'is_active' => true,
                'sort_order' => 50,
                'automation_enabled' => false,
                'automation_subject' =>
                    'Dziękujemy za pobyt w {{property_name}}',
                'automation_reference' => 'DEPARTURE',
                'automation_offset_days' => 1,
                'automation_send_time' => '10:00',
            ],
        ];
    }

    public static function ensureDefaultTemplates(): void
    {
        self::ensureTable();

        $connection = Database::connection();

        $keyStatement = $connection->query(
            'SELECT template_key
            FROM message_templates
            WHERE template_key IS NOT NULL'
        );

        if ($keyStatement === false) {
            throw new RuntimeException(
                'Nie udało się pobrać kluczy szablonów.'
            );
        }

        $existingKeys = [];

        while (
            ($templateKey =
                $keyStatement->fetchColumn())
            !== false
        ) {
            $templateKey = trim(
                (string) $templateKey
            );

            if ($templateKey !== '') {
                $existingKeys[$templateKey] = true;
            }
        }

        $statement = $connection->prepare(
            'INSERT INTO message_templates (
                name,
                template_key,
                template_context,
                content,
                is_active,
                sort_order,
                automation_enabled,
                automation_subject,
                automation_reference,
                automation_offset_days,
                automation_send_time
            ) VALUES (
                :name,
                :template_key,
                :template_context,
                :content,
                :is_active,
                :sort_order,
                :automation_enabled,
                :automation_subject,
                :automation_reference,
                :automation_offset_days,
                :automation_send_time
            )'
        );

        foreach (self::defaultTemplates() as $template) {
            $templateKey = trim(
                (string) (
                    $template['template_key']
                    ?? ''
                )
            );

            if (
                $templateKey !== ''
                && isset($existingKeys[$templateKey])
            ) {
                continue;
            }

            $statement->execute([
                'name' => $template['name'],
                'template_key' => $templateKey !== ''
                    ? $templateKey
                    : null,
                'template_context' => $template['template_context'],
                'content' => $template['content'],
                'is_active' => $template['is_active']
                    ? 1
                    : 0,
                'sort_order' => $template['sort_order'],
                'automation_enabled' => !empty(
                    $template['automation_enabled']
                ) ? 1 : 0,
                'automation_subject' => self::nullableText(
                    isset($template['automation_subject'])
                        ? (string) $template['automation_subject']
                        : null
                ),
                'automation_reference' => self::normalizeAutomationReference(
                    (string) (
                        $template['automation_reference']
                        ?? 'ARRIVAL'
                    )
                ),
                'automation_offset_days' => self::normalizeAutomationOffset(
                    (int) (
                        $template['automation_offset_days']
                        ?? 0
                    )
                ),
                'automation_send_time' => self::normalizeAutomationTime(
                    (string) (
                        $template['automation_send_time']
                        ?? '10:00'
                    )
                ),
            ]);

            if ($templateKey !== '') {
                $existingKeys[$templateKey] = true;
            }
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
                automation_enabled,
                automation_subject,
                automation_reference,
                automation_offset_days,
                automation_send_time,
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
                automation_enabled,
                automation_subject,
                automation_reference,
                automation_offset_days,
                automation_send_time,
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
                automation_enabled,
                automation_subject,
                automation_reference,
                automation_offset_days,
                automation_send_time,
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
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function automaticReservationTemplates(): array
    {
        self::ensureTable();

        $statement = Database::connection()->query(
            'SELECT
                id,
                name,
                template_key,
                template_context,
                content,
                is_active,
                sort_order,
                automation_enabled,
                automation_subject,
                automation_reference,
                automation_offset_days,
                automation_send_time,
                created_at,
                updated_at
            FROM message_templates
            WHERE is_active = 1
            AND automation_enabled = 1
            AND template_context = "RESERVATION"
            ORDER BY
                automation_send_time ASC,
                sort_order ASC,
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
            if (is_array($row)) {
                $templates[] = self::mapRow($row);
            }
        }

        return $templates;
    }

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
                automation_enabled,
                automation_subject,
                automation_reference,
                automation_offset_days,
                automation_send_time,
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
                automation_enabled,
                automation_subject,
                automation_reference,
                automation_offset_days,
                automation_send_time,
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
     *     sort_order?: int,
     *     automation_enabled?: bool,
     *     automation_subject?: string|null,
     *     automation_reference?: string,
     *     automation_offset_days?: int,
     *     automation_send_time?: string
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
                sort_order,
                automation_enabled,
                automation_subject,
                automation_reference,
                automation_offset_days,
                automation_send_time
            ) VALUES (
                :name,
                :template_key,
                :template_context,
                :content,
                :is_active,
                :sort_order,
                :automation_enabled,
                :automation_subject,
                :automation_reference,
                :automation_offset_days,
                :automation_send_time
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
            'automation_enabled' => !empty(
                $data['automation_enabled']
            ) ? 1 : 0,
            'automation_subject' => self::nullableText(
                isset($data['automation_subject'])
                    ? (string) $data['automation_subject']
                    : null
            ),
            'automation_reference' => self::normalizeAutomationReference(
                (string) (
                    $data['automation_reference']
                    ?? 'ARRIVAL'
                )
            ),
            'automation_offset_days' => self::normalizeAutomationOffset(
                (int) (
                    $data['automation_offset_days']
                    ?? 0
                )
            ),
            'automation_send_time' => self::normalizeAutomationTime(
                (string) (
                    $data['automation_send_time']
                    ?? '10:00'
                )
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
     *     sort_order?: int,
     *     automation_enabled?: bool,
     *     automation_subject?: string|null,
     *     automation_reference?: string,
     *     automation_offset_days?: int,
     *     automation_send_time?: string
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
                sort_order = :sort_order,
                automation_enabled = :automation_enabled,
                automation_subject = :automation_subject,
                automation_reference = :automation_reference,
                automation_offset_days = :automation_offset_days,
                automation_send_time = :automation_send_time
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
            'automation_enabled' => !empty(
                $data['automation_enabled']
            ) ? 1 : 0,
            'automation_subject' => self::nullableText(
                isset($data['automation_subject'])
                    ? (string) $data['automation_subject']
                    : null
            ),
            'automation_reference' => self::normalizeAutomationReference(
                (string) (
                    $data['automation_reference']
                    ?? 'ARRIVAL'
                )
            ),
            'automation_offset_days' => self::normalizeAutomationOffset(
                (int) (
                    $data['automation_offset_days']
                    ?? 0
                )
            ),
            'automation_send_time' => self::normalizeAutomationTime(
                (string) (
                    $data['automation_send_time']
                    ?? '10:00'
                )
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
        if (self::$structureEnsured) {
            return;
        }

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
                automation_enabled TINYINT(1) NOT NULL DEFAULT 0,
                automation_subject VARCHAR(255) NULL,
                automation_reference VARCHAR(20) NOT NULL DEFAULT "ARRIVAL",
                automation_offset_days SMALLINT NOT NULL DEFAULT 0,
                automation_send_time TIME NOT NULL DEFAULT "10:00:00",
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

        self::ensureColumn(
            'automation_enabled',
            'TINYINT(1) NOT NULL DEFAULT 0 AFTER sort_order'
        );
        self::ensureColumn(
            'automation_subject',
            'VARCHAR(255) NULL AFTER automation_enabled'
        );
        self::ensureColumn(
            'automation_reference',
            'VARCHAR(20) NOT NULL DEFAULT "ARRIVAL" AFTER automation_subject'
        );
        self::ensureColumn(
            'automation_offset_days',
            'SMALLINT NOT NULL DEFAULT 0 AFTER automation_reference'
        );
        self::ensureColumn(
            'automation_send_time',
            'TIME NOT NULL DEFAULT "10:00:00" AFTER automation_offset_days'
        );

        self::$structureEnsured = true;
    }

    private static function ensureColumn(
        string $columnName,
        string $definition
    ): void {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = "message_templates"
            AND COLUMN_NAME = :column_name'
        );

        $statement->execute([
            'column_name' => $columnName,
        ]);

        if ((int) $statement->fetchColumn() > 0) {
            return;
        }

        $allowedColumns = [
            'automation_enabled',
            'automation_subject',
            'automation_reference',
            'automation_offset_days',
            'automation_send_time',
        ];

        if (!in_array($columnName, $allowedColumns, true)) {
            throw new InvalidArgumentException(
                'Nieprawidłowa kolumna automatyzacji.'
            );
        }

        Database::connection()->exec(
            'ALTER TABLE message_templates ADD COLUMN '
            . $columnName
            . ' '
            . $definition
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
            'automation_enabled' => (int) (
                $row['automation_enabled']
                ?? 0
            ) === 1,
            'automation_subject' => self::nullableText(
                isset($row['automation_subject'])
                    ? (string) $row['automation_subject']
                    : null
            ),
            'automation_reference' => self::normalizeAutomationReference(
                (string) (
                    $row['automation_reference']
                    ?? 'ARRIVAL'
                )
            ),
            'automation_offset_days' => self::normalizeAutomationOffset(
                (int) (
                    $row['automation_offset_days']
                    ?? 0
                )
            ),
            'automation_send_time' => substr(
                self::normalizeAutomationTime(
                    (string) (
                        $row['automation_send_time']
                        ?? '10:00'
                    )
                ),
                0,
                5
            ),
            'created_at' => isset($row['created_at'])
                ? (string) $row['created_at']
                : null,
            'updated_at' => isset($row['updated_at'])
                ? (string) $row['updated_at']
                : null,
        ];
    }

    private static function normalizeAutomationReference(
        string $reference
    ): string {
        $reference = strtoupper(trim($reference));

        return in_array(
            $reference,
            ['ARRIVAL', 'DEPARTURE'],
            true
        )
            ? $reference
            : 'ARRIVAL';
    }

    private static function normalizeAutomationOffset(
        int $offsetDays
    ): int {
        return max(-365, min(365, $offsetDays));
    }

    private static function normalizeAutomationTime(
        string $time
    ): string {
        $time = trim($time);

        if (
            preg_match(
                '/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
                $time
            ) !== 1
        ) {
            return '10:00:00';
        }

        return strlen($time) === 5
            ? $time . ':00'
            : $time;
    }

    private static function nullableText(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }

}
