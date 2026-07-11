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
}