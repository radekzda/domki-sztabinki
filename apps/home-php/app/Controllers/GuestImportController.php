<?php

declare(strict_types=1);

final class GuestImportController
{
    public static function show(): void
    {
        Response::html(
            View::render(
                'pages/admin_guests_import',
                [
                    'title' =>
                        'Import gości CSV',
                    'result' => null,
                    'errorMessage' => null,
                ]
            )
        );
    }

    public static function store(): void
    {
        if (
            !Database::canAttemptConnection()
        ) {
            self::renderError(
                'Baza danych nie jest jeszcze skonfigurowana.'
            );

            return;
        }

        $path = self::uploadedCsvPath();

        if ($path === null) {
            return;
        }

        try {
            $result = self::importCsv(
                Database::connection(),
                $path
            );

            Response::html(
                View::render(
                    'pages/admin_guests_import',
                    [
                        'title' =>
                            'Import gości CSV',
                        'result' => $result,
                        'errorMessage' => null,
                    ]
                )
            );
        } catch (Throwable $exception) {
            self::renderError(
                'Import przerwany: '
                . AppErrorHandler::safeMessage(
                    $exception
                )
            );
        }
    }

    private static function uploadedCsvPath(): ?string
    {
        if (
            !isset($_FILES['csv_file'])
            || !is_array(
                $_FILES['csv_file']
            )
        ) {
            self::renderError(
                'Nie wybrano pliku CSV.'
            );

            return null;
        }

        $file = $_FILES['csv_file'];

        if (
            (
                $file['error']
                ?? UPLOAD_ERR_NO_FILE
            ) !== UPLOAD_ERR_OK
        ) {
            self::renderError(
                'Nie udało się wczytać pliku CSV. Kod błędu: '
                . (string) (
                    $file['error']
                    ?? 'brak'
                )
            );

            return null;
        }

        $path = (string) (
            $file['tmp_name']
            ?? ''
        );

        if (
            $path === ''
            || !is_uploaded_file($path)
        ) {
            self::renderError(
                'Przesłany plik jest nieprawidłowy.'
            );

            return null;
        }

        return $path;
    }

    private static function renderError(
        string $message
    ): void {
        Response::html(
            View::render(
                'pages/admin_guests_import',
                [
                    'title' =>
                        'Import gości CSV',
                    'result' => null,
                    'errorMessage' => $message,
                ]
            )
        );
    }

    /**
     * @return array{
     *     inserted:int,
     *     updated:int,
     *     skipped:int,
     *     total:int
     * }
     */
    private static function importCsv(
        PDO $pdo,
        string $path
    ): array {
        $handle = fopen(
            $path,
            'rb'
        );

        if ($handle === false) {
            throw new RuntimeException(
                'Nie udało się otworzyć przesłanego pliku.'
            );
        }

        try {
            $headers =
                self::readHeaders(
                    $handle
                );

            self::requireColumns(
                $headers,
                [
                    'first_name',
                    'last_name',
                    'email',
                ]
            );

            $inserted = 0;
            $updated = 0;
            $skipped = 0;
            $rowNumber = 1;

            $pdo->beginTransaction();

            try {
                while (
                    (
                        $row = fgetcsv(
                            $handle,
                            0,
                            ';'
                        )
                    ) !== false
                ) {
                    $rowNumber++;

                    if (
                        self::rowIsEmpty(
                            $row
                        )
                    ) {
                        continue;
                    }

                    $csv = self::mapCsvRow(
                        $headers,
                        $row
                    );

                    $data = self::guestData(
                        $csv,
                        $rowNumber
                    );

                    $existing =
                        self::findExistingGuest(
                            $pdo,
                            (string) $data['email'],
                            $data['phone']
                        );

                    if ($existing !== null) {
                        self::updateGuest(
                            $pdo,
                            (int) $existing['id'],
                            $data
                        );

                        $updated++;

                        continue;
                    }

                    self::insertGuest(
                        $pdo,
                        $data
                    );

                    $inserted++;
                }

                $pdo->commit();
            } catch (Throwable $exception) {
                if (
                    $pdo->inTransaction()
                ) {
                    $pdo->rollBack();
                }

                throw new RuntimeException(
                    'Błąd w okolicy wiersza '
                    . $rowNumber
                    . ': '
                    . AppErrorHandler::safeMessage(
                        $exception
                    )
                );
            }

            return [
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => $skipped,
                'total' =>
                    $inserted
                    + $updated
                    + $skipped,
            ];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string, string> $csv
     * @return array<string, mixed>
     */
    private static function guestData(
        array $csv,
        int $rowNumber
    ): array {
        $firstName =
            self::cleanText(
                $csv['first_name']
                ?? ''
            );

        $lastName =
            self::cleanText(
                $csv['last_name']
                ?? ''
            );

        $email = strtolower(
            self::cleanText(
                $csv['email']
                ?? ''
            )
        );

        if (
            $firstName === ''
            || $lastName === ''
        ) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': imię i nazwisko są wymagane.'
            );
        }

        if (
            $email === ''
            || !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': podaj poprawny adres e-mail.'
            );
        }

        $phone =
            self::nullIfEmpty(
                self::cleanText(
                    $csv['phone']
                    ?? ''
                )
            );

        $addressForm = [
            'street' =>
                self::cleanText(
                    $csv['street']
                    ?? ''
                ),
            'postal_code' =>
                self::cleanText(
                    $csv[
                        'postal_code'
                    ]
                    ?? ''
                ),
            'city' =>
                self::cleanText(
                    $csv['city']
                    ?? ''
                ),
            'country' =>
                self::cleanText(
                    $csv['country']
                    ?? ''
                ),
            'full_address' =>
                self::cleanText(
                    $csv[
                        'full_address'
                    ]
                    ?? (
                        $csv['address']
                        ?? ''
                    )
                ),
        ];

        if (
            function_exists(
                'normalizeGuestAddressForm'
            )
        ) {
            $addressForm =
                normalizeGuestAddressForm(
                    $addressForm
                );
        }

        $birthDate =
            self::optionalDate(
                $csv['birth_date']
                ?? (
                    $csv[
                        'date_of_birth'
                    ]
                    ?? ''
                ),
                $rowNumber
            );

        $source =
            self::mapSource(
                $csv['source']
                ?? ''
            );

        $preferredContact =
            self::optionalPreferredContact(
                $csv[
                    'preferred_contact'
                ]
                ?? '',
                $rowNumber
            );

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'country' =>
                self::nullIfEmpty(
                    (string) (
                        $addressForm[
                            'country'
                        ]
                        ?? ''
                    )
                ),
            'street' =>
                self::nullIfEmpty(
                    (string) (
                        $addressForm[
                            'street'
                        ]
                        ?? ''
                    )
                ),
            'postal_code' =>
                self::nullIfEmpty(
                    (string) (
                        $addressForm[
                            'postal_code'
                        ]
                        ?? ''
                    )
                ),
            'city' =>
                self::nullIfEmpty(
                    (string) (
                        $addressForm[
                            'city'
                        ]
                        ?? ''
                    )
                ),
            'full_address' =>
                self::nullIfEmpty(
                    (string) (
                        $addressForm[
                            'full_address'
                        ]
                        ?? ''
                    )
                ),
            'pesel' =>
                self::nullIfEmpty(
                    preg_replace(
                        '/\D+/',
                        '',
                        $csv['pesel']
                        ?? ''
                    )
                    ?? ''
                ),
            'document_number' =>
                self::nullIfEmpty(
                    self::cleanText(
                        $csv[
                            'document_number'
                        ]
                        ?? (
                            $csv[
                                'id_document'
                            ]
                            ?? ''
                        )
                    )
                ),
            'birth_date' => $birthDate,
            'notes' =>
                self::nullIfEmpty(
                    self::cleanText(
                        $csv['notes']
                        ?? ''
                    )
                ),
            'preferred_contact' =>
                $preferredContact,
            'preferences' =>
                self::nullIfEmpty(
                    self::cleanText(
                        $csv[
                            'preferences'
                        ]
                        ?? ''
                    )
                ),
            'important_notes' =>
                self::nullIfEmpty(
                    self::cleanText(
                        $csv[
                            'important_notes'
                        ]
                        ?? ''
                    )
                ),
            'source' => $source,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function findExistingGuest(
        PDO $pdo,
        string $email,
        ?string $phone
    ): ?array {
        $statement = $pdo->prepare(
            'SELECT *
            FROM guests
            WHERE LOWER(email)
                = LOWER(:email)
            LIMIT 1'
        );

        $statement->execute([
            'email' => $email,
        ]);

        $row = $statement->fetch();

        if (is_array($row)) {
            return $row;
        }

        $phoneKey =
            self::phoneKey(
                $phone
            );

        if ($phoneKey === '') {
            return null;
        }

        $statement = $pdo->query(
            'SELECT *
            FROM guests
            WHERE phone IS NOT NULL
            AND phone <> ""'
        );

        if ($statement === false) {
            return null;
        }

        foreach (
            $statement->fetchAll()
            as $candidate
        ) {
            if (
                !is_array($candidate)
            ) {
                continue;
            }

            if (
                self::phoneKey(
                    isset(
                        $candidate['phone']
                    )
                        ? (string) $candidate[
                            'phone'
                        ]
                        : null
                ) === $phoneKey
            ) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function insertGuest(
        PDO $pdo,
        array $data
    ): void {
        $statement = $pdo->prepare(
            'INSERT INTO guests (
                first_name,
                last_name,
                email,
                phone,
                country,
                street,
                postal_code,
                city,
                full_address,
                pesel,
                document_number,
                birth_date,
                is_vip,
                notes,
                preferred_contact,
                preferences,
                important_notes,
                source
            ) VALUES (
                :first_name,
                :last_name,
                :email,
                :phone,
                :country,
                :street,
                :postal_code,
                :city,
                :full_address,
                :pesel,
                :document_number,
                :birth_date,
                0,
                :notes,
                :preferred_contact,
                :preferences,
                :important_notes,
                :source
            )'
        );

        $statement->execute($data);
    }

    /**
     * Aktualizujemy dane kontaktowe i adresowe,
     * ale NIE nadpisujemy źródła istniejącego gościa.
     * Źródło gościa oznacza pierwsze pozyskanie gościa.
     *
     * @param array<string, mixed> $data
     */
    private static function updateGuest(
        PDO $pdo,
        int $id,
        array $data
    ): void {
        unset(
            $data['source']
        );

        $data['id'] = $id;

        $statement = $pdo->prepare(
            'UPDATE guests
            SET
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = COALESCE(
                    :phone,
                    phone
                ),
                country = COALESCE(
                    :country,
                    country
                ),
                street = COALESCE(
                    :street,
                    street
                ),
                postal_code = COALESCE(
                    :postal_code,
                    postal_code
                ),
                city = COALESCE(
                    :city,
                    city
                ),
                full_address = COALESCE(
                    :full_address,
                    full_address
                ),
                pesel = COALESCE(
                    :pesel,
                    pesel
                ),
                document_number = COALESCE(
                    :document_number,
                    document_number
                ),
                birth_date = COALESCE(
                    :birth_date,
                    birth_date
                ),
                notes = COALESCE(
                    :notes,
                    notes
                ),
                preferred_contact = COALESCE(
                    :preferred_contact,
                    preferred_contact
                ),
                preferences = COALESCE(
                    :preferences,
                    preferences
                ),
                important_notes = COALESCE(
                    :important_notes,
                    important_notes
                )
            WHERE id = :id'
        );

        $statement->execute($data);
    }

    /**
     * @param resource $handle
     * @return array<int, string>
     */
    private static function readHeaders(
        $handle
    ): array {
        $headers = fgetcsv(
            $handle,
            0,
            ';'
        );

        if (
            $headers === false
            || $headers === [null]
        ) {
            throw new RuntimeException(
                'Plik CSV jest pusty albo ma niepoprawny format.'
            );
        }

        return array_map(
            static fn (
                mixed $header
            ): string =>
                self::normalizeHeader(
                    (string) $header
                ),
            $headers
        );
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, string> $required
     */
    private static function requireColumns(
        array $headers,
        array $required
    ): void {
        $missing = [];

        foreach ($required as $column) {
            if (
                !in_array(
                    $column,
                    $headers,
                    true
                )
            ) {
                $missing[] = $column;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Brakuje wymaganych kolumn: '
                . implode(
                    ', ',
                    $missing
                )
            );
        }
    }

    private static function normalizeHeader(
        string $header
    ): string {
        $header =
            preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $header
            )
            ?? $header;

        return strtolower(
            trim($header)
        );
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, mixed> $row
     * @return array<string, string>
     */
    private static function mapCsvRow(
        array $headers,
        array $row
    ): array {
        $result = [];

        foreach (
            $headers
            as $index => $header
        ) {
            $result[$header] =
                isset($row[$index])
                    ? trim(
                        (string) $row[
                            $index
                        ]
                    )
                    : '';
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $row
     */
    private static function rowIsEmpty(
        array $row
    ): bool {
        foreach ($row as $value) {
            if (
                trim(
                    (string) $value
                ) !== ''
            ) {
                return false;
            }
        }

        return true;
    }

    private static function cleanText(
        string $value
    ): string {
        $value =
            preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $value
            )
            ?? $value;

        $value = trim($value);

        $value = str_replace(
            [
                "\r\n",
                "\r",
            ],
            "\n",
            $value
        );

        return trim(
            preg_replace(
                '/[ \t]+/',
                ' ',
                $value
            )
            ?? $value
        );
    }

    private static function nullIfEmpty(
        string $value
    ): ?string {
        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }

    private static function phoneKey(
        ?string $phone
    ): string {
        if ($phone === null) {
            return '';
        }

        return preg_replace(
            '/\D+/',
            '',
            $phone
        )
        ?? '';
    }

    private static function optionalDate(
        string $value,
        int $rowNumber
    ): ?string {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return (
                new DateTimeImmutable(
                    $value
                )
            )->format(
                'Y-m-d'
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': nieprawidłowa data urodzenia.'
            );
        }
    }

    private static function optionalPreferredContact(
        string $value,
        int $rowNumber
    ): ?string {
        $value = strtoupper(
            trim($value)
        );

        if ($value === '') {
            return null;
        }

        if (
            !in_array(
                $value,
                [
                    'PHONE',
                    'EMAIL',
                    'SMS',
                    'WHATSAPP',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': nieprawidłowy preferred_contact.'
            );
        }

        return $value;
    }

    private static function mapSource(
        string $source
    ): string {
        $source = trim($source);

        if ($source === '') {
            return 'MANUAL';
        }

        $normalized =
            function_exists(
                'normalizePmsSource'
            )
                ? normalizePmsSource(
                    $source
                )
                : strtoupper($source);

        if (
            !in_array(
                $normalized,
                [
                    'MANUAL',
                    'DIRECT',
                    'WWW',
                    'BOOKING',
                    'PHONE',
                    'AIRBNB',
                    'ICAL_OTHER',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Nieprawidłowe źródło gościa: '
                . $source
            );
        }

        return $normalized;
    }
}
