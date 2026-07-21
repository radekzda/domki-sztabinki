<?php

declare(strict_types=1);

final class InvoiceRepository
{
    private static bool $structureEnsured = false;

    public static function ensureStructure(): void
    {
        if (self::$structureEnsured) {
            return;
        }

        InvoiceSellerRepository::ensureStructure();

        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS invoice_sequences (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,

                seller_id INT UNSIGNED NOT NULL,

                series VARCHAR(40)
                    NOT NULL DEFAULT "FV",

                sequence_year
                    SMALLINT UNSIGNED NOT NULL,

                sequence_month
                    TINYINT UNSIGNED NOT NULL,

                last_number
                    INT UNSIGNED NOT NULL DEFAULT 0,

                created_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                PRIMARY KEY (id),

                UNIQUE KEY
                    invoice_sequences_seller_period_unique (
                        seller_id,
                        series,
                        sequence_year,
                        sequence_month
                    ),

                INDEX invoice_sequences_seller_index (
                    seller_id
                ),

                CONSTRAINT
                    invoice_sequences_seller_foreign
                    FOREIGN KEY (seller_id)
                    REFERENCES invoice_sellers(id)
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS invoices (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,

                reservation_id INT UNSIGNED NULL,
                seller_id INT UNSIGNED NOT NULL,

                invoice_number VARCHAR(100) NOT NULL,

                series VARCHAR(40)
                    NOT NULL DEFAULT "FV",

                sequence_year
                    SMALLINT UNSIGNED NOT NULL,

                sequence_month
                    TINYINT UNSIGNED NOT NULL,

                sequence_number
                    INT UNSIGNED NOT NULL,

                issue_date DATE NOT NULL,
                sale_date DATE NOT NULL,
                due_date DATE NULL,

                status VARCHAR(30)
                    NOT NULL DEFAULT "DRAFT",

                currency CHAR(3)
                    NOT NULL DEFAULT "PLN",

                payment_method VARCHAR(30) NULL,

                payment_status VARCHAR(30)
                    NOT NULL DEFAULT "UNPAID",

                paid_amount DECIMAL(12, 2)
                    NOT NULL DEFAULT 0.00,

                seller_name VARCHAR(190) NOT NULL,

                seller_tax_id_type VARCHAR(20)
                    NOT NULL DEFAULT "NIP",

                seller_tax_id VARCHAR(40) NULL,

                seller_street VARCHAR(190) NULL,
                seller_postal_code VARCHAR(40) NULL,
                seller_city VARCHAR(120) NULL,

                seller_country VARCHAR(120)
                    NOT NULL DEFAULT "Polska",

                seller_email VARCHAR(190) NULL,
                seller_phone VARCHAR(60) NULL,

                seller_bank_account_holder
                    VARCHAR(190) NULL,

                seller_bank_account_number
                    VARCHAR(80) NULL,

                buyer_type VARCHAR(20)
                    NOT NULL DEFAULT "PERSON",

                buyer_name VARCHAR(190) NOT NULL,

                buyer_tax_id_type VARCHAR(20)
                    NOT NULL DEFAULT "NONE",

                buyer_tax_id VARCHAR(40) NULL,

                buyer_street VARCHAR(190) NULL,
                buyer_postal_code VARCHAR(40) NULL,
                buyer_city VARCHAR(120) NULL,
                buyer_country VARCHAR(120) NULL,
                buyer_email VARCHAR(190) NULL,

                net_total DECIMAL(12, 2)
                    NOT NULL DEFAULT 0.00,

                vat_total DECIMAL(12, 2)
                    NOT NULL DEFAULT 0.00,

                gross_total DECIMAL(12, 2)
                    NOT NULL DEFAULT 0.00,

                tax_exemption_basis
                    VARCHAR(255) NULL,

                notes TEXT NULL,

                ksef_status VARCHAR(30) NULL,
                ksef_number VARCHAR(120) NULL,
                ksef_sent_at DATETIME NULL,

                created_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                PRIMARY KEY (id),

                UNIQUE KEY
                    invoices_seller_number_unique (
                        seller_id,
                        invoice_number
                    ),

                UNIQUE KEY invoices_sequence_unique (
                    seller_id,
                    series,
                    sequence_year,
                    sequence_month,
                    sequence_number
                ),

                INDEX invoices_reservation_index (
                    reservation_id
                ),

                INDEX invoices_seller_index (
                    seller_id
                ),

                INDEX invoices_issue_date_index (
                    issue_date
                ),

                INDEX invoices_status_index (
                    status
                ),

                INDEX invoices_buyer_tax_id_index (
                    buyer_tax_id
                ),

                INDEX invoices_ksef_status_index (
                    ksef_status
                ),

                CONSTRAINT
                    invoices_reservation_foreign
                    FOREIGN KEY (reservation_id)
                    REFERENCES reservations(id)
                    ON DELETE SET NULL,

                CONSTRAINT invoices_seller_foreign
                    FOREIGN KEY (seller_id)
                    REFERENCES invoice_sellers(id)
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );

        self::ensureInvoicePaidAmountColumn(
            $connection
        );

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS invoice_items (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,

                invoice_id INT UNSIGNED NOT NULL,

                name VARCHAR(255) NOT NULL,

                quantity DECIMAL(12, 3)
                    NOT NULL DEFAULT 1.000,

                unit VARCHAR(30)
                    NOT NULL DEFAULT "usł.",

                unit_net DECIMAL(12, 2)
                    NOT NULL DEFAULT 0.00,

                vat_rate_code VARCHAR(20)
                    NOT NULL DEFAULT "NP",

                net_amount DECIMAL(12, 2)
                    NOT NULL DEFAULT 0.00,

                vat_amount DECIMAL(12, 2)
                    NOT NULL DEFAULT 0.00,

                gross_amount DECIMAL(12, 2)
                    NOT NULL DEFAULT 0.00,

                sort_order INT NOT NULL DEFAULT 0,

                created_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id),

                INDEX invoice_items_invoice_index (
                    invoice_id
                ),

                CONSTRAINT invoice_items_invoice_foreign
                    FOREIGN KEY (invoice_id)
                    REFERENCES invoices(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );

        self::$structureEnsured = true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        self::ensureStructure();

        $statement = Database::connection()->query(
            'SELECT
                invoices.*,
                reservations.guest_name,
                cabins.name AS cabin_name
            FROM invoices
            LEFT JOIN reservations
                ON reservations.id =
                    invoices.reservation_id
            LEFT JOIN cabins
                ON cabins.id =
                    reservations.cabin_id
            ORDER BY
                invoices.issue_date DESC,
                invoices.id DESC'
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

        $statement = Database::connection()->prepare(
            'SELECT
                invoices.*,
                reservations.guest_name,
                reservations.cabin_id,
                cabins.name AS cabin_name
            FROM invoices
            LEFT JOIN reservations
                ON reservations.id =
                    invoices.reservation_id
            LEFT JOIN cabins
                ON cabins.id =
                    reservations.cabin_id
            WHERE invoices.id = :id
            LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $invoice = $statement->fetch();

        if (!is_array($invoice)) {
            return null;
        }

        $invoice['items'] =
            self::itemsForInvoice($id);

        return $invoice;
    }

    public static function lastSequenceNumber(
        int $sellerId,
        string $series,
        string $issueDate
    ): int {
        if ($sellerId < 1) {
            return 0;
        }

        self::ensureStructure();

        $normalizedSeries =
            self::normalizeSeries(
                $series
            );

        $normalizedDate =
            self::normalizeDate(
                $issueDate,
                'Data wystawienia'
            );

        $year = (int) substr(
            $normalizedDate,
            0,
            4
        );

        $month = (int) substr(
            $normalizedDate,
            5,
            2
        );

        $connection = Database::connection();

        $sequenceStatement =
            $connection->prepare(
                'SELECT last_number
                FROM invoice_sequences
                WHERE seller_id = :seller_id
                AND series = :series
                AND sequence_year = :sequence_year
                AND sequence_month = :sequence_month
                LIMIT 1'
            );

        $sequenceStatement->execute([
            'seller_id' =>
                $sellerId,
            'series' =>
                $normalizedSeries,
            'sequence_year' =>
                $year,
            'sequence_month' =>
                $month,
        ]);

        $sequenceLast = (int) (
            $sequenceStatement->fetchColumn()
            ?: 0
        );

        $invoiceStatement =
            $connection->prepare(
                'SELECT COALESCE(
                    MAX(sequence_number),
                    0
                )
                FROM invoices
                WHERE seller_id = :seller_id
                AND series = :series
                AND sequence_year = :sequence_year
                AND sequence_month = :sequence_month'
            );

        $invoiceStatement->execute([
            'seller_id' =>
                $sellerId,
            'series' =>
                $normalizedSeries,
            'sequence_year' =>
                $year,
            'sequence_month' =>
                $month,
        ]);

        $invoiceLast = (int) (
            $invoiceStatement->fetchColumn()
            ?: 0
        );

        return max(
            0,
            $sequenceLast,
            $invoiceLast
        );
    }
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forReservation(
        int $reservationId
    ): array {
        if ($reservationId < 1) {
            return [];
        }

        self::ensureStructure();

        $statement = Database::connection()->prepare(
            'SELECT *
            FROM invoices
            WHERE reservation_id = :reservation_id
            ORDER BY
                issue_date DESC,
                id DESC'
        );

        $statement->execute([
            'reservation_id' =>
                $reservationId,
        ]);

        $rows = $statement->fetchAll();

        return is_array($rows)
            ? $rows
            : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function firstForReservation(
        int $reservationId
    ): ?array {
        $invoices = self::forReservation(
            $reservationId
        );

        $invoice = $invoices[0] ?? null;

        return is_array($invoice)
            ? $invoice
            : null;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     */
    public static function create(
        array $data,
        array $items
    ): int {
        self::ensureStructure();

        if ($items === []) {
            throw new InvalidArgumentException(
                'Faktura musi zawierać '
                . 'co najmniej jedną pozycję.'
            );
        }

        $sellerId = (int) (
            $data['seller_id']
            ?? 0
        );

        if ($sellerId < 1) {
            throw new InvalidArgumentException(
                'Wybierz sprzedawcę faktury.'
            );
        }

        $seller =
            InvoiceSellerRepository::find(
                $sellerId
            );

        if ($seller === null) {
            throw new RuntimeException(
                'Nie znaleziono sprzedawcy faktury.'
            );
        }

        $issueDate = self::normalizeDate(
            $data['issue_date']
            ?? null,
            'Data wystawienia'
        );

        $saleDate = self::normalizeDate(
            $data['sale_date']
            ?? null,
            'Data sprzedaży'
        );

        $series = self::normalizeSeries(
            (string) (
                $data['series']
                ?? $seller['invoice_series']
                ?? 'FV'
            )
        );

        $year = (int) substr(
            $issueDate,
            0,
            4
        );

        $month = (int) substr(
            $issueDate,
            5,
            2
        );

        $reservationId =
            self::nullablePositiveInt(
                $data['reservation_id']
                ?? null
            );

        $connection = Database::connection();

        $connection->beginTransaction();

        try {
            if ($reservationId !== null) {
                $existingInvoiceStatement =
                    $connection->prepare(
                        'SELECT
                            id,
                            invoice_number
                        FROM invoices
                        WHERE reservation_id =
                            :reservation_id
                        ORDER BY id DESC
                        LIMIT 1
                        FOR UPDATE'
                    );

                $existingInvoiceStatement->execute([
                    'reservation_id' =>
                        $reservationId,
                ]);

                $existingInvoice =
                    $existingInvoiceStatement->fetch();

                if (is_array($existingInvoice)) {
                    $existingNumber = trim(
                        (string) (
                            $existingInvoice[
                                'invoice_number'
                            ]
                            ?? ''
                        )
                    );

                    throw new RuntimeException(
                        'Ta rezerwacja ma już '
                        . 'wystawioną fakturę'
                        . (
                            $existingNumber !== ''
                                ? ': ' . $existingNumber
                                : '.'
                        )
                    );
                }
            }

            $previousSequenceNumber =
                isset(
                    $data[
                        'previous_sequence_number'
                    ]
                )
                    ? max(
                        0,
                        (int) $data[
                            'previous_sequence_number'
                        ]
                    )
                    : null;

            $sequenceNumber =
                self::nextSequenceNumber(
                    $connection,
                    $sellerId,
                    $series,
                    $year,
                    $month,
                    $previousSequenceNumber
                );

            $invoiceNumber =
                self::formatInvoiceNumber(
                    $series,
                    $sequenceNumber,
                    $month,
                    $year
                );

            $totals =
                self::calculateTotals(
                    $items
                );

            $statement = $connection->prepare(
                'INSERT INTO invoices (
                    reservation_id,
                    seller_id,
                    invoice_number,
                    series,
                    sequence_year,
                    sequence_month,
                    sequence_number,
                    issue_date,
                    sale_date,
                    due_date,
                    status,
                    currency,
                    payment_method,
                    payment_status,
                    paid_amount,
                    seller_name,
                    seller_tax_id_type,
                    seller_tax_id,
                    seller_street,
                    seller_postal_code,
                    seller_city,
                    seller_country,
                    seller_email,
                    seller_phone,
                    seller_bank_account_holder,
                    seller_bank_account_number,
                    buyer_type,
                    buyer_name,
                    buyer_tax_id_type,
                    buyer_tax_id,
                    buyer_street,
                    buyer_postal_code,
                    buyer_city,
                    buyer_country,
                    buyer_email,
                    net_total,
                    vat_total,
                    gross_total,
                    tax_exemption_basis,
                    notes
                ) VALUES (
                    :reservation_id,
                    :seller_id,
                    :invoice_number,
                    :series,
                    :sequence_year,
                    :sequence_month,
                    :sequence_number,
                    :issue_date,
                    :sale_date,
                    :due_date,
                    :status,
                    :currency,
                    :payment_method,
                    :payment_status,
                    :paid_amount,
                    :seller_name,
                    :seller_tax_id_type,
                    :seller_tax_id,
                    :seller_street,
                    :seller_postal_code,
                    :seller_city,
                    :seller_country,
                    :seller_email,
                    :seller_phone,
                    :seller_bank_account_holder,
                    :seller_bank_account_number,
                    :buyer_type,
                    :buyer_name,
                    :buyer_tax_id_type,
                    :buyer_tax_id,
                    :buyer_street,
                    :buyer_postal_code,
                    :buyer_city,
                    :buyer_country,
                    :buyer_email,
                    :net_total,
                    :vat_total,
                    :gross_total,
                    :tax_exemption_basis,
                    :notes
                )'
            );

            $statement->execute([
                'reservation_id' =>
                    $reservationId,

                'seller_id' =>
                    $sellerId,

                'invoice_number' =>
                    $invoiceNumber,

                'series' =>
                    $series,

                'sequence_year' =>
                    $year,

                'sequence_month' =>
                    $month,

                'sequence_number' =>
                    $sequenceNumber,

                'issue_date' =>
                    $issueDate,

                'sale_date' =>
                    $saleDate,

                'due_date' =>
                    self::nullableDate(
                        $data['due_date']
                        ?? null
                    ),

                'status' =>
                    self::normalizeStatus(
                        $data['status']
                        ?? 'DRAFT'
                    ),

                'currency' =>
                    self::normalizeCurrency(
                        $data['currency']
                        ?? 'PLN'
                    ),

                'payment_method' =>
                    self::nullableText(
                        $data['payment_method']
                        ?? null
                    ),

                'payment_status' =>
                    self::normalizePaymentStatus(
                        $data['payment_status']
                        ?? 'UNPAID'
                    ),

                'paid_amount' =>
                    self::decimal(
                        max(
                            0.0,
                            self::money(
                                $data['paid_amount']
                                ?? 0
                            )
                        )
                    ),

                'seller_name' =>
                    (string) $seller['name'],

                'seller_tax_id_type' =>
                    (string) (
                        $seller['tax_id_type']
                        ?? 'NIP'
                    ),

                'seller_tax_id' =>
                    $seller['tax_id']
                    ?? null,

                'seller_street' =>
                    $seller['street']
                    ?? null,

                'seller_postal_code' =>
                    $seller['postal_code']
                    ?? null,

                'seller_city' =>
                    $seller['city']
                    ?? null,

                'seller_country' =>
                    (string) (
                        $seller['country']
                        ?? 'Polska'
                    ),

                'seller_email' =>
                    $seller['email']
                    ?? null,

                'seller_phone' =>
                    $seller['phone']
                    ?? null,

                'seller_bank_account_holder' =>
                    $seller[
                        'bank_account_holder'
                    ]
                    ?? null,

                'seller_bank_account_number' =>
                    $seller[
                        'bank_account_number'
                    ]
                    ?? null,

                'buyer_type' =>
                    self::normalizeBuyerType(
                        $data['buyer_type']
                        ?? 'PERSON'
                    ),

                'buyer_name' =>
                    self::requiredText(
                        $data['buyer_name']
                        ?? null,
                        'Nazwa nabywcy'
                    ),

                'buyer_tax_id_type' =>
                    self::normalizeBuyerTaxIdType(
                        $data['buyer_tax_id_type']
                        ?? 'NONE'
                    ),

                'buyer_tax_id' =>
                    self::nullableText(
                        $data['buyer_tax_id']
                        ?? null
                    ),

                'buyer_street' =>
                    self::nullableText(
                        $data['buyer_street']
                        ?? null
                    ),

                'buyer_postal_code' =>
                    self::nullableText(
                        $data['buyer_postal_code']
                        ?? null
                    ),

                'buyer_city' =>
                    self::nullableText(
                        $data['buyer_city']
                        ?? null
                    ),

                'buyer_country' =>
                    self::nullableText(
                        $data['buyer_country']
                        ?? null
                    ),

                'buyer_email' =>
                    self::nullableText(
                        $data['buyer_email']
                        ?? null
                    ),

                'net_total' =>
                    $totals['net_total'],

                'vat_total' =>
                    $totals['vat_total'],

                'gross_total' =>
                    $totals['gross_total'],

                'tax_exemption_basis' =>
                    self::nullableText(
                        $data[
                            'tax_exemption_basis'
                        ]
                        ?? null
                    ),

                'notes' =>
                    self::nullableText(
                        $data['notes']
                        ?? null
                    ),
            ]);

            $invoiceId =
                (int) $connection->lastInsertId();

            foreach (
                $items
                as $index => $item
            ) {
                self::insertItem(
                    $connection,
                    $invoiceId,
                    $item,
                    $index
                );
            }

            $connection->commit();

            return $invoiceId;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function delete(
        int $id
    ): void {
        if ($id < 1) {
            throw new InvalidArgumentException(
                'Nieprawidłowy identyfikator faktury.'
            );
        }

        self::ensureStructure();

        $invoice = self::find($id);

        if ($invoice === null) {
            throw new RuntimeException(
                'Nie znaleziono faktury.'
            );
        }

        $ksefNumber = trim(
            (string) (
                $invoice['ksef_number']
                ?? ''
            )
        );

        $ksefSentAt = trim(
            (string) (
                $invoice['ksef_sent_at']
                ?? ''
            )
        );

        if (
            $ksefNumber !== ''
            || $ksefSentAt !== ''
        ) {
            throw new RuntimeException(
                'Nie można usunąć faktury '
                . 'wysłanej do KSeF.'
            );
        }

        $statement =
            Database::connection()->prepare(
                'DELETE FROM invoices
                WHERE id = :id'
            );

        $statement->execute([
            'id' => $id,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new RuntimeException(
                'Nie udało się usunąć faktury.'
            );
        }
    }
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function itemsForInvoice(
        int $invoiceId
    ): array {
        if ($invoiceId < 1) {
            return [];
        }

        self::ensureStructure();

        $statement = Database::connection()->prepare(
            'SELECT *
            FROM invoice_items
            WHERE invoice_id = :invoice_id
            ORDER BY
                sort_order ASC,
                id ASC'
        );

        $statement->execute([
            'invoice_id' =>
                $invoiceId,
        ]);

        $rows = $statement->fetchAll();

        return is_array($rows)
            ? $rows
            : [];
    }

    private static function ensureInvoicePaidAmountColumn(
        PDO $connection
    ): void {
        $columnStatement =
            $connection->query(
                'SHOW COLUMNS
                FROM invoices
                LIKE "paid_amount"'
            );

        $columnExists =
            $columnStatement !== false
            && $columnStatement->fetch() !== false;

        if ($columnExists) {
            return;
        }

        $connection->exec(
            'ALTER TABLE invoices
            ADD COLUMN paid_amount
                DECIMAL(12, 2)
                NOT NULL DEFAULT 0.00
            AFTER payment_status'
        );

        $connection->exec(
            'UPDATE invoices
            LEFT JOIN reservations
                ON reservations.id =
                    invoices.reservation_id
            SET invoices.paid_amount =
                CASE
                    WHEN invoices.payment_status = "PAID"
                        THEN invoices.gross_total
                    ELSE LEAST(
                        invoices.gross_total,
                        GREATEST(
                            0.00,
                            COALESCE(
                                reservations.paid_amount,
                                0.00
                            )
                        )
                    )
                END
            WHERE invoices.reservation_id IS NOT NULL'
        );
    }

    private static function nextSequenceNumber(
        PDO $connection,
        int $sellerId,
        string $series,
        int $year,
        int $month,
        ?int $previousSequenceNumber = null
    ): int {
        $statement = $connection->prepare(
            'SELECT
                id,
                last_number
            FROM invoice_sequences
            WHERE seller_id = :seller_id
            AND series = :series
            AND sequence_year = :sequence_year
            AND sequence_month = :sequence_month
            FOR UPDATE'
        );

        $statement->execute([
            'seller_id' =>
                $sellerId,
            'series' =>
                $series,
            'sequence_year' =>
                $year,
            'sequence_month' =>
                $month,
        ]);

        $row = $statement->fetch();

        $invoiceMaxStatement =
            $connection->prepare(
                'SELECT COALESCE(
                    MAX(sequence_number),
                    0
                )
                FROM invoices
                WHERE seller_id = :seller_id
                AND series = :series
                AND sequence_year = :sequence_year
                AND sequence_month = :sequence_month'
            );

        $invoiceMaxStatement->execute([
            'seller_id' =>
                $sellerId,
            'series' =>
                $series,
            'sequence_year' =>
                $year,
            'sequence_month' =>
                $month,
        ]);

        $invoiceLast = (int) (
            $invoiceMaxStatement->fetchColumn()
            ?: 0
        );

        $sequenceLast = is_array($row)
            ? (int) (
                $row['last_number']
                ?? 0
            )
            : 0;

        $currentLast = max(
            $sequenceLast,
            $invoiceLast
        );

        $nextNumber =
            $previousSequenceNumber !== null
                ? $previousSequenceNumber + 1
                : $currentLast + 1;

        if ($nextNumber < 1) {
            throw new InvalidArgumentException(
                'Numer faktury musi być większy od zera.'
            );
        }

        $usedStatement =
            $connection->prepare(
                'SELECT COUNT(*)
                FROM invoices
                WHERE seller_id = :seller_id
                AND series = :series
                AND sequence_year = :sequence_year
                AND sequence_month = :sequence_month
                AND sequence_number = :sequence_number'
            );

        $usedStatement->execute([
            'seller_id' =>
                $sellerId,
            'series' =>
                $series,
            'sequence_year' =>
                $year,
            'sequence_month' =>
                $month,
            'sequence_number' =>
                $nextNumber,
        ]);

        if (
            (int) $usedStatement->fetchColumn()
            > 0
        ) {
            throw new InvalidArgumentException(
                'Numer faktury '
                . self::formatInvoiceNumber(
                    $series,
                    $nextNumber,
                    $month,
                    $year
                )
                . ' jest już zajęty. '
                . 'Wybierz inny numer poprzedniej faktury.'
            );
        }

        $newSequenceLast = max(
            $currentLast,
            $nextNumber
        );

        if (is_array($row)) {
            if (
                $newSequenceLast
                > $sequenceLast
            ) {
                $update = $connection->prepare(
                    'UPDATE invoice_sequences
                    SET last_number = :last_number
                    WHERE id = :id'
                );

                $update->execute([
                    'last_number' =>
                        $newSequenceLast,
                    'id' =>
                        (int) $row['id'],
                ]);
            }

            return $nextNumber;
        }

        $insert = $connection->prepare(
            'INSERT INTO invoice_sequences (
                seller_id,
                series,
                sequence_year,
                sequence_month,
                last_number
            ) VALUES (
                :seller_id,
                :series,
                :sequence_year,
                :sequence_month,
                :last_number
            )'
        );

        $insert->execute([
            'seller_id' =>
                $sellerId,
            'series' =>
                $series,
            'sequence_year' =>
                $year,
            'sequence_month' =>
                $month,
            'last_number' =>
                $newSequenceLast,
        ]);

        return $nextNumber;
    }

    private static function formatInvoiceNumber(
        string $series,
        int $number,
        int $month,
        int $year
    ): string {
        return sprintf(
            '%s/%d/%02d/%d',
            $series,
            $number,
            $month,
            $year
        );
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array{
     *     net_total: string,
     *     vat_total: string,
     *     gross_total: string
     * }
     */
    private static function calculateTotals(
        array $items
    ): array {
        $net = 0.0;
        $vat = 0.0;
        $gross = 0.0;

        foreach ($items as $item) {
            $net += self::money(
                $item['net_amount']
                ?? 0
            );

            $vat += self::money(
                $item['vat_amount']
                ?? 0
            );

            $gross += self::money(
                $item['gross_amount']
                ?? 0
            );
        }

        return [
            'net_total' =>
                number_format(
                    $net,
                    2,
                    '.',
                    ''
                ),

            'vat_total' =>
                number_format(
                    $vat,
                    2,
                    '.',
                    ''
                ),

            'gross_total' =>
                number_format(
                    $gross,
                    2,
                    '.',
                    ''
                ),
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function insertItem(
        PDO $connection,
        int $invoiceId,
        array $item,
        int $index
    ): void {
        $name = self::requiredText(
            $item['name']
            ?? null,
            'Nazwa pozycji faktury'
        );

        $statement = $connection->prepare(
            'INSERT INTO invoice_items (
                invoice_id,
                name,
                quantity,
                unit,
                unit_net,
                vat_rate_code,
                net_amount,
                vat_amount,
                gross_amount,
                sort_order
            ) VALUES (
                :invoice_id,
                :name,
                :quantity,
                :unit,
                :unit_net,
                :vat_rate_code,
                :net_amount,
                :vat_amount,
                :gross_amount,
                :sort_order
            )'
        );

        $statement->execute([
            'invoice_id' =>
                $invoiceId,

            'name' =>
                $name,

            'quantity' =>
                self::decimal(
                    $item['quantity']
                    ?? 1,
                    3
                ),

            'unit' =>
                self::nullableText(
                    $item['unit']
                    ?? null
                )
                ?? 'usł.',

            'unit_net' =>
                self::decimal(
                    $item['unit_net']
                    ?? 0
                ),

            'vat_rate_code' =>
                strtoupper(
                    self::nullableText(
                        $item['vat_rate_code']
                        ?? null
                    )
                    ?? 'NP'
                ),

            'net_amount' =>
                self::decimal(
                    $item['net_amount']
                    ?? 0
                ),

            'vat_amount' =>
                self::decimal(
                    $item['vat_amount']
                    ?? 0
                ),

            'gross_amount' =>
                self::decimal(
                    $item['gross_amount']
                    ?? 0
                ),

            'sort_order' =>
                isset($item['sort_order'])
                    ? (int) $item['sort_order']
                    : $index,
        ]);
    }

    private static function normalizeSeries(
        string $series
    ): string {
        $series = strtoupper(
            trim($series)
        );

        if (
            preg_match(
                '/^[A-Z0-9_-]{1,40}$/',
                $series
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowa seria faktury.'
            );
        }

        return $series;
    }

    private static function normalizeDate(
        mixed $value,
        string $label
    ): string {
        $date = self::nullableDate($value);

        if ($date === null) {
            throw new InvalidArgumentException(
                $label . ' jest wymagana.'
            );
        }

        return $date;
    }

    private static function nullableDate(
        mixed $value
    ): ?string {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );

        if (
            $date === false
            || $date->format('Y-m-d')
                !== $value
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowy format daty.'
            );
        }

        return $value;
    }

    private static function normalizeStatus(
        mixed $value
    ): string {
        $value = strtoupper(
            trim((string) $value)
        );

        return in_array(
            $value,
            [
                'DRAFT',
                'ISSUED',
                'CANCELLED',
            ],
            true
        )
            ? $value
            : 'DRAFT';
    }

    private static function normalizePaymentStatus(
        mixed $value
    ): string {
        $value = strtoupper(
            trim((string) $value)
        );

        return in_array(
            $value,
            [
                'UNPAID',
                'PARTIALLY_PAID',
                'PAID',
            ],
            true
        )
            ? $value
            : 'UNPAID';
    }

    private static function normalizeBuyerType(
        mixed $value
    ): string {
        $value = strtoupper(
            trim((string) $value)
        );

        return in_array(
            $value,
            [
                'PERSON',
                'COMPANY',
            ],
            true
        )
            ? $value
            : 'PERSON';
    }

    private static function normalizeBuyerTaxIdType(
        mixed $value
    ): string {
        $value = strtoupper(
            trim((string) $value)
        );

        return in_array(
            $value,
            [
                'NIP',
                'VAT_EU',
                'OTHER',
                'NONE',
            ],
            true
        )
            ? $value
            : 'NONE';
    }

    private static function normalizeCurrency(
        mixed $value
    ): string {
        $value = strtoupper(
            trim((string) $value)
        );

        if (
            preg_match(
                '/^[A-Z]{3}$/',
                $value
            ) !== 1
        ) {
            return 'PLN';
        }

        return $value;
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

    private static function nullablePositiveInt(
        mixed $value
    ): ?int {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $value = filter_var(
            $value,
            FILTER_VALIDATE_INT
        );

        return is_int($value)
            && $value > 0
                ? $value
                : null;
    }

    private static function money(
        mixed $value
    ): float {
        return round(
            (float) str_replace(
                ',',
                '.',
                (string) $value
            ),
            2
        );
    }

    private static function decimal(
        mixed $value,
        int $precision = 2
    ): string {
        return number_format(
            (float) str_replace(
                ',',
                '.',
                (string) $value
            ),
            $precision,
            '.',
            ''
        );
    }
}