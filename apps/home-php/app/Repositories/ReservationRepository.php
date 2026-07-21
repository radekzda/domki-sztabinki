<?php

declare(strict_types=1);

final class ReservationRepository
{
    /**
     * @return array<int, array{
     *     id: int,
     *     cabin_id: int,
     *     guest_id: int|null,
     *     cabin_name: string|null,
     *     linked_guest_name: string|null,
     *     guest_name: string,
     *     email: string,
     *     phone: string|null,
     *     start_date: string,
     *     end_date: string,
     *     nights: int,
     *     guests: int,
     *     adults: int,
     *     children: int,
     *     status: string,
     *     source: string,
     *     payment_status: string|null,
     *     total_price: string|null,
     *     paid_amount: string|null,
     *     created_at: string
     * }>
     */
    public static function all(): array
    {
        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT
                reservations.id,
                reservations.external_id,
                reservations.cabin_id,
                reservations.guest_id,
                cabins.name AS cabin_name,
                CONCAT(guests.first_name, " ", guests.last_name) AS linked_guest_name,
                  guests.full_address AS linked_guest_address,
                  guests.phone AS linked_guest_phone,
                reservations.guest_name,
                reservations.email,
                reservations.phone,
                reservations.first_name,
                reservations.last_name,
                reservations.street,
                reservations.postal_code,
                reservations.city,
                reservations.country,
                reservations.start_date,
                reservations.end_date,
                reservations.check_in_at,
                reservations.check_out_at,
                reservations.nights,
                reservations.guests,
                reservations.adults,
                reservations.children,
                reservations.status,
                reservations.source,
                reservations.payment_status,
                reservations.total_price,
                reservations.paid_amount,
                  reservations.street,
                  reservations.postal_code,
                  reservations.city,
                  reservations.country,
                reservations.created_at
            FROM reservations
            LEFT JOIN cabins ON cabins.id = reservations.cabin_id
            LEFT JOIN guests ON guests.id = reservations.guest_id
            ORDER BY reservations.start_date DESC, reservations.id DESC'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'cabin_id' => (int) ($row['cabin_id'] ?? 0),
                'guest_id' => isset($row['guest_id']) ? (int) $row['guest_id'] : null,
                'cabin_name' => isset($row['cabin_name']) ? (string) $row['cabin_name'] : null,
                'linked_guest_name' => isset($row['linked_guest_name']) ? (string) $row['linked_guest_name'] : null,
                  'linked_guest_address' => isset($row['linked_guest_address']) ? (string) $row['linked_guest_address'] : null,
                  'linked_guest_phone' => isset($row['linked_guest_phone']) ? (string) $row['linked_guest_phone'] : null,
                'guest_name' => (string) ($row['guest_name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'phone' => isset($row['phone']) ? (string) $row['phone'] : null,
                'start_date' => (string) ($row['start_date'] ?? ''),
                'end_date' => (string) ($row['end_date'] ?? ''),
                  'check_in_at' => isset($row['check_in_at']) ? (string) $row['check_in_at'] : null,
                  'check_out_at' => isset($row['check_out_at']) ? (string) $row['check_out_at'] : null,
                'nights' => (int) ($row['nights'] ?? 0),
                'guests' => (int) ($row['guests'] ?? 0),
                'adults' => (int) ($row['adults'] ?? 0),
                'children' => (int) ($row['children'] ?? 0),
                'status' => (string) ($row['status'] ?? ''),
                'source' => (string) ($row['source'] ?? ''),
                'payment_status' => isset($row['payment_status']) ? (string) $row['payment_status'] : null,
                'total_price' => isset($row['total_price']) ? (string) $row['total_price'] : null,
                'paid_amount' => isset($row['paid_amount']) ? (string) $row['paid_amount'] : null,
                  'street' => isset($row['street']) ? (string) $row['street'] : null,
                  'postal_code' => isset($row['postal_code']) ? (string) $row['postal_code'] : null,
                  'city' => isset($row['city']) ? (string) $row['city'] : null,
                  'country' => isset($row['country']) ? (string) $row['country'] : null,
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }, $rows);
    }

    /**
     * @return array{
     *     id: int,
     *     cabin_id: int,
     *     guest_id: int|null,
     *     cabin_name: string|null,
     *     linked_guest_name: string|null,
     *     guest_name: string,
     *     email: string,
     *     phone: string|null,
     *     start_date: string,
     *     end_date: string,
     *     nights: int,
     *     guests: int,
     *     adults: int,
     *     children: int,
     *     status: string,
     *     source: string,
     *     payment_status: string|null,
     *     total_price: string|null,
     *     paid_amount: string|null,
     *     notes: string|null,
     *     created_at: string
     * }|null
     */
    public static function find(int $id): ?array
    {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                reservations.id,
                reservations.external_id,
                reservations.cabin_id,
                reservations.guest_id,
                cabins.name AS cabin_name,
                CONCAT(guests.first_name, " ", guests.last_name) AS linked_guest_name,
                reservations.guest_name,
                reservations.email,
                reservations.phone,
                reservations.first_name,
                reservations.last_name,
                reservations.street,
                reservations.postal_code,
                reservations.city,
                reservations.country,
                reservations.start_date,
                reservations.end_date,
                reservations.check_in_at,
                reservations.check_out_at,
                reservations.nights,
                reservations.guests,
                reservations.adults,
                reservations.children,
                reservations.status,
                reservations.source,
                reservations.payment_status,
                reservations.total_price,
                reservations.paid_amount,
                reservations.notes,
                reservations.created_at
            FROM reservations
            LEFT JOIN cabins ON cabins.id = reservations.cabin_id
            LEFT JOIN guests ON guests.id = reservations.guest_id
            WHERE reservations.id = :id
            LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
              'external_id' => isset($row['external_id']) ? (string) $row['external_id'] : null,
            'cabin_id' => (int) ($row['cabin_id'] ?? 0),
            'guest_id' => isset($row['guest_id']) ? (int) $row['guest_id'] : null,
            'cabin_name' => isset($row['cabin_name']) ? (string) $row['cabin_name'] : null,
            'linked_guest_name' => isset($row['linked_guest_name']) ? (string) $row['linked_guest_name'] : null,
            'guest_name' => (string) ($row['guest_name'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'phone' => isset($row['phone']) ? (string) $row['phone'] : null,
            'first_name' => isset($row['first_name'])
                ? (string) $row['first_name']
                : null,
            'last_name' => isset($row['last_name'])
                ? (string) $row['last_name']
                : null,
            'street' => isset($row['street'])
                ? (string) $row['street']
                : null,
            'postal_code' => isset($row['postal_code'])
                ? (string) $row['postal_code']
                : null,
            'city' => isset($row['city'])
                ? (string) $row['city']
                : null,
            'country' => isset($row['country'])
                ? (string) $row['country']
                : null,
            'start_date' => (string) ($row['start_date'] ?? ''),
            'end_date' => (string) ($row['end_date'] ?? ''),
              'check_in_at' => isset($row['check_in_at']) ? (string) $row['check_in_at'] : null,
              'check_out_at' => isset($row['check_out_at']) ? (string) $row['check_out_at'] : null,
            'nights' => (int) ($row['nights'] ?? 0),
            'guests' => (int) ($row['guests'] ?? 0),
            'adults' => (int) ($row['adults'] ?? 0),
            'children' => (int) ($row['children'] ?? 0),
            'status' => (string) ($row['status'] ?? ''),
            'source' => (string) ($row['source'] ?? ''),
            'payment_status' => isset($row['payment_status']) ? (string) $row['payment_status'] : null,
            'total_price' => isset($row['total_price']) ? (string) $row['total_price'] : null,
            'paid_amount' => isset($row['paid_amount']) ? (string) $row['paid_amount'] : null,
            'notes' => isset($row['notes']) ? (string) $row['notes'] : null,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     cabin_id: int,
     *     guest_id: int|null,
     *     cabin_name: string|null,
     *     linked_guest_name: string|null,
     *     guest_name: string,
     *     email: string,
     *     phone: string|null,
     *     start_date: string,
     *     end_date: string,
     *     nights: int,
     *     guests: int,
     *     adults: int,
     *     children: int,
     *     status: string,
     *     source: string,
     *     payment_status: string|null,
     *     total_price: string|null,
     *     paid_amount: string|null,
     *     created_at: string
     * }>
     */
    public static function forGuest(int $guestId): array
    {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                reservations.id,
                reservations.cabin_id,
                reservations.guest_id,
                cabins.name AS cabin_name,
                CONCAT(guests.first_name, " ", guests.last_name) AS linked_guest_name,
                reservations.guest_name,
                reservations.email,
                reservations.phone,
                reservations.start_date,
                reservations.end_date,
                reservations.nights,
                reservations.guests,
                reservations.adults,
                reservations.children,
                reservations.status,
                reservations.source,
                reservations.payment_status,
                reservations.total_price,
                reservations.paid_amount,
                reservations.created_at
            FROM reservations
            LEFT JOIN cabins ON cabins.id = reservations.cabin_id
            LEFT JOIN guests ON guests.id = reservations.guest_id
            WHERE reservations.guest_id = :guest_id
            ORDER BY reservations.start_date DESC, reservations.id DESC'
        );

        $statement->execute([
            'guest_id' => $guestId,
        ]);

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'cabin_id' => (int) ($row['cabin_id'] ?? 0),
                'guest_id' => isset($row['guest_id']) ? (int) $row['guest_id'] : null,
                'cabin_name' => isset($row['cabin_name']) ? (string) $row['cabin_name'] : null,
                'linked_guest_name' => isset($row['linked_guest_name']) ? (string) $row['linked_guest_name'] : null,
                'guest_name' => (string) ($row['guest_name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'phone' => isset($row['phone']) ? (string) $row['phone'] : null,
                'start_date' => (string) ($row['start_date'] ?? ''),
                'end_date' => (string) ($row['end_date'] ?? ''),
                'nights' => (int) ($row['nights'] ?? 0),
                'guests' => (int) ($row['guests'] ?? 0),
                'adults' => (int) ($row['adults'] ?? 0),
                'children' => (int) ($row['children'] ?? 0),
                'status' => (string) ($row['status'] ?? ''),
                'source' => (string) ($row['source'] ?? ''),
                'payment_status' => isset($row['payment_status']) ? (string) $row['payment_status'] : null,
                'total_price' => isset($row['total_price']) ? (string) $row['total_price'] : null,
                'paid_amount' => isset($row['paid_amount']) ? (string) $row['paid_amount'] : null,
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }, $rows);
    }

    /**
     * Rezerwacje z wymeldowaniem w podanym dniu,
     * dla których nie wystawiono jeszcze faktury.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function departuresWithoutInvoiceForDate(
        string $date
    ): array {
        $dateObject = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        if (
            $dateObject === false
            || $dateObject->format('Y-m-d') !== $date
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowa data przypomnienia o fakturach.'
            );
        }

        $statement = Database::connection()->prepare(
            'SELECT
                reservations.id,
                reservations.guest_name,
                reservations.end_date,
                reservations.total_price,
                reservations.status,
                cabins.name AS cabin_name
            FROM reservations
            LEFT JOIN cabins
                ON cabins.id = reservations.cabin_id
            WHERE reservations.end_date = :end_date
            AND reservations.status <> "CANCELLED"
            AND NOT EXISTS (
                SELECT 1
                FROM invoices
                WHERE invoices.reservation_id = reservations.id
            )
            ORDER BY
                cabins.name ASC,
                reservations.id ASC'
        );

        $statement->execute([
            'end_date' => $date,
        ]);

        $rows = $statement->fetchAll();

        return is_array($rows)
            ? $rows
            : [];
    }
    /**
     * Rezerwacje PMS eksportowane do zewnętrznego kalendarza.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forIcalExport(
        int $cabinId
    ): array {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                cabin_id,
                start_date,
                end_date,
                status
            FROM reservations
            WHERE cabin_id = :cabin_id
            AND status IN (
                "PENDING",
                "CONFIRMED",
                "CHECKED_IN"
            )
            AND end_date > CURDATE()
            ORDER BY start_date ASC, id ASC'
        );

        $statement->execute([
            'cabin_id' => $cabinId,
        ]);

        $rows = $statement->fetchAll();

        return is_array($rows)
            ? $rows
            : [];
    }

    public static function hasBlockingOverlap(
        int $cabinId,
        string $startDate,
        string $endDate,
        ?int $ignoreReservationId = null,
        ?int $ignoreIcalEventId = null
    ): bool {
        $connection = Database::connection();

        $sql = 'SELECT COUNT(*)
            FROM reservations
            WHERE cabin_id = :cabin_id
            AND status IN ("PENDING", "CONFIRMED", "CHECKED_IN")
            AND start_date < :end_date
            AND end_date > :start_date';

        $params = [
            'cabin_id' => $cabinId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($ignoreReservationId !== null) {
            $sql .= ' AND id <> :ignore_reservation_id';

            $params['ignore_reservation_id'] =
                $ignoreReservationId;
        }

        $statement = $connection->prepare(
            $sql
        );

        $statement->execute(
            $params
        );

        if (
            (int) $statement->fetchColumn() > 0
        ) {
            return true;
        }

        return IcalEventRepository::hasBlockingOverlap(
            $cabinId,
            $startDate,
            $endDate,
            $ignoreReservationId,
            $ignoreIcalEventId
        );
    }

    /**
     * @param array{
     *     cabin_id: int,
     *     guest_id: int|null,
     *     guest_name: string,
     *     email: string,
     *     phone: string|null,
     *     start_date: string,
     *     end_date: string,
     *     nights: int,
     *     guests: int,
     *     adults: int,
     *     children: int,
     *     status: string,
     *     source: string,
     *     payment_status: string,
     *     total_price: int,
     *     paid_amount: int,
     *     notes: string|null
     * } $data
     */
    public static function create(array $data): int
    {
        $data['guests'] = self::normalizeGuestCount($data);

        self::assertGuestCapacity(
            (int) ($data['cabin_id'] ?? 0),
            (int) $data['guests']
        );

        $paymentState = self::normalizePaymentState(
            $data['total_price'] ?? 0,
            $data['paid_amount'] ?? 0,
            $data['payment_status'] ?? 'PENDING'
        );

        $connection = Database::connection();

        $statement = $connection->prepare(
            'INSERT INTO reservations (
                cabin_id,
                guest_id,
                guest_name,
                first_name,
                last_name,
                email,
                phone,
                street,
                postal_code,
                city,
                country,
                start_date,
                end_date,
                check_in_at,
                check_out_at,
                nights,
                guests,
                adults,
                children,
                status,
                source,
                payment_status,
                total_price,
                paid_amount,
                notes
            ) VALUES (
                :cabin_id,
                :guest_id,
                :guest_name,
                :first_name,
                :last_name,
                :email,
                :phone,
                :street,
                :postal_code,
                :city,
                :country,
                :start_date,
                :end_date,
                :check_in_at,
                :check_out_at,
                :nights,
                :guests,
                :adults,
                :children,
                :status,
                :source,
                :payment_status,
                :total_price,
                :paid_amount,
                :notes
            )'
        );

        $statement->execute([
            'cabin_id' => $data['cabin_id'],
            'guest_id' => $data['guest_id'],
            'guest_name' => $data['guest_name'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'street' => $data['street'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'check_in_at' => $data['check_in_at'] ?? null,
            'check_out_at' => $data['check_out_at'] ?? null,
            'nights' => $data['nights'],
            'guests' => $data['guests'],
            'adults' => $data['adults'],
            'children' => $data['children'],
            'status' => $data['status'],
            'source' => $data['source'],
            'payment_status' =>
                $paymentState['payment_status'],
            'total_price' =>
                $paymentState['total_price'],
            'paid_amount' =>
                $paymentState['paid_amount'],
            'notes' => $data['notes'],
        ]);

        $reservationId = (int) $connection->lastInsertId();

        try {
            $details = [
                'Termin: '
                    . $data['start_date']
                    . ' — '
                    . $data['end_date'],
                'Źródło: '
                    . $data['source'],
                'Cena pobytu: '
                    . number_format(
                        (float) $data['total_price'],
                        0,
                        ',',
                        ' '
                    )
                    . ' zł',
            ];

            ReservationHistoryRepository::add(
                $reservationId,
                'CREATE',
                'Utworzono rezerwację',
                implode(
                    "\n",
                    $details
                ),
                null,
                (string) $data['status']
            );
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się zapisać historii utworzenia rezerwacji #'
                . $reservationId
                . ': '
                . $exception->getMessage()
            );
        }

        self::syncCleaningForStatusTransition(
            $reservationId,
            null,
            (string) $data['status'],
            null,
            (int) $data['cabin_id']
        );

        return $reservationId;
    }

    /**
     * @param array{
     *     cabin_id: int,
     *     guest_id: int|null,
     *     guest_name: string,
     *     email: string,
     *     phone: string|null,
     *     start_date: string,
     *     end_date: string,
     *     nights: int,
     *     guests: int,
     *     adults: int,
     *     children: int,
     *     status: string,
     *     source: string,
     *     payment_status: string,
     *     total_price: int,
     *     paid_amount: int,
     *     notes: string|null
     * } $data
     */
    public static function update(int $id, array $data): void
    {
        $reservationBefore = self::find($id);

        $data['guests'] = self::normalizeGuestCount($data);

        self::assertGuestCapacity(
            (int) ($data['cabin_id'] ?? 0),
            (int) $data['guests']
        );

        $paymentState = self::normalizePaymentState(
            $data['total_price'] ?? 0,
            $data['paid_amount'] ?? 0,
            $data['payment_status'] ?? 'PENDING'
        );

        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE reservations
            SET
                cabin_id = :cabin_id,
                guest_id = :guest_id,
                guest_name = :guest_name,
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone,
                street = :street,
                postal_code = :postal_code,
                city = :city,
                country = :country,
                start_date = :start_date,
                end_date = :end_date,
                check_in_at = :check_in_at,
                check_out_at = :check_out_at,
                nights = :nights,
                guests = :guests,
                adults = :adults,
                children = :children,
                status = :status,
                source = :source,
                payment_status = :payment_status,
                total_price = :total_price,
                paid_amount = :paid_amount,
                notes = :notes
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'cabin_id' => $data['cabin_id'],
            'guest_id' => $data['guest_id'],
            'guest_name' => $data['guest_name'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'street' => $data['street'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'check_in_at' => $data['check_in_at'] ?? null,
            'check_out_at' => $data['check_out_at'] ?? null,
            'nights' => $data['nights'],
            'guests' => $data['guests'],
            'adults' => $data['adults'],
            'children' => $data['children'],
            'status' => $data['status'],
            'source' => $data['source'],
            'payment_status' =>
                $paymentState['payment_status'],
            'total_price' =>
                $paymentState['total_price'],
            'paid_amount' =>
                $paymentState['paid_amount'],
            'notes' => $data['notes'],
        ]);

        $reservationAfter = self::find($id);

        if (
            $reservationBefore === null
            || $reservationAfter === null
        ) {
            return;
        }

        self::syncCleaningForStatusTransition(
            $id,
            (string) ($reservationBefore['status'] ?? ''),
            (string) ($reservationAfter['status'] ?? ''),
            (int) ($reservationBefore['cabin_id'] ?? 0),
            (int) ($reservationAfter['cabin_id'] ?? 0)
        );

        $changes = self::describeReservationChanges(
            $reservationBefore,
            $reservationAfter
        );

        if ($changes === []) {
            return;
        }

        try {
            ReservationHistoryRepository::add(
                $id,
                'EDIT',
                'Zmieniono dane rezerwacji',
                implode(
                    "\n",
                    $changes
                )
            );
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się zapisać historii edycji rezerwacji #'
                . $id
                . ': '
                . $exception->getMessage()
            );
        }
    }

    private static function normalizeGuestCount(
        array $data
    ): int {
        $adults = (int) ($data['adults'] ?? 0);
        $children = (int) ($data['children'] ?? 0);

        if ($adults < 1) {
            throw new InvalidArgumentException(
                'Liczba dorosłych musi być większa od zera.'
            );
        }

        if ($children < 0) {
            throw new InvalidArgumentException(
                'Liczba dzieci nie może być ujemna.'
            );
        }

        return $adults + $children;
    }

    private static function assertGuestCapacity(
        int $cabinId,
        int $guestCount
    ): void {
        if ($cabinId < 1) {
            throw new InvalidArgumentException(
                'Wybrany domek nie istnieje.'
            );
        }

        $cabin = CabinRepository::find($cabinId);

        if ($cabin === null) {
            throw new InvalidArgumentException(
                'Wybrany domek nie istnieje.'
            );
        }

        $maxGuests = (int) ($cabin['max_guests'] ?? 0);

        if (
            $maxGuests > 0
            && $guestCount > $maxGuests
        ) {
            throw new InvalidArgumentException(
                'Wybrany domek może pomieścić maksymalnie '
                . $maxGuests
                . ' osób. Podano '
                . $guestCount
                . '.'
            );
        }
    }

    /**
     * @return array{
     *     total_price: string,
     *     paid_amount: string,
     *     payment_status: string
     * }
     */
    private static function normalizePaymentState(
        mixed $totalPriceValue,
        mixed $paidAmountValue,
        mixed $requestedStatusValue
    ): array {
        $totalPrice = round(
            (float) str_replace(
                ',',
                '.',
                (string) $totalPriceValue
            ),
            2
        );

        $paidAmount = round(
            (float) str_replace(
                ',',
                '.',
                (string) $paidAmountValue
            ),
            2
        );

        if ($totalPrice < 0) {
            throw new InvalidArgumentException(
                'Cena pobytu nie może być ujemna.'
            );
        }

        if ($paidAmount < 0) {
            throw new InvalidArgumentException(
                'Kwota wpłacona nie może być ujemna.'
            );
        }

        if ($paidAmount > $totalPrice) {
            throw new InvalidArgumentException(
                'Kwota wpłacona nie może być większa '
                . 'od ceny pobytu.'
            );
        }

        $requestedStatus = strtoupper(
            trim(
                (string) $requestedStatusValue
            )
        );

        if ($requestedStatus === 'REFUNDED') {
            $paymentStatus = 'REFUNDED';
        } elseif ($paidAmount <= 0) {
            $paymentStatus = 'PENDING';
        } elseif (
            $totalPrice > 0
            && $paidAmount >= $totalPrice
        ) {
            $paymentStatus = 'PAID';
        } else {
            $paymentStatus = 'PARTIAL';
        }

        return [
            'total_price' => number_format(
                $totalPrice,
                2,
                '.',
                ''
            ),
            'paid_amount' => number_format(
                $paidAmount,
                2,
                '.',
                ''
            ),
            'payment_status' => $paymentStatus,
        ];
    }

    private static function syncCleaningForStatusTransition(
        int $reservationId,
        ?string $oldStatus,
        string $newStatus,
        ?int $oldCabinId,
        int $newCabinId
    ): void {
        $normalizedOldStatus = $oldStatus !== null
            ? strtoupper(trim($oldStatus))
            : null;

        $normalizedNewStatus = strtoupper(
            trim($newStatus)
        );

        $enteredCheckedOut =
            $normalizedNewStatus === 'CHECKED_OUT'
            && $normalizedOldStatus !== 'CHECKED_OUT';

        $changedCabinWhileCheckedOut =
            $normalizedNewStatus === 'CHECKED_OUT'
            && $normalizedOldStatus === 'CHECKED_OUT'
            && $oldCabinId !== null
            && $oldCabinId > 0
            && $oldCabinId !== $newCabinId;

        if (
            $newCabinId <= 0
            || (
                !$enteredCheckedOut
                && !$changedCabinWhileCheckedOut
            )
        ) {
            return;
        }

        try {
            CabinRepository::setCleaningStatus(
                $newCabinId,
                'DIRTY'
            );
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się ustawić statusu sprzątania domku #'
                . $newCabinId
                . ' po wymeldowaniu z rezerwacji #'
                . $reservationId
                . ': '
                . $exception->getMessage()
            );
        }
    }

    public static function setStatus(int $id, string $status): void
    {
        $reservationBefore = self::find($id);

        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE reservations
            SET status = :status
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'status' => $status,
        ]);

        $oldStatus = $reservationBefore !== null
            ? (string) (
                $reservationBefore['status']
                ?? ''
            )
            : '';

        $cabinId = $reservationBefore !== null
            ? (int) (
                $reservationBefore['cabin_id']
                ?? 0
            )
            : 0;

        self::syncCleaningForStatusTransition(
            $id,
            $oldStatus !== '' ? $oldStatus : null,
            $status,
            $cabinId > 0 ? $cabinId : null,
            $cabinId
        );

        if ($oldStatus === $status) {
            return;
        }

        $title = match ($status) {
            'CONFIRMED' => 'Rezerwacja została potwierdzona',
            'CHECKED_IN' => 'Gość został zameldowany',
            'CHECKED_OUT' => 'Gość został wymeldowany',
            'CANCELLED' => 'Rezerwacja została anulowana',
            default => 'Zmieniono status rezerwacji',
        };

        try {
            ReservationHistoryRepository::add(
                $id,
                'STATUS',
                $title,
                null,
                $oldStatus !== ''
                    ? $oldStatus
                    : null,
                $status
            );
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się zapisać historii statusu rezerwacji #'
                . $id
                . ': '
                . $exception->getMessage()
            );
        }
    }


    public static function addPayment(int $id, int $amount): void
    {
        $reservationBefore = self::find($id);

        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE reservations
             SET paid_amount = LEAST(COALESCE(total_price, 0), COALESCE(paid_amount, 0) + :amount_for_paid_amount),
                 payment_status = CASE
                     WHEN COALESCE(total_price, 0) <= 0 THEN payment_status
                     WHEN COALESCE(paid_amount, 0) + :amount_for_status >= COALESCE(total_price, 0) THEN "PAID"
                     ELSE "PARTIAL"
                 END
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'amount_for_paid_amount' => $amount,
            'amount_for_status' => $amount,
        ]);

        $reservationAfter = self::find($id);

        $oldPaidAmount = $reservationBefore !== null
            ? (float) (
                $reservationBefore['paid_amount']
                ?? 0
            )
            : 0;

        $newPaidAmount = $reservationAfter !== null
            ? (float) (
                $reservationAfter['paid_amount']
                ?? 0
            )
            : $oldPaidAmount + $amount;

        try {
            ReservationHistoryRepository::add(
                $id,
                'PAYMENT',
                'Dodano wpłatę',
                'Zarejestrowano wpłatę '
                    . number_format(
                        $amount,
                        0,
                        ',',
                        ' '
                    )
                    . ' zł. Łącznie wpłacono: '
                    . number_format(
                        $newPaidAmount,
                        0,
                        ',',
                        ' '
                    )
                    . ' zł.',
                number_format(
                    $oldPaidAmount,
                    2,
                    '.',
                    ''
                ),
                number_format(
                    $newPaidAmount,
                    2,
                    '.',
                    ''
                ),
                (float) $amount
            );
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się zapisać historii wpłaty rezerwacji #'
                . $id
                . ': '
                . $exception->getMessage()
            );
        }
    }


    public static function markPaid(int $id): void
    {
        $reservationBefore = self::find($id);

        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE reservations
             SET paid_amount = COALESCE(total_price, paid_amount, 0),
                 payment_status = "PAID"
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $oldPaymentStatus = $reservationBefore !== null
            ? (string) (
                $reservationBefore['payment_status']
                ?? ''
            )
            : '';

        if ($oldPaymentStatus === 'PAID') {
            return;
        }

        try {
            ReservationHistoryRepository::add(
                $id,
                'PAYMENT_STATUS',
                'Rezerwacja została oznaczona jako opłacona',
                null,
                $oldPaymentStatus !== ''
                    ? $oldPaymentStatus
                    : null,
                'PAID'
            );
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się zapisać historii płatności rezerwacji #'
                . $id
                . ': '
                . $exception->getMessage()
            );
        }
    }

    public static function setPaymentStatus(int $id, string $paymentStatus): void
    {
        $reservationBefore = self::find($id);

        if ($reservationBefore === null) {
            throw new RuntimeException(
                'Nie znaleziono rezerwacji.'
            );
        }

        $paymentStatus = strtoupper(
            trim($paymentStatus)
        );

        if (
            !in_array(
                $paymentStatus,
                [
                    'PENDING',
                    'PARTIAL',
                    'PAID',
                    'REFUNDED',
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowy status płatności.'
            );
        }

        if ($paymentStatus === 'PAID') {
            self::markPaid($id);

            return;
        }

        $totalPrice = round(
            (float) (
                $reservationBefore['total_price']
                ?? 0
            ),
            2
        );

        $currentPaidAmount = round(
            (float) (
                $reservationBefore['paid_amount']
                ?? 0
            ),
            2
        );

        if ($paymentStatus === 'PENDING') {
            $paidAmount = 0.0;
        } elseif ($paymentStatus === 'PARTIAL') {
            if (
                $totalPrice <= 0
                || $currentPaidAmount <= 0
                || $currentPaidAmount >= $totalPrice
            ) {
                throw new InvalidArgumentException(
                    'Status Częściowa wymaga kwoty wpłaconej '
                    . 'większej od 0 zł i mniejszej od ceny pobytu.'
                );
            }

            $paidAmount = $currentPaidAmount;
        } else {
            // REFUNDED pozostaje ręcznym statusem.
            // Kwotę wpłat zachowujemy do czasu wdrożenia
            // osobnej obsługi zwrotów.
            $paidAmount = min(
                max(0.0, $currentPaidAmount),
                max(0.0, $totalPrice)
            );
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE reservations
            SET
                payment_status = :payment_status,
                paid_amount = :paid_amount
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'payment_status' => $paymentStatus,
            'paid_amount' => number_format(
                $paidAmount,
                2,
                '.',
                ''
            ),
        ]);

        $oldPaymentStatus = (string) (
            $reservationBefore['payment_status']
            ?? ''
        );

        $oldPaidAmount = round(
            (float) (
                $reservationBefore['paid_amount']
                ?? 0
            ),
            2
        );

        if (
            $oldPaymentStatus === $paymentStatus
            && $oldPaidAmount === $paidAmount
        ) {
            return;
        }

        try {
            ReservationHistoryRepository::add(
                $id,
                'PAYMENT_STATUS',
                'Zmieniono status płatności',
                'Wpłacono: '
                    . number_format(
                        $paidAmount,
                        2,
                        ',',
                        ' '
                    )
                    . ' zł.',
                $oldPaymentStatus !== ''
                    ? $oldPaymentStatus
                    : null,
                $paymentStatus
            );
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się zapisać historii statusu płatności rezerwacji #'
                . $id
                . ': '
                . $exception->getMessage()
            );
        }
    }
    public static function cancel(int $id): void
    {
        self::setStatus($id, 'CANCELLED');
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array<int, string>
     */
    private static function describeReservationChanges(
        array $before,
        array $after
    ): array {
        $fields = [
            'cabin_id' => 'Domek',
            'guest_name' => 'Gość',
            'first_name' => 'Imię',
            'last_name' => 'Nazwisko',
            'email' => 'E-mail',
            'phone' => 'Telefon',
            'street' => 'Ulica i numer',
            'postal_code' => 'Kod pocztowy',
            'city' => 'Miejscowość',
            'country' => 'Kraj',
            'start_date' => 'Data przyjazdu',
            'end_date' => 'Data wyjazdu',
            'check_in_at' => 'Godzina przyjazdu',
            'check_out_at' => 'Godzina wyjazdu',
            'nights' => 'Liczba nocy',
            'guests' => 'Liczba osób',
            'adults' => 'Dorośli',
            'children' => 'Dzieci',
            'status' => 'Status',
            'source' => 'Źródło',
            'payment_status' => 'Status płatności',
            'total_price' => 'Cena pobytu',
            'paid_amount' => 'Wpłacono',
            'notes' => 'Notatki',
        ];

        $changes = [];

        foreach ($fields as $field => $label) {
            $oldValue = self::historyComparableValue(
                $before[$field] ?? null
            );

            $newValue = self::historyComparableValue(
                $after[$field] ?? null
            );

            if ($oldValue === $newValue) {
                continue;
            }

            $changes[] = $label
                . ': '
                . self::historyDisplayValue(
                    $field,
                    $oldValue
                )
                . ' → '
                . self::historyDisplayValue(
                    $field,
                    $newValue
                );
        }

        return $changes;
    }

    private static function historyComparableValue(
        mixed $value
    ): string {
        if ($value === null) {
            return '';
        }

        return trim(
            (string) $value
        );
    }

    private static function historyDisplayValue(
        string $field,
        string $value
    ): string {
        if ($value === '') {
            return '—';
        }

        $statusLabels = [
            'PENDING' => 'Oczekuje',
            'CONFIRMED' => 'Potwierdzona',
            'CHECKED_IN' => 'Zameldowany',
            'CHECKED_OUT' => 'Wymeldowany',
            'CANCELLED' => 'Anulowana',
        ];

        $paymentLabels = [
            'PENDING' => 'Oczekuje',
            'PAID' => 'Opłacona',
            'PARTIAL' => 'Częściowa',
            'REFUNDED' => 'Zwrócona',
        ];

        if ($field === 'status') {
            return $statusLabels[$value]
                ?? $value;
        }

        if ($field === 'payment_status') {
            return $paymentLabels[$value]
                ?? $value;
        }

        if (
            $field === 'total_price'
            || $field === 'paid_amount'
        ) {
            return is_numeric($value)
                ? number_format(
                    (float) $value,
                    0,
                    ',',
                    ' '
                ) . ' zł'
                : $value;
        }

        return $value;
    }

    public static function delete(int $id): void
    {
        if ($id < 1) {
            return;
        }

        ReservationHistoryRepository::ensureTable();

        $connection = Database::connection();

        $connection->beginTransaction();

        try {
            ReservationHistoryRepository::deleteForReservation(
                $id
            );

            $statement = $connection->prepare(
                'DELETE FROM reservations
                WHERE id = :id'
            );

            $statement->execute([
                'id' => $id,
            ]);

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }
}
