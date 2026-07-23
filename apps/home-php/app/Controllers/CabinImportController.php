<?php

declare(strict_types=1);

final class CabinImportController
{
    public static function show(): void
    {
        Response::html(
            View::render(
                'pages/admin_cabins_import',
                [
                    'title' =>
                        'Import domków CSV',
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
                    'pages/admin_cabins_import',
                    [
                        'title' =>
                            'Import domków CSV',
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
                'pages/admin_cabins_import',
                [
                    'title' =>
                        'Import domków CSV',
                    'result' => null,
                    'errorMessage' => $message,
                ]
            )
        );
    }

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
                'Nie udało się otworzyć pliku CSV.'
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
                    'short_name',
                    'name',
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
                        self::rowIsEmpty($row)
                    ) {
                        continue;
                    }

                    $csv = self::mapCsvRow(
                        $headers,
                        $row
                    );

                    $shortName =
                        strtoupper(
                            self::cleanText(
                                $csv[
                                    'short_name'
                                ]
                                ?? ''
                            )
                        );

                    $name =
                        self::cleanText(
                            $csv['name']
                            ?? ''
                        );

                    if (
                        $shortName === ''
                        || $name === ''
                    ) {
                        throw new RuntimeException(
                            'Wiersz '
                            . $rowNumber
                            . ': short_name i name są wymagane.'
                        );
                    }

                    $existing =
                        self::findExistingCabin(
                            $pdo,
                            $shortName,
                            $name
                        );

                    $data =
                        self::buildCabinData(
                            $csv,
                            $existing,
                            $rowNumber,
                            $shortName,
                            $name
                        );

                    if ($existing !== null) {
                        self::updateCabin(
                            $pdo,
                            (int) $existing['id'],
                            $data
                        );

                        $updated++;

                        continue;
                    }

                    self::insertCabin(
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

                throw $exception;
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

    private static function buildCabinData(
        array $csv,
        ?array $existing,
        int $rowNumber,
        string $shortName,
        string $name
    ): array {
        $existing = $existing ?? [];

        $sortOrder =
            self::optionalInt(
                $csv['sort_order']
                ?? ''
            );

        if ($sortOrder === null) {
            $number =
                preg_replace(
                    '/\D+/',
                    '',
                    $shortName
                )
                ?? '';

            $sortOrder =
                $number !== ''
                    ? (int) $number
                    : (
                        isset(
                            $existing[
                                'sort_order'
                            ]
                        )
                            ? (int) $existing[
                                'sort_order'
                            ]
                            : $rowNumber
                    );
        }

        return [
            'name' => $name,
            'short_name' => $shortName,
            'description' =>
                self::textOrExisting(
                    $csv,
                    'description',
                    $existing,
                    ''
                ),
            'max_guests' =>
                self::positiveIntOrExisting(
                    $csv,
                    'max_guests',
                    $existing,
                    6
                ),
            'area_sqm' =>
                self::nullablePositiveIntOrExisting(
                    $csv,
                    'area_sqm',
                    $existing
                ),
            'bedrooms' =>
                self::positiveIntOrExisting(
                    $csv,
                    'bedrooms',
                    $existing,
                    2
                ),
            'bathrooms' =>
                self::positiveIntOrExisting(
                    $csv,
                    'bathrooms',
                    $existing,
                    1
                ),
            'price_per_night' =>
                self::priceOrExisting(
                    $csv,
                    [
                        'price_per_night',
                    ],
                    $existing,
                    'price_per_night',
                    440
                ),
            'price_one_night' =>
                self::priceOrExisting(
                    $csv,
                    [
                        'price_one_night',
                        'price_1_night',
                    ],
                    $existing,
                    'price_one_night',
                    800
                ),
            'price_two_nights' =>
                self::priceOrExisting(
                    $csv,
                    [
                        'price_two_nights',
                        'price_2_nights',
                    ],
                    $existing,
                    'price_two_nights',
                    440
                ),
            'price_three_nights' =>
                self::priceOrExisting(
                    $csv,
                    [
                        'price_three_nights',
                        'price_3_nights',
                    ],
                    $existing,
                    'price_three_nights',
                    430
                ),
            'price_four_nights' =>
                self::priceOrExisting(
                    $csv,
                    [
                        'price_four_nights',
                        'price_4_nights',
                    ],
                    $existing,
                    'price_four_nights',
                    420
                ),
            'price_five_nights' =>
                self::priceOrExisting(
                    $csv,
                    [
                        'price_five_nights',
                        'price_5_nights',
                    ],
                    $existing,
                    'price_five_nights',
                    410
                ),
            'price_six_nights' =>
                self::priceOrExisting(
                    $csv,
                    [
                        'price_six_nights',
                        'price_6_nights',
                    ],
                    $existing,
                    'price_six_nights',
                    400
                ),
            'price_seven_plus_nights' =>
                self::priceOrExisting(
                    $csv,
                    [
                        'price_seven_plus_nights',
                        'price_7_plus_nights',
                    ],
                    $existing,
                    'price_seven_plus_nights',
                    350
                ),
            'amenities' =>
                self::nullableTextOrExisting(
                    $csv,
                    'amenities',
                    $existing
                ),
            'location' =>
                self::nullableTextOrExisting(
                    $csv,
                    'location',
                    $existing
                ),
            'cabin_type' =>
                self::nullableTextOrExisting(
                    $csv,
                    'cabin_type',
                    $existing,
                    $csv['type']
                    ?? ''
                ),
            'pets_allowed' =>
                self::booleanOrExisting(
                    $csv,
                    'pets_allowed',
                    $existing,
                    0
                ),
            'has_parking' =>
                self::booleanOrExisting(
                    $csv,
                    'has_parking',
                    $existing,
                    0
                ),
            'has_kitchen' =>
                self::booleanOrExisting(
                    $csv,
                    'has_kitchen',
                    $existing,
                    0
                ),
            'is_active' =>
                self::statusOrExisting(
                    $csv['status']
                    ?? '',
                    $existing
                ),
            'sort_order' => $sortOrder,
        ];
    }

    private static function findExistingCabin(
        PDO $pdo,
        string $shortName,
        string $name
    ): ?array {
        $statement = $pdo->prepare(
            'SELECT *
            FROM cabins
            WHERE UPPER(short_name)
                = UPPER(:short_name)
            LIMIT 1'
        );

        $statement->execute([
            'short_name' => $shortName,
        ]);

        $row = $statement->fetch();

        if (is_array($row)) {
            return $row;
        }

        $statement = $pdo->prepare(
            'SELECT *
            FROM cabins
            WHERE LOWER(name)
                = LOWER(:name)
            LIMIT 1'
        );

        $statement->execute([
            'name' => $name,
        ]);

        $row = $statement->fetch();

        return is_array($row)
            ? $row
            : null;
    }

    private static function insertCabin(
        PDO $pdo,
        array $data
    ): void {
        $statement = $pdo->prepare(
            'INSERT INTO cabins (
                name,
                short_name,
                description,
                max_guests,
                area_sqm,
                bedrooms,
                bathrooms,
                price_per_night,
                price_one_night,
                price_two_nights,
                price_three_nights,
                price_four_nights,
                price_five_nights,
                price_six_nights,
                price_seven_plus_nights,
                amenities,
                location,
                cabin_type,
                pets_allowed,
                has_parking,
                has_kitchen,
                is_active,
                sort_order
            ) VALUES (
                :name,
                :short_name,
                :description,
                :max_guests,
                :area_sqm,
                :bedrooms,
                :bathrooms,
                :price_per_night,
                :price_one_night,
                :price_two_nights,
                :price_three_nights,
                :price_four_nights,
                :price_five_nights,
                :price_six_nights,
                :price_seven_plus_nights,
                :amenities,
                :location,
                :cabin_type,
                :pets_allowed,
                :has_parking,
                :has_kitchen,
                :is_active,
                :sort_order
            )'
        );

        $statement->execute($data);
    }

    private static function updateCabin(
        PDO $pdo,
        int $id,
        array $data
    ): void {
        $data['id'] = $id;

        $statement = $pdo->prepare(
            'UPDATE cabins
            SET
                name = :name,
                short_name = :short_name,
                description = :description,
                max_guests = :max_guests,
                area_sqm = :area_sqm,
                bedrooms = :bedrooms,
                bathrooms = :bathrooms,
                price_per_night = :price_per_night,
                price_one_night = :price_one_night,
                price_two_nights = :price_two_nights,
                price_three_nights = :price_three_nights,
                price_four_nights = :price_four_nights,
                price_five_nights = :price_five_nights,
                price_six_nights = :price_six_nights,
                price_seven_plus_nights = :price_seven_plus_nights,
                amenities = :amenities,
                location = :location,
                cabin_type = :cabin_type,
                pets_allowed = :pets_allowed,
                has_parking = :has_parking,
                has_kitchen = :has_kitchen,
                is_active = :is_active,
                sort_order = :sort_order
            WHERE id = :id'
        );

        $statement->execute($data);
    }

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

        return trim(
            preg_replace(
                '/[ \t]+/',
                ' ',
                str_replace(
                    [
                        "\r\n",
                        "\r",
                    ],
                    "\n",
                    trim($value)
                )
            )
            ?? $value
        );
    }

    private static function textOrExisting(
        array $csv,
        string $key,
        array $existing,
        string $default
    ): string {
        $value =
            self::cleanText(
                $csv[$key]
                ?? ''
            );

        if ($value !== '') {
            return $value;
        }

        return isset(
            $existing[$key]
        )
            ? (string) $existing[$key]
            : $default;
    }

    private static function nullableTextOrExisting(
        array $csv,
        string $key,
        array $existing,
        string $alternative = ''
    ): ?string {
        $value =
            self::cleanText(
                $csv[$key]
                ?? $alternative
            );

        if ($value !== '') {
            return $value;
        }

        return isset(
            $existing[$key]
        )
        && $existing[$key] !== null
            ? (string) $existing[$key]
            : null;
    }

    private static function optionalInt(
        string $value
    ): ?int {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw new RuntimeException(
                'Nieprawidłowa liczba całkowita: '
                . $value
            );
        }

        return (int) $value;
    }

    private static function optionalMoneyInt(
        string $value
    ): ?int {
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
                'Nieprawidłowa cena: '
                . $value
            );
        }

        return (int) round(
            (float) $value
        );
    }

    private static function positiveIntOrExisting(
        array $csv,
        string $key,
        array $existing,
        int $default
    ): int {
        $value = self::optionalInt(
            $csv[$key]
            ?? ''
        );

        if ($value !== null) {
            if ($value < 1) {
                throw new RuntimeException(
                    $key
                    . ' musi być większe od 0.'
                );
            }

            return $value;
        }

        return isset(
            $existing[$key]
        )
            ? (int) $existing[$key]
            : $default;
    }

    private static function nullablePositiveIntOrExisting(
        array $csv,
        string $key,
        array $existing
    ): ?int {
        $value = self::optionalInt(
            $csv[$key]
            ?? ''
        );

        if ($value !== null) {
            if ($value < 1) {
                throw new RuntimeException(
                    $key
                    . ' musi być większe od 0.'
                );
            }

            return $value;
        }

        return isset(
            $existing[$key]
        )
        && $existing[$key] !== null
            ? (int) $existing[$key]
            : null;
    }

    private static function priceOrExisting(
        array $csv,
        array $keys,
        array $existing,
        string $existingKey,
        int $default
    ): int {
        foreach ($keys as $key) {
            if (
                isset($csv[$key])
                && trim(
                    $csv[$key]
                ) !== ''
            ) {
                $value =
                    self::optionalMoneyInt(
                        $csv[$key]
                    );

                if ($value !== null) {
                    return $value;
                }
            }
        }

        return isset(
            $existing[$existingKey]
        )
            ? (int) $existing[
                $existingKey
            ]
            : $default;
    }

    private static function booleanOrExisting(
        array $csv,
        string $key,
        array $existing,
        int $default
    ): int {
        $value = strtolower(
            trim(
                $csv[$key]
                ?? ''
            )
        );

        if ($value === '') {
            return isset(
                $existing[$key]
            )
                ? (int) $existing[$key]
                : $default;
        }

        if (
            in_array(
                $value,
                [
                    '1',
                    'true',
                    'yes',
                    'tak',
                    't',
                ],
                true
            )
        ) {
            return 1;
        }

        if (
            in_array(
                $value,
                [
                    '0',
                    'false',
                    'no',
                    'nie',
                    'n',
                ],
                true
            )
        ) {
            return 0;
        }

        throw new RuntimeException(
            'Nieprawidłowa wartość logiczna w '
            . $key
            . ': '
            . $value
        );
    }

    private static function statusOrExisting(
        string $value,
        array $existing
    ): int {
        $value = strtolower(
            trim($value)
        );

        if ($value === '') {
            return isset(
                $existing['is_active']
            )
                ? (int) $existing[
                    'is_active'
                ]
                : 1;
        }

        if (
            in_array(
                $value,
                [
                    'active',
                    'aktywny',
                    'dostępny',
                    'dostepny',
                    '1',
                    'true',
                    'tak',
                ],
                true
            )
        ) {
            return 1;
        }

        if (
            in_array(
                $value,
                [
                    'inactive',
                    'nieaktywny',
                    'ukryty',
                    '0',
                    'false',
                    'nie',
                ],
                true
            )
        ) {
            return 0;
        }

        throw new RuntimeException(
            'Nieprawidłowy status domku: '
            . $value
        );
    }
}
