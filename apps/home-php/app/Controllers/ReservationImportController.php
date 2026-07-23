<?php

declare(strict_types=1);

final class ReservationImportController
{
    public static function show(): void
    {
        Response::html(
            View::render(
                'pages/admin_reservations_import',
                [
                    'title' =>
                        'Import rezerwacji CSV',
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
                    'pages/admin_reservations_import',
                    [
                        'title' =>
                            'Import rezerwacji CSV',
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
                'pages/admin_reservations_import',
                [
                    'title' =>
                        'Import rezerwacji CSV',
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
                    'cabin',
                    'first_name',
                    'last_name',
                    'email',
                    'check_in',
                    'check_out',
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

                    $reservation =
                        self::reservationData(
                            $pdo,
                            $csv,
                            $rowNumber
                        );

                    $existing =
                        self::findExistingReservation(
                            $pdo,
                            (int) $reservation[
                                'cabin_id'
                            ],
                            (int) $reservation[
                                'guest_id'
                            ],
                            (string) $reservation[
                                'email'
                            ],
                            (string) $reservation[
                                'start_date'
                            ],
                            (string) $reservation[
                                'end_date'
                            ]
                        );

                    if ($existing !== null) {
                        self::updateReservation(
                            $pdo,
                            (int) $existing['id'],
                            $reservation
                        );

                        $updated++;

                        continue;
                    }

                    self::insertReservation(
                        $pdo,
                        $reservation
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
    private static function reservationData(
        PDO $pdo,
        array $csv,
        int $rowNumber
    ): array {
        $cabinValue =
            self::cleanText(
                $csv['cabin']
                ?? ''
            );

        if ($cabinValue === '') {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': cabin jest wymagane.'
            );
        }

        $cabin =
            self::findCabin(
                $pdo,
                $cabinValue
            );

        if ($cabin === null) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': nie znaleziono domku "'
                . $cabinValue
                . '".'
            );
        }

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

        $source =
            self::mapSource(
                $csv['source']
                ?? ''
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
            'full_address' => '',
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

        $guest =
            self::findGuest(
                $pdo,
                $email,
                $phone
            );

        if ($guest === null) {
            $guest =
                self::createGuest(
                    $pdo,
                    [
                        'first_name' =>
                            $firstName,
                        'last_name' =>
                            $lastName,
                        'email' =>
                            $email,
                        'phone' =>
                            $phone,
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
                        'source' =>
                            $source,
                    ]
                );
        } else {
            self::completeGuestData(
                $pdo,
                (int) $guest['id'],
                [
                    'first_name' =>
                        $firstName,
                    'last_name' =>
                        $lastName,
                    'email' =>
                        $email,
                    'phone' =>
                        $phone,
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
                ]
            );

            $guest =
                self::findGuestById(
                    $pdo,
                    (int) $guest['id']
                )
                ?? $guest;
        }

        $startDate =
            self::requiredDate(
                $csv['check_in']
                ?? '',
                $rowNumber,
                'check_in'
            );

        $endDate =
            self::requiredDate(
                $csv['check_out']
                ?? '',
                $rowNumber,
                'check_out'
            );

        if ($endDate <= $startDate) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': check_out musi być później niż check_in.'
            );
        }

        $nights =
            self::calculateNights(
                $startDate,
                $endDate
            );

        $adults =
            self::positiveInt(
                $csv['adults']
                ?? '',
                1,
                'adults',
                $rowNumber
            );

        $children =
            self::nonNegativeInt(
                $csv['children']
                ?? '',
                0,
                'children',
                $rowNumber
            );

        $guests =
            $adults
            + $children;

        if (
            $guests > (int) (
                $cabin['max_guests']
                ?? 0
            )
        ) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': liczba gości ('
                . $guests
                . ') przekracza pojemność domku '
                . (string) (
                    $cabin['short_name']
                    ?? $cabin['name']
                    ?? ''
                )
                . ' ('
                . (int) (
                    $cabin['max_guests']
                    ?? 0
                )
                . ').'
            );
        }

        $totalPrice =
            self::optionalMoney(
                $csv['total_price']
                ?? '',
                $rowNumber,
                'total_price'
            );

        if ($totalPrice === null) {
            $totalPrice =
                $nights
                * self::nightPrice(
                    $cabin,
                    $nights
                );
        }

        $paidAmount =
            self::optionalMoney(
                $csv['paid_amount']
                ?? '',
                $rowNumber,
                'paid_amount'
            )
            ?? 0.0;

        if (
            $paidAmount
            > $totalPrice
        ) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': paid_amount nie może być większe od total_price.'
            );
        }

        $paymentStatus =
            self::paymentStatus(
                $csv['payment_status']
                ?? '',
                $paidAmount,
                $totalPrice
            );

        $status =
            self::reservationStatus(
                $csv['status']
                ?? ''
            );

        $guestName = trim(
            $firstName
            . ' '
            . $lastName
        );

        return [
            'cabin_id' =>
                (int) $cabin['id'],
            'guest_id' =>
                (int) $guest['id'],
            'guest_name' =>
                $guestName,
            'email' => $email,
            'phone' =>
                $phone
                ?? (
                    isset($guest['phone'])
                    && $guest['phone'] !== null
                        ? (string) $guest[
                            'phone'
                        ]
                        : null
                ),
            'first_name' =>
                $firstName,
            'last_name' =>
                $lastName,
            'start_date' =>
                $startDate,
            'end_date' =>
                $endDate,
            'check_in_at' =>
                self::combineDateAndTime(
                    $startDate,
                    $csv[
                        'check_in_time'
                    ]
                    ?? '',
                    '15:00'
                ),
            'check_out_at' =>
                self::combineDateAndTime(
                    $endDate,
                    $csv[
                        'check_out_time'
                    ]
                    ?? '',
                    '11:00'
                ),
            'nights' => $nights,
            'price_per_night' =>
                $nights > 0
                    ? round(
                        $totalPrice
                        / $nights,
                        2
                    )
                    : $totalPrice,
            'guests' => $guests,
            'adults' => $adults,
            'children' => $children,
            'status' => $status,
            'source' => $source,
            'payment_status' =>
                $paymentStatus,
            'total_price' =>
                $totalPrice,
            'paid_amount' =>
                $paidAmount,
            'street' =>
                self::nullIfEmpty(
                    (string) (
                        $addressForm[
                            'street'
                        ]
                        ?? (
                            $guest['street']
                            ?? ''
                        )
                    )
                ),
            'postal_code' =>
                self::nullIfEmpty(
                    (string) (
                        $addressForm[
                            'postal_code'
                        ]
                        ?? (
                            $guest[
                                'postal_code'
                            ]
                            ?? ''
                        )
                    )
                ),
            'city' =>
                self::nullIfEmpty(
                    (string) (
                        $addressForm[
                            'city'
                        ]
                        ?? (
                            $guest['city']
                            ?? ''
                        )
                    )
                ),
            'country' =>
                self::nullIfEmpty(
                    (string) (
                        $addressForm[
                            'country'
                        ]
                        ?? (
                            $guest[
                                'country'
                            ]
                            ?? ''
                        )
                    )
                ),
            'notes' =>
                self::nullIfEmpty(
                    self::cleanText(
                        $csv['notes']
                        ?? ''
                    )
                ),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function findCabin(
        PDO $pdo,
        string $value
    ): ?array {
        $statement = $pdo->prepare(
            'SELECT *
            FROM cabins
            WHERE UPPER(short_name)
                = UPPER(:value)
            LIMIT 1'
        );

        $statement->execute([
            'value' => $value,
        ]);

        $row = $statement->fetch();

        if (is_array($row)) {
            return $row;
        }

        $statement = $pdo->prepare(
            'SELECT *
            FROM cabins
            WHERE LOWER(name)
                = LOWER(:value)
            LIMIT 1'
        );

        $statement->execute([
            'value' => $value,
        ]);

        $row = $statement->fetch();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function findGuest(
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
     * @return array<string, mixed>|null
     */
    private static function findGuestById(
        PDO $pdo,
        int $id
    ): ?array {
        $statement = $pdo->prepare(
            'SELECT *
            FROM guests
            WHERE id = :id
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
     * @return array<string, mixed>
     */
    private static function createGuest(
        PDO $pdo,
        array $data
    ): array {
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
                is_vip,
                source,
                notes
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
                0,
                :source,
                :notes
            )'
        );

        $statement->execute([
            'first_name' =>
                $data['first_name'],
            'last_name' =>
                $data['last_name'],
            'email' =>
                $data['email'],
            'phone' =>
                $data['phone'],
            'country' =>
                $data['country'],
            'street' =>
                $data['street'],
            'postal_code' =>
                $data['postal_code'],
            'city' =>
                $data['city'],
            'full_address' =>
                $data['full_address'],
            'source' =>
                $data['source'],
            'notes' =>
                'Gość utworzony automatycznie podczas importu CSV rezerwacji.',
        ]);

        $id = (int) $pdo
            ->lastInsertId();

        return self::findGuestById(
            $pdo,
            $id
        ) ?? [
            'id' => $id,
            'first_name' =>
                $data['first_name'],
            'last_name' =>
                $data['last_name'],
            'email' =>
                $data['email'],
            'phone' =>
                $data['phone'],
            'country' =>
                $data['country'],
            'street' =>
                $data['street'],
            'postal_code' =>
                $data['postal_code'],
            'city' =>
                $data['city'],
        ];
    }

    /**
     * Uzupełniamy brakujące dane istniejącego gościa,
     * ale nie zmieniamy jego źródła.
     *
     * @param array<string, mixed> $data
     */
    private static function completeGuestData(
        PDO $pdo,
        int $id,
        array $data
    ): void {
        $data['id'] = $id;

        $statement = $pdo->prepare(
            'UPDATE guests
            SET
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = COALESCE(
                    phone,
                    :phone
                ),
                country = COALESCE(
                    country,
                    :country
                ),
                street = COALESCE(
                    street,
                    :street
                ),
                postal_code = COALESCE(
                    postal_code,
                    :postal_code
                ),
                city = COALESCE(
                    city,
                    :city
                ),
                full_address = COALESCE(
                    full_address,
                    :full_address
                )
            WHERE id = :id'
        );

        $statement->execute($data);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function findExistingReservation(
        PDO $pdo,
        int $cabinId,
        int $guestId,
        string $email,
        string $startDate,
        string $endDate
    ): ?array {
        $statement = $pdo->prepare(
            'SELECT id
            FROM reservations
            WHERE cabin_id = :cabin_id
            AND start_date = :start_date
            AND end_date = :end_date
            AND (
                guest_id = :guest_id
                OR LOWER(email)
                    = LOWER(:email)
            )
            LIMIT 1'
        );

        $statement->execute([
            'cabin_id' => $cabinId,
            'guest_id' => $guestId,
            'email' => $email,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $row = $statement->fetch();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function insertReservation(
        PDO $pdo,
        array $data
    ): void {
        $statement = $pdo->prepare(
            'INSERT INTO reservations (
                cabin_id,
                guest_id,
                guest_name,
                email,
                phone,
                first_name,
                last_name,
                start_date,
                end_date,
                check_in_at,
                check_out_at,
                nights,
                price_per_night,
                guests,
                adults,
                children,
                status,
                source,
                payment_status,
                total_price,
                paid_amount,
                street,
                postal_code,
                city,
                country,
                notes
            ) VALUES (
                :cabin_id,
                :guest_id,
                :guest_name,
                :email,
                :phone,
                :first_name,
                :last_name,
                :start_date,
                :end_date,
                :check_in_at,
                :check_out_at,
                :nights,
                :price_per_night,
                :guests,
                :adults,
                :children,
                :status,
                :source,
                :payment_status,
                :total_price,
                :paid_amount,
                :street,
                :postal_code,
                :city,
                :country,
                :notes
            )'
        );

        $statement->execute(
            $data
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function updateReservation(
        PDO $pdo,
        int $id,
        array $data
    ): void {
        $data['id'] = $id;

        $statement = $pdo->prepare(
            'UPDATE reservations
            SET
                cabin_id = :cabin_id,
                guest_id = :guest_id,
                guest_name = :guest_name,
                email = :email,
                phone = :phone,
                first_name = :first_name,
                last_name = :last_name,
                start_date = :start_date,
                end_date = :end_date,
                check_in_at = :check_in_at,
                check_out_at = :check_out_at,
                nights = :nights,
                price_per_night = :price_per_night,
                guests = :guests,
                adults = :adults,
                children = :children,
                status = :status,
                source = :source,
                payment_status = :payment_status,
                total_price = :total_price,
                paid_amount = :paid_amount,
                street = :street,
                postal_code = :postal_code,
                city = :city,
                country = :country,
                notes = :notes
            WHERE id = :id'
        );

        $statement->execute(
            $data
        );
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
        $value = trim(
            $value
        );

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

    private static function requiredDate(
        string $value,
        int $rowNumber,
        string $field
    ): string {
        $value = trim(
            $value
        );

        if ($value === '') {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': '
                . $field
                . ' jest wymagane.'
            );
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
                . ': nieprawidłowa data '
                . $field
                . '.'
            );
        }
    }

    private static function combineDateAndTime(
        string $date,
        string $time,
        string $fallback
    ): string {
        $time = trim(
            $time
        );

        if ($time === '') {
            $time = $fallback;
        }

        try {
            return (
                new DateTimeImmutable(
                    $date
                    . ' '
                    . $time
                )
            )->format(
                'Y-m-d H:i:s'
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Nieprawidłowa godzina: '
                . $time
            );
        }
    }

    private static function calculateNights(
        string $startDate,
        string $endDate
    ): int {
        $start =
            new DateTimeImmutable(
                $startDate
            );

        $end =
            new DateTimeImmutable(
                $endDate
            );

        return (int) $start
            ->diff($end)
            ->days;
    }

    private static function positiveInt(
        string $value,
        int $default,
        string $field,
        int $rowNumber
    ): int {
        $value = trim(
            $value
        );

        if ($value === '') {
            return $default;
        }

        if (
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
            || (int) $value < 1
        ) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': '
                . $field
                . ' musi być dodatnią liczbą całkowitą.'
            );
        }

        return (int) $value;
    }

    private static function nonNegativeInt(
        string $value,
        int $default,
        string $field,
        int $rowNumber
    ): int {
        $value = trim(
            $value
        );

        if ($value === '') {
            return $default;
        }

        if (
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
            || (int) $value < 0
        ) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': '
                . $field
                . ' musi być nieujemną liczbą całkowitą.'
            );
        }

        return (int) $value;
    }

    private static function optionalMoney(
        string $value,
        int $rowNumber,
        string $field
    ): ?float {
        $value = str_replace(
            ',',
            '.',
            trim($value)
        );

        if ($value === '') {
            return null;
        }

        if (
            !is_numeric($value)
            || (float) $value < 0
        ) {
            throw new RuntimeException(
                'Wiersz '
                . $rowNumber
                . ': nieprawidłowa kwota '
                . $field
                . '.'
            );
        }

        return round(
            (float) $value,
            2
        );
    }

    /**
     * @param array<string, mixed> $cabin
     */
    private static function nightPrice(
        array $cabin,
        int $nights
    ): float {
        $key = match (true) {
            $nights <= 1 =>
                'price_one_night',
            $nights === 2 =>
                'price_two_nights',
            $nights === 3 =>
                'price_three_nights',
            $nights === 4 =>
                'price_four_nights',
            $nights === 5 =>
                'price_five_nights',
            $nights === 6 =>
                'price_six_nights',
            default =>
                'price_seven_plus_nights',
        };

        return (float) (
            $cabin[$key]
            ?? $cabin[
                'price_per_night'
            ]
            ?? 0
        );
    }

    private static function reservationStatus(
        string $status
    ): string {
        $status = strtolower(
            trim($status)
        );

        if ($status === '') {
            return 'CONFIRMED';
        }

        return match ($status) {
            'pending',
            'oczekujaca',
            'oczekująca' =>
                'PENDING',

            'confirmed',
            'potwierdzona',
            'potwierdzony' =>
                'CONFIRMED',

            'checked_in',
            'check_in',
            'zameldowany' =>
                'CHECKED_IN',

            'checked_out',
            'completed',
            'wymeldowany' =>
                'CHECKED_OUT',

            'cancelled',
            'canceled',
            'anulowana',
            'anulowany' =>
                'CANCELLED',

            default =>
                throw new RuntimeException(
                    'Nieprawidłowy status rezerwacji: '
                    . $status
                ),
        };
    }

    private static function paymentStatus(
        string $status,
        float $paidAmount,
        float $totalPrice
    ): string {
        $status = strtolower(
            trim($status)
        );

        if (
            in_array(
                $status,
                [
                    'refunded',
                    'zwrócona',
                    'zwrocona',
                ],
                true
            )
        ) {
            return 'REFUNDED';
        }

        if (
            $totalPrice > 0
            && $paidAmount >= $totalPrice
        ) {
            return 'PAID';
        }

        if ($paidAmount > 0) {
            return 'PARTIAL';
        }

        return 'PENDING';
    }

    private static function mapSource(
        string $source
    ): string {
        $source = trim(
            $source
        );

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
                : strtoupper(
                    $source
                );

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
                'Nieprawidłowe źródło rezerwacji: '
                . $source
            );
        }

        return $normalized;
    }
}
