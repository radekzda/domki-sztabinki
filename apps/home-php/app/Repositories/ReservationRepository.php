<?php

declare(strict_types=1);

final class ReservationRepository
{
    /**
     * @return array<int, array{
     *     id: int,
     *     cabin_id: int,
     *     cabin_name: string|null,
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
                reservations.cabin_id,
                cabins.name AS cabin_name,
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
                'cabin_name' => isset($row['cabin_name']) ? (string) $row['cabin_name'] : null,
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

    public static function hasBlockingOverlap(
        int $cabinId,
        string $startDate,
        string $endDate,
        ?int $ignoreReservationId = null
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
            $params['ignore_reservation_id'] = $ignoreReservationId;
        }

        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @param array{
     *     cabin_id: int,
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
        $connection = Database::connection();

        $statement = $connection->prepare(
            'INSERT INTO reservations (
                cabin_id,
                guest_name,
                email,
                phone,
                start_date,
                end_date,
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
                :guest_name,
                :email,
                :phone,
                :start_date,
                :end_date,
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
            'guest_name' => $data['guest_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'nights' => $data['nights'],
            'guests' => $data['guests'],
            'adults' => $data['adults'],
            'children' => $data['children'],
            'status' => $data['status'],
            'source' => $data['source'],
            'payment_status' => $data['payment_status'],
            'total_price' => $data['total_price'],
            'paid_amount' => $data['paid_amount'],
            'notes' => $data['notes'],
        ]);

        return (int) $connection->lastInsertId();
    }
}