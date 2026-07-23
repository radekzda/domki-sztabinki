<?php

declare(strict_types=1);

final class CheckoutAutomationService
{
    /**
     * @return array{
     *     found: int,
     *     checked_out: int,
     *     reminder_found: int,
     *     reminder_sent: int,
     *     reminder_recipient: string|null
     * }
     */
    public static function process(
        DateTimeImmutable $now
    ): array {
        $reservations =
            ReservationRepository::automaticCheckoutCandidates(
                $now
            );

        $checkedOut = 0;

        foreach (
            $reservations
            as $reservation
        ) {
            $reservationId = (int) (
                $reservation['id']
                ?? 0
            );

            if ($reservationId < 1) {
                continue;
            }

            /*
             * setStatus() uruchamia istniejącą logikę:
             * CHECKED_OUT -> status sprzątania domku DIRTY.
             *
             * Rezerwacja wcześniej ręcznie wymeldowana
             * nie trafia do automaticCheckoutCandidates().
             */
            ReservationRepository::setStatus(
                $reservationId,
                'CHECKED_OUT'
            );

            $checkedOut++;
        }

        /*
         * Po wymeldowaniu sprawdzamy wszystkie dzisiejsze
         * rezerwacje CHECKED_OUT bez wystawionej faktury.
         *
         * Dzięki temu wcześniejsze ręczne wymeldowanie
         * także zostanie uwzględnione przy uruchomieniu
         * procesu o 11:00.
         */
        $reminder =
            InvoiceReminderService::sendForDate(
                $now->format(
                    'Y-m-d'
                )
            );

        return [
            'found' =>
                count(
                    $reservations
                ),
            'checked_out' =>
                $checkedOut,
            'reminder_found' =>
                (int) (
                    $reminder['found']
                    ?? 0
                ),
            'reminder_sent' =>
                (int) (
                    $reminder['sent']
                    ?? 0
                ),
            'reminder_recipient' =>
                isset(
                    $reminder['recipient']
                )
                    ? (string) $reminder[
                        'recipient'
                    ]
                    : null,
        ];
    }
}
