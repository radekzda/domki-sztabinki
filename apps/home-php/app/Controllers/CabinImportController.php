<?php

declare(strict_types=1);

final class CabinImportController
{
    public static function show(): void
    {
        Response::html(View::render('pages/admin_cabins_import', [
            'title' => 'Import domków',
            'result' => null,
            'errorMessage' => null,
        ]));
    }

    public static function store(): void
    {
        if (!Database::canAttemptConnection()) {
            Response::html(View::render('pages/admin_cabins_import', [
                'title' => 'Import domków',
                'result' => null,
                'errorMessage' => 'Baza danych nie jest jeszcze skonfigurowana.',
            ]));

            return;
        }

        if (!isset($_FILES['csv_file']) || !is_array($_FILES['csv_file'])) {
            Response::html(View::render('pages/admin_cabins_import', [
                'title' => 'Import domków',
                'result' => null,
                'errorMessage' => 'Nie wybrano pliku CSV.',
            ]));

            return;
        }

        $file = $_FILES['csv_file'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::html(View::render('pages/admin_cabins_import', [
                'title' => 'Import domków',
                'result' => null,
                'errorMessage' => 'Nie udało się wczytać pliku CSV. Kod błędu: ' . (string) ($file['error'] ?? 'brak'),
            ]));

            return;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            Response::html(View::render('pages/admin_cabins_import', [
                'title' => 'Import domków',
                'result' => null,
                'errorMessage' => 'Przesłany plik jest nieprawidłowy.',
            ]));

            return;
        }

        try {
            $pdo = Database::connection();
            self::ensureCabinsImportColumns($pdo);
            $result = self::importCsv($pdo, $tmpName);

            Response::html(View::render('pages/admin_cabins_import', [
                'title' => 'Import domków',
                'result' => $result,
                'errorMessage' => null,
            ]));
        } catch (Throwable $exception) {
            Response::html(View::render('pages/admin_cabins_import', [
                'title' => 'Import domków',
                'result' => null,
                'errorMessage' => 'Import przerwany: ' . AppErrorHandler::safeMessage($exception),
            ]));
        }
    }

    private static function importCsv(PDO $pdo, string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Nie udało się otworzyć przesłanego pliku.');
        }

        $headers = fgetcsv($handle, 0, ';');

        if ($headers === false || $headers === [null]) {
            fclose($handle);

            throw new RuntimeException('Plik CSV jest pusty albo ma niepoprawny format.');
        }

        $headers = array_map(
            static fn (string $header): string => self::normalizeHeader($header),
            $headers
        );

        $requiredColumns = [
            'id',
            'name',
            'number',
            'description',
            'max_guests',
            'area_sqm',
            'bedrooms',
            'bathrooms',
            'price_1_night',
            'price_2_nights',
            'price_3_nights',
            'price_4_nights',
            'price_5_nights',
            'price_6_nights',
            'price_7_plus_nights',
            'price_per_night',
            'amenities',
            'location',
            'type',
            'status',
            'pets_allowed',
            'has_parking',
            'has_kitchen',
        ];

        $missingColumns = [];

        foreach ($requiredColumns as $requiredColumn) {
            if (!in_array($requiredColumn, $headers, true)) {
                $missingColumns[] = $requiredColumn;
            }
        }

        if ($missingColumns !== []) {
            fclose($handle);

            throw new RuntimeException('Brakuje kolumn w CSV: ' . implode(', ', $missingColumns));
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $rowNumber = 1;

        $pdo->beginTransaction();

        try {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rowNumber++;

                if ($row === [] || $row === [null]) {
                    continue;
                }

                $csvRow = self::mapCsvRow($headers, $row);
                $cabin = self::cabinDataFromBase44($csvRow, $rowNumber);

                if ($cabin === null) {
                    $skipped++;
                    continue;
                }

                $existingCabin = self::findExistingCabin(
                    $pdo,
                    $cabin['external_id'],
                    $cabin['short_name'],
                    $cabin['name']
                );

                if ($existingCabin !== null) {
                    self::updateCabin($pdo, (int) $existingCabin['id'], $cabin);
                    $updated++;

                    continue;
                }

                self::insertCabin($pdo, $cabin);
                $inserted++;
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            fclose($handle);

            throw new RuntimeException('Błąd w okolicy wiersza ' . $rowNumber . ': ' . AppErrorHandler::safeMessage($exception));
        }

        fclose($handle);

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $inserted + $updated + $skipped,
        ];
    }

    private static function ensureCabinsImportColumns(PDO $pdo): void
    {
        $columns = self::tableColumns($pdo, 'cabins');

        if (!in_array('external_id', $columns, true)) {
            $pdo->exec('ALTER TABLE cabins ADD COLUMN external_id VARCHAR(80) NULL AFTER id');
        }

        if (!in_array('area_sqm', $columns, true)) {
            $pdo->exec('ALTER TABLE cabins ADD COLUMN area_sqm INT NULL AFTER max_guests');
        }

        if (!in_array('amenities', $columns, true)) {
            $pdo->exec('ALTER TABLE cabins ADD COLUMN amenities TEXT NULL AFTER description');
        }

        if (!in_array('location', $columns, true)) {
            $pdo->exec('ALTER TABLE cabins ADD COLUMN location VARCHAR(120) NULL AFTER amenities');
        }

        if (!in_array('cabin_type', $columns, true)) {
            $pdo->exec('ALTER TABLE cabins ADD COLUMN cabin_type VARCHAR(80) NULL AFTER location');
        }

        if (!in_array('pets_allowed', $columns, true)) {
            $pdo->exec('ALTER TABLE cabins ADD COLUMN pets_allowed TINYINT(1) NOT NULL DEFAULT 0 AFTER cabin_type');
        }

        if (!in_array('has_parking', $columns, true)) {
            $pdo->exec('ALTER TABLE cabins ADD COLUMN has_parking TINYINT(1) NOT NULL DEFAULT 0 AFTER pets_allowed');
        }

        if (!in_array('has_kitchen', $columns, true)) {
            $pdo->exec('ALTER TABLE cabins ADD COLUMN has_kitchen TINYINT(1) NOT NULL DEFAULT 0 AFTER has_parking');
        }

        $indexes = self::tableIndexes($pdo, 'cabins');

        if (!in_array('idx_cabins_external_id', $indexes, true)) {
            $pdo->exec('CREATE INDEX idx_cabins_external_id ON cabins (external_id)');
        }
    }

    private static function tableColumns(PDO $pdo, string $table): array
    {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');

        if ($statement === false) {
            return [];
        }

        $columns = [];

        foreach ($statement->fetchAll() as $row) {
            $columns[] = (string) $row['Field'];
        }

        return $columns;
    }

    private static function tableIndexes(PDO $pdo, string $table): array
    {
        $statement = $pdo->query('SHOW INDEX FROM `' . $table . '`');

        if ($statement === false) {
            return [];
        }

        $indexes = [];

        foreach ($statement->fetchAll() as $row) {
            $indexes[] = (string) $row['Key_name'];
        }

        return array_values(array_unique($indexes));
    }

    private static function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        return strtolower(trim($header));
    }

    private static function mapCsvRow(array $headers, array $row): array
    {
        $result = [];

        foreach ($headers as $index => $header) {
            $result[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }

        return $result;
    }

    private static function cabinDataFromBase44(array $row, int $rowNumber): ?array
    {
        $externalId = self::cleanText($row['id'] ?? '');
        $name = self::cleanText($row['name'] ?? '');
        $number = self::cleanText($row['number'] ?? '');

        if ($externalId === '' && $name === '' && $number === '') {
            return null;
        }

        if ($name === '') {
            $name = 'Domek ' . ($number !== '' ? $number : (string) $rowNumber);
        }

        $sortOrder = self::normalizeInt($number);

        if ($sortOrder < 1) {
            $sortOrder = $rowNumber;
        }

        $shortName = 'D' . (string) $sortOrder;

        return [
            'external_id' => $externalId !== '' ? $externalId : null,
            'name' => $name,
            'short_name' => $shortName,
            'description' => self::cleanText($row['description'] ?? ''),
            'max_guests' => self::positiveInt($row['max_guests'] ?? '', 6),
            'area_sqm' => self::nullablePositiveInt($row['area_sqm'] ?? ''),
            'bedrooms' => self::positiveInt($row['bedrooms'] ?? '', 2),
            'bathrooms' => self::positiveInt($row['bathrooms'] ?? '', 1),
            'price_per_night' => self::moneyInt($row['price_per_night'] ?? '', 0),
            'price_one_night' => self::moneyInt($row['price_1_night'] ?? '', 0),
            'price_two_nights' => self::moneyInt($row['price_2_nights'] ?? '', 0),
            'price_three_nights' => self::moneyInt($row['price_3_nights'] ?? '', 0),
            'price_four_nights' => self::moneyInt($row['price_4_nights'] ?? '', 0),
            'price_five_nights' => self::moneyInt($row['price_5_nights'] ?? '', 0),
            'price_six_nights' => self::moneyInt($row['price_6_nights'] ?? '', 0),
            'price_seven_plus_nights' => self::moneyInt($row['price_7_plus_nights'] ?? '', 0),
            'amenities' => self::cleanText($row['amenities'] ?? ''),
            'location' => self::cleanText($row['location'] ?? ''),
            'cabin_type' => self::cleanText($row['type'] ?? ''),
            'pets_allowed' => self::normalizeBoolean($row['pets_allowed'] ?? '') ? 1 : 0,
            'has_parking' => self::normalizeBoolean($row['has_parking'] ?? '') ? 1 : 0,
            'has_kitchen' => self::normalizeBoolean($row['has_kitchen'] ?? '') ? 1 : 0,
            'is_active' => self::statusIsActive($row['status'] ?? '') ? 1 : 0,
            'sort_order' => $sortOrder,
        ];
    }

    private static function cleanText(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        $value = trim($value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[ \t]+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private static function normalizeInt(string $value): int
    {
        $value = preg_replace('/\D+/', '', $value) ?? '';

        if ($value === '') {
            return 0;
        }

        return (int) $value;
    }

    private static function positiveInt(string $value, int $fallback): int
    {
        $number = self::normalizeInt($value);

        return $number > 0 ? $number : $fallback;
    }

    private static function nullablePositiveInt(string $value): ?int
    {
        $number = self::normalizeInt($value);

        return $number > 0 ? $number : null;
    }

    private static function moneyInt(string $value, int $fallback): int
    {
        $value = str_replace(',', '.', trim($value));

        if ($value === '') {
            return $fallback;
        }

        if (!is_numeric($value)) {
            return $fallback;
        }

        return (int) round((float) $value);
    }

    private static function normalizeBoolean(string $value): bool
    {
        $value = strtolower(trim($value));

        return in_array($value, ['1', 'true', 'yes', 'tak', 't'], true);
    }

    private static function statusIsActive(string $value): bool
    {
        $value = strtolower(trim($value));

        return in_array($value, ['dostepny', 'dostępny', 'active', 'aktywny', '1', 'true'], true);
    }

    private static function findExistingCabin(PDO $pdo, ?string $externalId, string $shortName, string $name): ?array
    {
        if ($externalId !== null && $externalId !== '') {
            $statement = $pdo->prepare('SELECT id FROM cabins WHERE external_id = :external_id LIMIT 1');
            $statement->execute([
                'external_id' => $externalId,
            ]);

            $row = $statement->fetch();

            if (is_array($row)) {
                return $row;
            }
        }

        if ($shortName !== '') {
            $statement = $pdo->prepare('SELECT id FROM cabins WHERE short_name = :short_name LIMIT 1');
            $statement->execute([
                'short_name' => $shortName,
            ]);

            $row = $statement->fetch();

            if (is_array($row)) {
                return $row;
            }
        }

        if ($name !== '') {
            $statement = $pdo->prepare('SELECT id FROM cabins WHERE LOWER(name) = LOWER(:name) LIMIT 1');
            $statement->execute([
                'name' => $name,
            ]);

            $row = $statement->fetch();

            if (is_array($row)) {
                return $row;
            }
        }

        return null;
    }

    private static function insertCabin(PDO $pdo, array $cabin): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO cabins (
                external_id,
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
                :external_id,
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

        $statement->execute($cabin);
    }

    private static function updateCabin(PDO $pdo, int $id, array $cabin): void
    {
        $cabin['id'] = $id;

        $statement = $pdo->prepare(
            'UPDATE cabins
            SET
                external_id = COALESCE(:external_id, external_id),
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

        $statement->execute($cabin);
    }
}