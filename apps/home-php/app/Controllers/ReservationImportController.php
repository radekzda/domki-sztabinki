<?php

declare(strict_types=1);

final class ReservationImportController
{
    public static function show(): void
    {
        Response::html(View::render('pages/admin_reservations_import', [
            'title' => 'Import rezerwacji',
            'result' => null,
            'errorMessage' => null,
        ]));
    }

    public static function store(): void
    {
        if (!Database::canAttemptConnection()) {
            Response::html(View::render('pages/admin_reservations_import', [
                'title' => 'Import rezerwacji',
                'result' => null,
                'errorMessage' => 'Baza danych nie jest jeszcze skonfigurowana.',
            ]));

            return;
        }

        if (!isset($_FILES['csv_file']) || !is_array($_FILES['csv_file'])) {
            Response::html(View::render('pages/admin_reservations_import', [
                'title' => 'Import rezerwacji',
                'result' => null,
                'errorMessage' => 'Nie wybrano pliku CSV.',
            ]));

            return;
        }

        $file = $_FILES['csv_file'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::html(View::render('pages/admin_reservations_import', [
                'title' => 'Import rezerwacji',
                'result' => null,
                'errorMessage' => 'Nie udało się wczytać pliku CSV. Kod błędu: ' . (string) ($file['error'] ?? 'brak'),
            ]));

            return;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            Response::html(View::render('pages/admin_reservations_import', [
                'title' => 'Import rezerwacji',
                'result' => null,
                'errorMessage' => 'Przesłany plik jest nieprawidłowy.',
            ]));

            return;
        }

        try {
            $pdo = Database::connection();
            self::ensureReservationsImportColumns($pdo);
            $result = self::importCsv($pdo, $tmpName);

            Response::html(View::render('pages/admin_reservations_import', [
                'title' => 'Import rezerwacji',
                'result' => $result,
                'errorMessage' => null,
            ]));
        } catch (Throwable $exception) {
            Response::html(View::render('pages/admin_reservations_import', [
                'title' => 'Import rezerwacji',
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

        $requiredColumns = [
            'id',
            'room_id',
            'guest_id',
            'check_in',
            'check_out',
            'adults_count',
            'children_count',
            'total_price',
            'paid_amount',
            'status',
            'payment_status',
            'source',
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
                $reservation = self::reservationDataFromBase44($pdo, $csvRow, $rowNumber);

                if ($reservation === null) {
                    $skipped++;
                    continue;
                }

                $existingReservation = self::findExistingReservation(
                    $pdo,
                    $reservation['external_id']
                );

                if ($existingReservation !== null) {
                    self::updateReservation($pdo, (int) $existingReservation['id'], $reservation);
                    $updated++;

                    continue;
                }

                self::insertReservation($pdo, $reservation);
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

    private static function ensureReservationsImportColumns(PDO $pdo): void
    {
        $columns = self::tableColumns($pdo, 'reservations');

        if (!in_array('external_id', $columns, true)) {
            $pdo->exec('ALTER TABLE reservations ADD COLUMN external_id VARCHAR(80) NULL AFTER id');
        }

        $indexes = self::tableIndexes($pdo, 'reservations');

        if (!in_array('idx_reservations_external_id', $indexes, true)) {
            $pdo->exec('CREATE INDEX idx_reservations_external_id ON reservations (external_id)');
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

    private static function reservationDataFromBase44(PDO $pdo, array $row, int $rowNumber): ?array
    {
        $externalId = self::cleanText($row['id'] ?? '');
        $roomExternalId = self::cleanText($row['room_id'] ?? '');
        $guestExternalId = self::cleanText($row['guest_id'] ?? '');
        $cabinName = self::cleanText(self::valueFromAnyKey($row, ['domek (nazwa)', 'domek', 'cabin_name', 'room_name']));
        $guestName = self::cleanText(self::valueFromAnyKey($row, ['gość (imię)', 'gosc (imie)', 'gość', 'gosc', 'guest_name', 'ordered_by']));

        $startDate = self::normalizeDate($row['check_in'] ?? '');
        $endDate = self::normalizeDate($row['check_out'] ?? '');

        if ($externalId === '' || $startDate === null || $endDate === null) {
            return null;
        }

        $cabin = self::findCabinForReservation($pdo, $roomExternalId, $cabinName);

        if ($cabin === null) {
            throw new RuntimeException('Nie znaleziono domku dla rezerwacji ' . $externalId . ' / ' . $cabinName);
        }

        $guest = self::findGuestForReservation($pdo, $guestExternalId);

        if ($guestName === '' && $guest !== null) {
            $guestName = trim((string) $guest['first_name'] . ' ' . (string) $guest['last_name']);
        }

        if ($guestName === '') {
            $guestName = 'Gość Base44';
        }

        $nameParts = preg_split('/\s+/', $guestName) ?: [];
        $firstName = $nameParts[0] ?? null;
        $lastNameParts = array_slice($nameParts, 1);
        $lastName = implode(' ', $lastNameParts);

        if ($lastName === '') {
            $lastName = null;
        }

        $email = $guest !== null && trim((string) ($guest['email'] ?? '')) !== ''
            ? strtolower(trim((string) $guest['email']))
            : 'brak-email-rezerwacja-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $externalId) . '@base44.local';

        $phone = $guest !== null && trim((string) ($guest['phone'] ?? '')) !== ''
            ? trim((string) $guest['phone'])
            : null;

        $adults = self::positiveInt($row['adults_count'] ?? '', 1);
        $children = self::nonNegativeInt($row['children_count'] ?? '', 0);
        $guests = max(1, $adults + $children);
        $nights = self::calculateNights($startDate, $endDate);
        $totalPrice = self::moneyValue($row['total_price'] ?? '');
        $paidAmount = self::moneyValue($row['paid_amount'] ?? '');
        $pricePerNight = $nights > 0 && $totalPrice !== null
            ? round($totalPrice / $nights, 2)
            : null;

        $notesParts = [];

        $specialRequests = self::cleanText($row['special_requests'] ?? '');

        if ($specialRequests !== '') {
            $notesParts[] = $specialRequests;
        }

        $orderedBy = self::cleanText($row['ordered_by'] ?? '');

        if ($orderedBy !== '') {
            $notesParts[] = 'Zamawiający: ' . $orderedBy;
        }

        $createdBy = self::cleanText($row['created_by'] ?? '');

        if ($createdBy !== '') {
            $notesParts[] = 'Utworzył w Base44: ' . $createdBy;
        }

        return [
            'external_id' => $externalId,
            'cabin_id' => (int) $cabin['id'],
            'guest_id' => $guest !== null ? (int) $guest['id'] : null,
            'guest_name' => $guestName,
            'email' => $email,
            'phone' => $phone,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'check_in_at' => self::combineDateAndTime($startDate, $row['check_in_time'] ?? ''),
            'check_out_at' => self::combineDateAndTime($endDate, $row['check_out_time'] ?? ''),
            'nights' => $nights,
            'price_per_night' => $pricePerNight,
            'guests' => $guests,
            'adults' => $adults,
            'children' => $children,
            'status' => self::mapStatus($row['status'] ?? ''),
            'source' => self::mapSource($row['source'] ?? ''),
            'payment_status' => self::mapPaymentStatus($row['payment_status'] ?? ''),
            'total_price' => $totalPrice,
            'paid_amount' => $paidAmount,
            'street' => null,
            'postal_code' => null,
            'city' => $guest['city'] ?? null,
            'country' => $guest['country'] ?? null,
            'notes' => $notesParts !== [] ? implode("\n", $notesParts) : null,
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

    private static function valueFromAnyKey(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return (string) $row[$key];
            }
        }

        return '';
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

    private static function combineDateAndTime(string $date, string $time): ?string
    {
        $time = trim($time);

        if ($date === '') {
            return null;
        }

        if ($time === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{1,2}:\d{2}/', $time)) {
                return (new DateTimeImmutable($date . ' ' . $time))->format('Y-m-d H:i:s');
            }

            return (new DateTimeImmutable($time))->format('Y-m-d H:i:s');
        } catch (Throwable $exception) {
            return null;
        }
    }

    private static function positiveInt(string $value, int $fallback): int
    {
        $number = self::nonNegativeInt($value, 0);

        return $number > 0 ? $number : $fallback;
    }

    private static function nonNegativeInt(string $value, int $fallback): int
    {
        $value = preg_replace('/\D+/', '', trim($value)) ?? '';

        if ($value === '') {
            return $fallback;
        }

        return max(0, (int) $value);
    }

    private static function moneyValue(string $value): ?float
    {
        $value = str_replace(',', '.', trim($value));

        if ($value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private static function calculateNights(string $startDate, string $endDate): int
    {
        try {
            $start = new DateTimeImmutable($startDate);
            $end = new DateTimeImmutable($endDate);
            $days = (int) $start->diff($end)->days;

            return max(1, $days);
        } catch (Throwable $exception) {
            return 1;
        }
    }

    private static function mapStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'confirmed', 'potwierdzona', 'potwierdzony' => 'CONFIRMED',
            'checked_in', 'check_in', 'zameldowany' => 'CHECKED_IN',
            'checked_out', 'completed', 'wymeldowany' => 'CHECKED_OUT',
            'cancelled', 'canceled', 'anulowana', 'anulowany' => 'CANCELLED',
            default => 'PENDING',
        };
    }

    private static function mapPaymentStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'paid', 'opłacona', 'oplacona' => 'PAID',
            'partial', 'partially_paid', 'częściowa', 'czesciowa' => 'PARTIAL',
            'refunded', 'zwrócona', 'zwrocona' => 'REFUNDED',
            default => 'PENDING',
        };
    }

    private static function mapSource(string $source): string
    {
        $source = strtolower(trim($source));

        return match ($source) {
            'booking_com', 'booking.com', 'booking' => 'BOOKING',
            'airbnb' => 'AIRBNB',
            'www', 'website', 'strona' => 'WWW',
            'phone', 'telefon' => 'PHONE',
            default => 'MANUAL',
        };
    }

    private static function findCabinForReservation(PDO $pdo, string $roomExternalId, string $cabinName): ?array
    {
        if ($roomExternalId !== '') {
            $statement = $pdo->prepare('SELECT id FROM cabins WHERE external_id = :external_id LIMIT 1');
            $statement->execute([
                'external_id' => $roomExternalId,
            ]);

            $row = $statement->fetch();

            if (is_array($row)) {
                return $row;
            }
        }

        if ($cabinName !== '') {
            $statement = $pdo->prepare('SELECT id FROM cabins WHERE LOWER(name) = LOWER(:name) LIMIT 1');
            $statement->execute([
                'name' => $cabinName,
            ]);

            $row = $statement->fetch();

            if (is_array($row)) {
                return $row;
            }

            $number = preg_replace('/\D+/', '', $cabinName) ?? '';

            if ($number !== '') {
                $shortName = 'D' . (string) ((int) $number);

                $statement = $pdo->prepare('SELECT id FROM cabins WHERE short_name = :short_name LIMIT 1');
                $statement->execute([
                    'short_name' => $shortName,
                ]);

                $row = $statement->fetch();

                if (is_array($row)) {
                    return $row;
                }
            }
        }

        return null;
    }

    private static function findGuestForReservation(PDO $pdo, string $guestExternalId): ?array
    {
        if ($guestExternalId === '') {
            return null;
        }

        $statement = $pdo->prepare(
            'SELECT
                id,
                first_name,
                last_name,
                email,
                phone,
                city,
                country
            FROM guests
            WHERE external_id = :external_id
            LIMIT 1'
        );

        $statement->execute([
            'external_id' => $guestExternalId,
        ]);

        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    private static function findExistingReservation(PDO $pdo, string $externalId): ?array
    {
        $statement = $pdo->prepare('SELECT id FROM reservations WHERE external_id = :external_id LIMIT 1');
        $statement->execute([
            'external_id' => $externalId,
        ]);

        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    private static function insertReservation(PDO $pdo, array $reservation): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO reservations (
                external_id,
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
                :external_id,
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

        $statement->execute($reservation);
    }

    private static function updateReservation(PDO $pdo, int $id, array $reservation): void
    {
        $reservation['id'] = $id;

        $statement = $pdo->prepare(
            'UPDATE reservations
            SET
                external_id = :external_id,
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

        $statement->execute($reservation);
    }
}