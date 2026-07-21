<?php

declare(strict_types=1);

final class InvoiceReminderService
{
    private static bool $structureEnsured = false;

    /**
     * @return array{found: int, sent: int, recipient: string|null}
     */
    public static function sendForDate(
        string $date
    ): array {
        self::validateDate($date);

        InvoiceRepository::ensureStructure();
        self::ensureStructure();

        $reservations =
            ReservationRepository::departuresWithoutInvoiceForDate(
                $date
            );

        if ($reservations === []) {
            return [
                'found' => 0,
                'sent' => 0,
                'recipient' => null,
            ];
        }

        $reservationIds = array_values(
            array_filter(
                array_map(
                    static fn (array $reservation): int =>
                        (int) (
                            $reservation['id']
                            ?? 0
                        ),
                    $reservations
                ),
                static fn (int $id): bool => $id > 0
            )
        );

        $alreadyNotified =
            self::notifiedReservationIds(
                $date,
                $reservationIds
            );

        $pendingReservations = array_values(
            array_filter(
                $reservations,
                static function (
                    array $reservation
                ) use ($alreadyNotified): bool {
                    $id = (int) (
                        $reservation['id']
                        ?? 0
                    );

                    return $id > 0
                        && !isset(
                            $alreadyNotified[$id]
                        );
                }
            )
        );

        if ($pendingReservations === []) {
            return [
                'found' => count($reservations),
                'sent' => 0,
                'recipient' => null,
            ];
        }

        $recipient = self::adminRecipient();

        if ($recipient === null) {
            throw new RuntimeException(
                'Nie znaleziono prawidłowego adresu e-mail '
                . 'administratora do wysłania przypomnienia.'
            );
        }

        if (!Mailer::isEnabled()) {
            throw new RuntimeException(
                'Wysyłka e-mail jest wyłączona. '
                . 'Ustaw MAIL_ENABLED=true w pliku .env.'
            );
        }

        $count = count(
            $pendingReservations
        );

        $subject = sprintf(
            'Faktury do wystawienia - %s (%d)',
            self::formatDate($date),
            $count
        );

        $body = self::buildBody(
            $date,
            $pendingReservations
        );

        $sent = Mailer::sendSafely(
            $recipient,
            $subject,
            $body
        );

        if (!$sent) {
            throw new RuntimeException(
                'Nie udało się wysłać przypomnienia e-mail '
                . 'o fakturach do wystawienia.'
            );
        }

        self::markAsNotified(
            $date,
            $recipient,
            $pendingReservations
        );

        return [
            'found' => count($reservations),
            'sent' => $count,
            'recipient' => $recipient,
        ];
    }

    private static function ensureStructure(): void
    {
        if (self::$structureEnsured) {
            return;
        }

        Database::connection()->exec(
            'CREATE TABLE IF NOT EXISTS invoice_reminder_notifications (
                reservation_id INT UNSIGNED NOT NULL,
                reminder_date DATE NOT NULL,
                recipient VARCHAR(190) NOT NULL,
                sent_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (
                    reservation_id,
                    reminder_date
                ),

                INDEX invoice_reminder_date_index (
                    reminder_date
                ),

                CONSTRAINT invoice_reminder_reservation_foreign
                    FOREIGN KEY (reservation_id)
                    REFERENCES reservations(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );

        self::ensureCompositePrimaryKey();

        self::$structureEnsured = true;
    }

    private static function ensureCompositePrimaryKey(): void
    {
        $statement = Database::connection()->query(
            "SELECT COLUMN_NAME, SEQ_IN_INDEX "
            . "FROM information_schema.STATISTICS "
            . "WHERE TABLE_SCHEMA = DATABASE() "
            . "AND TABLE_NAME = 'invoice_reminder_notifications' "
            . "AND INDEX_NAME = 'PRIMARY' "
            . "ORDER BY SEQ_IN_INDEX ASC"
        );

        if ($statement === false) {
            throw new RuntimeException(
                'Nie udało się sprawdzić klucza głównego ' .
                'tabeli przypomnień o fakturach.'
            );
        }

        $rows = $statement->fetchAll();
        $primaryColumns = [];

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $columnName = (string) (
                    $row['COLUMN_NAME']
                    ?? $row['Column_name']
                    ?? ''
                );

                if ($columnName !== '') {
                    $primaryColumns[] = $columnName;
                }
            }
        }

        if (
            $primaryColumns === [
                'reservation_id',
                'reminder_date',
            ]
        ) {
            return;
        }

        if (
            $primaryColumns !== [
                'reservation_id',
            ]
        ) {
            throw new RuntimeException(
                'Nieoczekiwany klucz główny tabeli ' .
                'invoice_reminder_notifications.'
            );
        }

        Database::connection()->exec(
            'ALTER TABLE invoice_reminder_notifications
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (
                reservation_id,
                reminder_date
            )'
        );
    }

    /**
     * @param array<int, int> $reservationIds
     * @return array<int, true>
     */
    private static function notifiedReservationIds(
        string $date,
        array $reservationIds
    ): array {
        if ($reservationIds === []) {
            return [];
        }

        $placeholders = implode(
            ', ',
            array_fill(
                0,
                count($reservationIds),
                '?'
            )
        );

        $statement = Database::connection()->prepare(
            'SELECT reservation_id
            FROM invoice_reminder_notifications
            WHERE reminder_date = ?
            AND reservation_id IN ('
            . $placeholders
            . ')'
        );

        $statement->execute(
            array_merge(
                [
                    $date,
                ],
                $reservationIds
            )
        );

        $rows = $statement->fetchAll();
        $notified = [];

        if (!is_array($rows)) {
            return $notified;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = (int) (
                $row['reservation_id']
                ?? 0
            );

            if ($id > 0) {
                $notified[$id] = true;
            }
        }

        return $notified;
    }

    /**
     * @param array<int, array<string, mixed>> $reservations
     */
    private static function markAsNotified(
        string $date,
        string $recipient,
        array $reservations
    ): void {
        $statement = Database::connection()->prepare(
            'INSERT IGNORE INTO invoice_reminder_notifications (
                reservation_id,
                reminder_date,
                recipient
            ) VALUES (
                :reservation_id,
                :reminder_date,
                :recipient
            )'
        );

        foreach ($reservations as $reservation) {
            $reservationId = (int) (
                $reservation['id']
                ?? 0
            );

            if ($reservationId < 1) {
                continue;
            }

            $statement->execute([
                'reservation_id' =>
                    $reservationId,
                'reminder_date' =>
                    $date,
                'recipient' =>
                    $recipient,
            ]);
        }
    }

    private static function adminRecipient(): ?string
    {
        try {
            $settings = SettingsRepository::all();
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się pobrać ustawień '
                . 'dla przypomnienia o fakturach: '
                . $exception->getMessage()
            );

            $settings = [];
        }

        $contactEmail = trim(
            (string) (
                $settings['contact_email']
                ?? ''
            )
        );

        if (
            filter_var(
                $contactEmail,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return $contactEmail;
        }

        $adminEmail = trim(
            (string) Env::get(
                'ADMIN_EMAIL',
                ''
            )
        );

        if (
            filter_var(
                $adminEmail,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return $adminEmail;
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $reservations
     */
    private static function buildBody(
        string $date,
        array $reservations
    ): string {
        $lines = [
            'Przypomnienie o fakturach do wystawienia.',
            '',
            'Dzisiaj ('
                . self::formatDate($date)
                . ') wypada wymeldowanie gości z poniższych rezerwacji, '
                . 'dla których nie ma jeszcze wystawionej faktury:',
            '',
        ];

        $baseUrl = rtrim(
            trim(
                (string) Env::get(
                    'APP_URL',
                    ''
                )
            ),
            '/'
        );

        foreach ($reservations as $reservation) {
            $id = (int) (
                $reservation['id']
                ?? 0
            );

            $guestName = trim(
                (string) (
                    $reservation['guest_name']
                    ?? ''
                )
            );

            if ($guestName === '') {
                $guestName = 'Gość';
            }

            $cabinName = trim(
                (string) (
                    $reservation['cabin_name']
                    ?? ''
                )
            );

            if ($cabinName === '') {
                $cabinName = 'Brak nazwy domku';
            }

            $totalPrice = (float) (
                $reservation['total_price']
                ?? 0
            );

            $lines[] = sprintf(
                '- Rezerwacja #%d | %s | %s | %.2f zł',
                $id,
                $guestName,
                $cabinName,
                $totalPrice
            );

            if (
                $baseUrl !== ''
                && $id > 0
            ) {
                $lines[] = $baseUrl
                    . '/admin/rezerwacje/pokaz?id='
                    . $id;
            }
        }

        $lines[] = '';
        $lines[] = 'Faktury możesz sprawdzić w panelu administratora:';

        if ($baseUrl !== '') {
            $lines[] = $baseUrl
                . '/admin/faktury';
        } else {
            $lines[] = '/admin/faktury';
        }

        return implode(
            "\n",
            $lines
        );
    }

    private static function validateDate(
        string $date
    ): void {
        $dateObject = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        if (
            $dateObject === false
            || $dateObject->format('Y-m-d') !== $date
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowa data przypomnienia.'
            );
        }
    }

    private static function formatDate(
        string $date
    ): string {
        $dateObject = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        return $dateObject === false
            ? $date
            : $dateObject->format('d.m.Y');
    }
}