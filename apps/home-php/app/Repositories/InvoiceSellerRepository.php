<?php

declare(strict_types=1);

final class InvoiceSellerRepository
{
    private static bool $structureEnsured = false;

    public static function ensureStructure(): void
    {
        if (self::$structureEnsured) {
            return;
        }

        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS invoice_sellers (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,

                name VARCHAR(190) NOT NULL,

                tax_id_type VARCHAR(20)
                    NOT NULL DEFAULT "NIP",
                tax_id VARCHAR(40) NULL,

                street VARCHAR(190) NULL,
                postal_code VARCHAR(40) NULL,
                city VARCHAR(120) NULL,
                country VARCHAR(120)
                    NOT NULL DEFAULT "Polska",

                email VARCHAR(190) NULL,
                phone VARCHAR(60) NULL,

                bank_account_holder VARCHAR(190) NULL,
                bank_account_number VARCHAR(80) NULL,

                invoice_series VARCHAR(40)
                    NOT NULL DEFAULT "FV",

                is_active TINYINT(1)
                    NOT NULL DEFAULT 1,

                created_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                PRIMARY KEY (id),

                INDEX invoice_sellers_active_index (
                    is_active
                )
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );

        self::ensureCabinSellerColumn(
            $connection
        );

        self::$structureEnsured = true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(
        bool $activeOnly = false
    ): array {
        self::ensureStructure();

        $connection = Database::connection();

        $sql = '
            SELECT
                invoice_sellers.id,
                invoice_sellers.name,
                invoice_sellers.tax_id_type,
                invoice_sellers.tax_id,
                invoice_sellers.street,
                invoice_sellers.postal_code,
                invoice_sellers.city,
                invoice_sellers.country,
                invoice_sellers.email,
                invoice_sellers.phone,
                invoice_sellers.bank_account_holder,
                invoice_sellers.bank_account_number,
                invoice_sellers.invoice_series,
                invoice_sellers.is_active,
                invoice_sellers.created_at,
                invoice_sellers.updated_at,
                COUNT(cabins.id) AS cabins_count
            FROM invoice_sellers
            LEFT JOIN cabins
                ON cabins.invoice_seller_id =
                    invoice_sellers.id
        ';

        if ($activeOnly) {
            $sql .= '
                WHERE invoice_sellers.is_active = 1
            ';
        }

        $sql .= '
            GROUP BY
                invoice_sellers.id,
                invoice_sellers.name,
                invoice_sellers.tax_id_type,
                invoice_sellers.tax_id,
                invoice_sellers.street,
                invoice_sellers.postal_code,
                invoice_sellers.city,
                invoice_sellers.country,
                invoice_sellers.email,
                invoice_sellers.phone,
                invoice_sellers.bank_account_holder,
                invoice_sellers.bank_account_number,
                invoice_sellers.invoice_series,
                invoice_sellers.is_active,
                invoice_sellers.created_at,
                invoice_sellers.updated_at
            ORDER BY
                invoice_sellers.is_active DESC,
                invoice_sellers.name ASC,
                invoice_sellers.id ASC
        ';

        $statement = $connection->query(
            $sql
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array($rows)
            ? $rows
            : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(
        int $id
    ): ?array {
        if ($id < 1) {
            return null;
        }

        self::ensureStructure();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                invoice_sellers.id,
                invoice_sellers.name,
                invoice_sellers.tax_id_type,
                invoice_sellers.tax_id,
                invoice_sellers.street,
                invoice_sellers.postal_code,
                invoice_sellers.city,
                invoice_sellers.country,
                invoice_sellers.email,
                invoice_sellers.phone,
                invoice_sellers.bank_account_holder,
                invoice_sellers.bank_account_number,
                invoice_sellers.invoice_series,
                invoice_sellers.is_active,
                invoice_sellers.created_at,
                invoice_sellers.updated_at,
                COUNT(cabins.id) AS cabins_count
            FROM invoice_sellers
            LEFT JOIN cabins
                ON cabins.invoice_seller_id =
                    invoice_sellers.id
            WHERE invoice_sellers.id = :id
            GROUP BY
                invoice_sellers.id,
                invoice_sellers.name,
                invoice_sellers.tax_id_type,
                invoice_sellers.tax_id,
                invoice_sellers.street,
                invoice_sellers.postal_code,
                invoice_sellers.city,
                invoice_sellers.country,
                invoice_sellers.email,
                invoice_sellers.phone,
                invoice_sellers.bank_account_holder,
                invoice_sellers.bank_account_number,
                invoice_sellers.invoice_series,
                invoice_sellers.is_active,
                invoice_sellers.created_at,
                invoice_sellers.updated_at
            LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $row = $statement->fetch();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(
        array $data
    ): int {
        self::ensureStructure();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'INSERT INTO invoice_sellers (
                name,
                tax_id_type,
                tax_id,
                street,
                postal_code,
                city,
                country,
                email,
                phone,
                bank_account_holder,
                bank_account_number,
                invoice_series,
                is_active
            ) VALUES (
                :name,
                :tax_id_type,
                :tax_id,
                :street,
                :postal_code,
                :city,
                :country,
                :email,
                :phone,
                :bank_account_holder,
                :bank_account_number,
                :invoice_series,
                :is_active
            )'
        );

        $statement->execute(
            self::normalizeData(
                $data
            )
        );

        return (int) $connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(
        int $id,
        array $data
    ): void {
        if ($id < 1) {
            throw new InvalidArgumentException(
                'Nieprawidłowy identyfikator sprzedawcy.'
            );
        }

        self::ensureStructure();

        if (self::find($id) === null) {
            throw new RuntimeException(
                'Nie znaleziono sprzedawcy faktury.'
            );
        }

        $connection = Database::connection();

        $normalized = self::normalizeData(
            $data
        );

        $normalized['id'] = $id;

        $statement = $connection->prepare(
            'UPDATE invoice_sellers
            SET
                name = :name,
                tax_id_type = :tax_id_type,
                tax_id = :tax_id,
                street = :street,
                postal_code = :postal_code,
                city = :city,
                country = :country,
                email = :email,
                phone = :phone,
                bank_account_holder =
                    :bank_account_holder,
                bank_account_number =
                    :bank_account_number,
                invoice_series =
                    :invoice_series,
                is_active = :is_active
            WHERE id = :id'
        );

        $statement->execute(
            $normalized
        );
    }

    public static function setActive(
        int $id,
        bool $isActive
    ): void {
        if ($id < 1) {
            throw new InvalidArgumentException(
                'Nieprawidłowy identyfikator sprzedawcy.'
            );
        }

        self::ensureStructure();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE invoice_sellers
            SET is_active = :is_active
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'is_active' =>
                $isActive
                    ? 1
                    : 0,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function cabinsForSeller(
        int $sellerId
    ): array {
        if ($sellerId < 1) {
            return [];
        }

        self::ensureStructure();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                name,
                short_name,
                is_active
            FROM cabins
            WHERE invoice_seller_id =
                :invoice_seller_id
            ORDER BY
                sort_order ASC,
                name ASC,
                id ASC'
        );

        $statement->execute([
            'invoice_seller_id' =>
                $sellerId,
        ]);

        $rows = $statement->fetchAll();

        return is_array($rows)
            ? $rows
            : [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function normalizeData(
        array $data
    ): array {
        $name = self::requiredText(
            $data['name'] ?? null,
            'Nazwa sprzedawcy'
        );

        $taxIdType = strtoupper(
            trim(
                (string) (
                    $data['tax_id_type']
                    ?? 'NIP'
                )
            )
        );

        if (
            !in_array(
                $taxIdType,
                [
                    'NIP',
                    'VAT_EU',
                    'OTHER',
                    'NONE',
                ],
                true
            )
        ) {
            $taxIdType = 'NIP';
        }

        $invoiceSeries = strtoupper(
            trim(
                (string) (
                    $data['invoice_series']
                    ?? 'FV'
                )
            )
        );

        $invoiceSeries = preg_replace(
            '/[^A-Z0-9_-]/',
            '',
            $invoiceSeries
        );

        if (
            !is_string($invoiceSeries)
            || $invoiceSeries === ''
        ) {
            $invoiceSeries = 'FV';
        }

        $invoiceSeries = substr(
            $invoiceSeries,
            0,
            40
        );

        $country = self::nullableText(
            $data['country']
            ?? null
        );

        if ($country === null) {
            $country = 'Polska';
        }

        return [
            'name' =>
                $name,

            'tax_id_type' =>
                $taxIdType,

            'tax_id' =>
                self::nullableText(
                    $data['tax_id']
                    ?? null
                ),

            'street' =>
                self::nullableText(
                    $data['street']
                    ?? null
                ),

            'postal_code' =>
                self::nullableText(
                    $data['postal_code']
                    ?? null
                ),

            'city' =>
                self::nullableText(
                    $data['city']
                    ?? null
                ),

            'country' =>
                $country,

            'email' =>
                self::nullableText(
                    $data['email']
                    ?? null
                ),

            'phone' =>
                self::nullableText(
                    $data['phone']
                    ?? null
                ),

            'bank_account_holder' =>
                self::nullableText(
                    $data[
                        'bank_account_holder'
                    ]
                    ?? null
                ),

            'bank_account_number' =>
                self::nullableText(
                    $data[
                        'bank_account_number'
                    ]
                    ?? null
                ),

            'invoice_series' =>
                $invoiceSeries,

            'is_active' =>
                self::normalizeBoolean(
                    $data['is_active']
                    ?? true
                ),
        ];
    }

    private static function ensureCabinSellerColumn(
        PDO $connection
    ): void {
        $columnStatement =
            $connection->query(
                'SHOW COLUMNS
                FROM cabins
                LIKE "invoice_seller_id"'
            );

        $columnExists =
            $columnStatement !== false
            && $columnStatement->fetch() !== false;

        if (!$columnExists) {
            $connection->exec(
                'ALTER TABLE cabins
                ADD COLUMN invoice_seller_id
                    INT UNSIGNED NULL
                AFTER id'
            );
        }

        $indexStatement =
            $connection->query(
                'SHOW INDEX
                FROM cabins
                WHERE Key_name =
                    "cabins_invoice_seller_index"'
            );

        $indexExists =
            $indexStatement !== false
            && $indexStatement->fetch() !== false;

        if (!$indexExists) {
            $connection->exec(
                'ALTER TABLE cabins
                ADD INDEX
                    cabins_invoice_seller_index (
                        invoice_seller_id
                    )'
            );
        }

        $constraintStatement =
            $connection->query(
                'SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = "cabins"
                AND CONSTRAINT_TYPE =
                    "FOREIGN KEY"
                AND CONSTRAINT_NAME =
                    "cabins_invoice_seller_foreign"
                LIMIT 1'
            );

        $constraintExists =
            $constraintStatement !== false
            && $constraintStatement->fetch() !== false;

        if (!$constraintExists) {
            $connection->exec(
                'ALTER TABLE cabins
                ADD CONSTRAINT
                    cabins_invoice_seller_foreign
                FOREIGN KEY (
                    invoice_seller_id
                )
                REFERENCES invoice_sellers(id)
                ON DELETE SET NULL'
            );
        }
    }

    private static function requiredText(
        mixed $value,
        string $label
    ): string {
        $value = is_string($value)
            ? trim($value)
            : '';

        if ($value === '') {
            throw new InvalidArgumentException(
                $label . ' jest wymagana.'
            );
        }

        return $value;
    }

    private static function nullableText(
        mixed $value
    ): ?string {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }

    private static function normalizeBoolean(
        mixed $value
    ): int {
        if (is_bool($value)) {
            return $value
                ? 1
                : 0;
        }

        if (is_int($value)) {
            return $value === 1
                ? 1
                : 0;
        }

        $value = strtolower(
            trim(
                (string) $value
            )
        );

        return in_array(
            $value,
            [
                '1',
                'true',
                'yes',
                'on',
                'tak',
            ],
            true
        )
            ? 1
            : 0;
    }
}