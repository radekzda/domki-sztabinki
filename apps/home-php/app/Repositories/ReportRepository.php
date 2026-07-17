<?php

declare(strict_types=1);

final class ReportRepository
{
    /**
     * Raport obejmuje rezerwacje, których data rozpoczęcia
     * przypada w wybranym okresie.
     *
     * Rezerwacje anulowane niemuje rezerwacje, których są uwzględniane
     * w podsumowaniu operacyjnym i finansowym.
     *
     * @return array{
     *     reservations_count:int,
     *     total_value:float,
     *     paid_value:float,
     *     remaining_value:float,
     *     nights_count:int,
     *     guests_count:int
     * }
     */
    public static function summary(
        string $dateFrom,
        string $dateTo
    ): array {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                COUNT(*) AS reservations_count,
                COALESCE(
                    SUM(COALESCE(total_price, 0)),
                    0
                ) AS total_value,
                COALESCE(
                    SUM(COALESCE(paid_amount, 0)),
                    0
                ) AS paid_value,
                COALESCE(
                    SUM(
                        GREATEST(
                            COALESCE(total_price, 0)
                            - COALESCE(paid_amount, 0),
                            0
                        )
                    ),
                    0
                ) AS remaining_value,
                COALESCE(
                    SUM(COALESCE(nights, 0)),
                    0
                ) AS nights_count,
                COALESCE(
                    SUM(COALESCE(guests, 0)),
                    0
                ) AS guests_count
            FROM reservations
            WHERE start_date >= :date_from
            AND start_date <= :date_to
            AND status <> "CANCELLED"'
        );

        $statement->execute([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return self::emptySummary();
        }

        return [
            'reservations_count' => (int) (
                $row['reservations_count']
                ?? 0
            ),
            'total_value' => (float) (
                $row['total_value']
                ?? 0
            ),
            'paid_value' => (float) (
                $row['paid_value']
                ?? 0
            ),
            'remaining_value' => (float) (
                $row['remaining_value']
                ?? 0
            ),
            'nights_count' => (int) (
                $row['nights_count']
                ?? 0
            ),
            'guests_count' => (int) (
                $row['guests_count']
                ?? 0
            ),
        ];
    }

    /**
     * @return array{
     *     reservations_count:int,
     *     total_value:float,
     *     paid_value:float,
     *     remaining_value:float,
     *     nights_count:int,
     *     guests_count:int
     * }
     */
    public static function emptySummary(): array
    {
        return [
            'reservations_count' => 0,
            'total_value' => 0.0,
            'paid_value' => 0.0,
            'remaining_value' => 0.0,
            'nights_count' => 0,
            'guests_count' => 0,
        ];
    }
}
