<?php

declare(strict_types=1);

final class GuestImportController
{
    public static function show(): void
    {
        Response::html(View::render('pages/admin_guests_import', [
            'title' => 'Import gości',
            'result' => null,
            'errorMessage' => null,
        ]));
    }

    public static function store(): void
    {
        if (!Database::canAttemptConnection()) {
            Response::html(View::render('pages/admin_guests_import', [
                'title' => 'Import gości',
                'result' => null,
                'errorMessage' => 'Baza danych nie jest jeszcze skonfigurowana.',
            ]));

            return;
        }

        if (!isset($_FILES['csv_file']) || !is_array($_FILES['csv_file'])) {
            Response::html(View::render('pages/admin_guests_import', [
                'title' => 'Import gości',
                'result' => null,
                'errorMessage' => 'Nie wybrano pliku CSV.',
            ]));

            return;
        }

        $file = $_FILES['csv_file'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::html(View::render('pages/admin_guests_import', [
                'title' => 'Import gości',
                'result' => null,
                'errorMessage' => 'Nie udało się wczytać pliku CSV. Kod błędu: ' . (string) ($file['error'] ?? 'brak'),
            ]));

            return;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            Response::html(View::render('pages/admin_guests_import', [
                'title' => 'Import gości',
                'result' => null,
                'errorMessage' => 'Przesłany plik jest nieprawidłowy.',
            ]));

            return;
        }

        try {
            $pdo = Database::connection();
            self::ensureGuestsImportColumns($pdo);
            $result = self::importCsv($pdo, $tmpName);

            Response::html(View::render('pages/admin_guests_import', [
                'title' => 'Import gości',
                'result' => $result,
                'errorMessage' => null,
            ]));
        } catch (Throwable $exception) {
            Response::html(View::render('pages/admin_guests_import', [
                'title' => 'Import gości',
                'result' => null,
                'errorMessage' => 'Import przerwany: ' . $exception->getMessage(),
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
                $guest = self::guestDataFromBase44($csvRow, $rowNumber);

                if ($guest === null) {
                    $skipped++;
                    continue;
                }

                $existingGuest = self::findExistingGuest(
                    $pdo,
                    $guest['external_id'],
                    $guest['email']
                );

                if ($existingGuest !== null) {
                    self::updateGuest($pdo, (int) $existingGuest['id'], $guest);
                    $updated++;

                    continue;
                }

                self::insertGuest($pdo, $guest);
                $inserted++;
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            fclose($handle);

            throw new RuntimeException('Błąd w okolicy wiersza ' . $rowNumber . ': ' . $exception->getMessage());
        }

        fclose($handle);

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $inserted + $updated + $skipped,
        ];
    }

    private static function ensureGuestsImportColumns(PDO $pdo): void
    {
        $columns = self::tableColumns($pdo, 'guests');

        if (!in_array('external_id', $columns, true)) {
            $pdo->exec('ALTER TABLE guests ADD COLUMN external_id VARCHAR(80) NULL AFTER id');
        }

        if (!in_array('full_address', $columns, true)) {
            $pdo->exec('ALTER TABLE guests ADD COLUMN full_address VARCHAR(255) NULL AFTER city');
        }

        if (!in_array('pesel', $columns, true)) {
            $pdo->exec('ALTER TABLE guests ADD COLUMN pesel VARCHAR(30) NULL AFTER full_address');
        }

        if (!in_array('document_number', $columns, true)) {
            $pdo->exec('ALTER TABLE guests ADD COLUMN document_number VARCHAR(80) NULL AFTER pesel');
        }

        if (!in_array('nationality', $columns, true)) {
            $pdo->exec('ALTER TABLE guests ADD COLUMN nationality VARCHAR(120) NULL AFTER document_number');
        }

        if (!in_array('birth_date', $columns, true)) {
            $pdo->exec('ALTER TABLE guests ADD COLUMN birth_date DATE NULL AFTER nationality');
        }

        $indexes = self::tableIndexes($pdo, 'guests');

        if (!in_array('idx_guests_external_id', $indexes, true)) {
            $pdo->exec('CREATE INDEX idx_guests_external_id ON guests (external_id)');
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

    private static function guestDataFromBase44(array $row, int $rowNumber): ?array
    {
        $externalId = self::cleanText($row['id'] ?? '');
        $firstName = self::cleanText($row['first_name'] ?? '');
        $lastName = self::cleanText($row['last_name'] ?? '');
        $email = strtolower(self::cleanText($row['email'] ?? ''));
        $phone = self::cleanText($row['phone'] ?? '');
        $fullAddress = self::cleanText($row['address'] ?? '');
        $pesel = self::normalizePesel($row['pesel'] ?? '');
        $documentNumber = self::cleanText($row['id_document'] ?? '');
        $nationality = self::cleanText($row['nationality'] ?? '');
        $birthDate = self::normalizeDate($row['date_of_birth'] ?? '');
        $notes = self::cleanText($row['notes'] ?? '');
        $isVip = self::normalizeBoolean($row['vip_status'] ?? '') ? 1 : 0;

        if ($externalId === '' && $email === '') {
            return null;
        }

        if ($firstName === '') {
            $firstName = 'Gość';
        }

        if ($lastName === '') {
            $lastName = '—';
        }

        if ($email === '') {
            $safeId = $externalId !== '' ? $externalId : (string) $rowNumber;
            $email = 'brak-email-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $safeId) . '@base44.local';
        }

        return [
            'external_id' => $externalId !== '' ? $externalId : null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'country' => $nationality !== '' ? $nationality : null,
            'full_address' => $fullAddress !== '' ? $fullAddress : null,
            'pesel' => $pesel,
            'document_number' => $documentNumber !== '' ? $documentNumber : null,
            'nationality' => $nationality !== '' ? $nationality : null,
            'birth_date' => $birthDate,
            'is_vip' => $isVip,
            'notes' => $notes !== '' ? $notes : null,
            'source' => 'BASE44',
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

    private static function normalizePesel(string $value): ?string
    {
        $value = preg_replace('/\D+/', '', $value) ?? '';

        return $value !== '' ? $value : null;
    }

    private static function normalizeDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->format('Y-m-d');
        } catch (Throwable $exception) {
            return null;
        }
    }

    private static function normalizeBoolean(string $value): bool
    {
        $value = strtolower(trim($value));

        return in_array($value, ['1', 'true', 'yes', 'tak', 'vip'], true);
    }

    private static function findExistingGuest(PDO $pdo, ?string $externalId, string $email): ?array
    {
        if ($externalId !== null && $externalId !== '') {
            $statement = $pdo->prepare('SELECT id FROM guests WHERE external_id = :external_id LIMIT 1');
            $statement->execute([
                'external_id' => $externalId,
            ]);

            $row = $statement->fetch();

            if (is_array($row)) {
                return $row;
            }
        }

        if ($email !== '') {
            $statement = $pdo->prepare('SELECT id FROM guests WHERE email = :email LIMIT 1');
            $statement->execute([
                'email' => $email,
            ]);

            $row = $statement->fetch();

            if (is_array($row)) {
                return $row;
            }
        }

        return null;
    }

    private static function insertGuest(PDO $pdo, array $guest): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO guests (
                external_id,
                first_name,
                last_name,
                email,
                phone,
                country,
                full_address,
                pesel,
                document_number,
                nationality,
                birth_date,
                is_vip,
                notes,
                source
            ) VALUES (
                :external_id,
                :first_name,
                :last_name,
                :email,
                :phone,
                :country,
                :full_address,
                :pesel,
                :document_number,
                :nationality,
                :birth_date,
                :is_vip,
                :notes,
                :source
            )'
        );

        $statement->execute($guest);
    }

    private static function updateGuest(PDO $pdo, int $id, array $guest): void
    {
        $guest['id'] = $id;

        $statement = $pdo->prepare(
            'UPDATE guests
            SET
                external_id = COALESCE(:external_id, external_id),
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone,
                country = :country,
                full_address = :full_address,
                pesel = :pesel,
                document_number = :document_number,
                nationality = :nationality,
                birth_date = :birth_date,
                is_vip = :is_vip,
                notes = :notes,
                source = :source
            WHERE id = :id'
        );

        $statement->execute($guest);
    }
}