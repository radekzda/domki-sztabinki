<?php

declare(strict_types=1);

final class MessageAutomationService
{
    /**
     * @return array{
     *     templates: int,
     *     found: int,
     *     due: int,
     *     sent: int,
     *     skipped: int,
     *     failed: int
     * }
     */
    public static function process(
        DateTimeImmutable $now,
        bool $dryRun = false
    ): array {
        MessageTemplateRepository::ensureDefaultTemplates();
        AutomaticMessageDeliveryRepository::ensureStructure();

        $templates =
            MessageTemplateRepository::automaticReservationTemplates();

        $settings = SettingsRepository::all();

        $result = [
            'templates' => count($templates),
            'found' => 0,
            'due' => 0,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        if (!$dryRun && !Mailer::isEnabled()) {
            throw new RuntimeException(
                'Wysyłka e-mail jest wyłączona. '
                . 'Ustaw MAIL_ENABLED=true w pliku .env.'
            );
        }

        foreach ($templates as $template) {
            $templateId = (int) ($template['id'] ?? 0);
            $reference = strtoupper(
                trim(
                    (string) (
                        $template['automation_reference']
                        ?? 'ARRIVAL'
                    )
                )
            );
            $offsetDays = (int) (
                $template['automation_offset_days']
                ?? 0
            );
            $sendTime = substr(
                (string) (
                    $template['automation_send_time']
                    ?? '10:00'
                ),
                0,
                5
            );

            if (
                $templateId < 1
                || !in_array(
                    $reference,
                    ['ARRIVAL', 'DEPARTURE'],
                    true
                )
                || preg_match(
                    '/^([01]\d|2[0-3]):[0-5]\d$/',
                    $sendTime
                ) !== 1
            ) {
                $result['failed']++;
                continue;
            }

            $todaySendAt = new DateTimeImmutable(
                $now->format('Y-m-d')
                . ' '
                . $sendTime
                . ':00'
            );

            if ($todaySendAt > $now) {
                continue;
            }

            $referenceDate = $now
                ->modify(
                    sprintf('%+d days', -$offsetDays)
                )
                ->format('Y-m-d');

            $reservations =
                ReservationRepository::automaticMessageCandidates(
                    $reference,
                    $referenceDate,
                    $offsetDays
                );

            $result['found'] += count($reservations);

            foreach ($reservations as $reservation) {
                $reservationId = (int) (
                    $reservation['id']
                    ?? 0
                );
                $recipient = trim(
                    (string) (
                        $reservation['email']
                        ?? ''
                    )
                );

                if (
                    $reservationId < 1
                    || filter_var(
                        $recipient,
                        FILTER_VALIDATE_EMAIL
                    ) === false
                ) {
                    $result['skipped']++;
                    continue;
                }

                $referenceValue = trim(
                    (string) (
                        $reservation[
                            $reference === 'ARRIVAL'
                                ? 'start_date'
                                : 'end_date'
                        ]
                        ?? ''
                    )
                );

                try {
                    $scheduledFor = (
                        new DateTimeImmutable(
                            $referenceValue
                            . ' '
                            . $sendTime
                            . ':00'
                        )
                    )->modify(
                        sprintf('%+d days', $offsetDays)
                    );
                } catch (Throwable $exception) {
                    $result['failed']++;
                    continue;
                }

                if ($scheduledFor > $now) {
                    continue;
                }

                $result['due']++;

                $subjectTemplate = trim(
                    (string) (
                        $template['automation_subject']
                        ?? ''
                    )
                );

                if ($subjectTemplate === '') {
                    $subjectTemplate = (string) (
                        $template['name']
                        ?? 'Wiadomość z Domków Sztabinki'
                    );
                }

                $subject = trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        MessageTemplateRenderer::forReservation(
                            $subjectTemplate,
                            $reservation,
                            $settings
                        )
                    )
                    ?? ''
                );

                $body = MessageTemplateRenderer::forReservation(
                    (string) ($template['content'] ?? ''),
                    $reservation,
                    $settings
                );

                if ($dryRun) {
                    continue;
                }

                $deliveryId =
                    AutomaticMessageDeliveryRepository::claim(
                        $templateId,
                        $reservationId,
                        $scheduledFor->format('Y-m-d H:i:s'),
                        $now
                    );

                if ($deliveryId === null) {
                    $result['skipped']++;
                    continue;
                }

                try {
                    $sent = Mailer::sendSafely(
                        $recipient,
                        $subject,
                        $body
                    );

                    if (!$sent) {
                        throw new RuntimeException(
                            'Mailer zwrócił informację o nieudanej wysyłce.'
                        );
                    }

                    AutomaticMessageDeliveryRepository::markSent(
                        $deliveryId,
                        $recipient,
                        $subject,
                        $now
                    );

                    try {
                        ReservationHistoryRepository::add(
                            $reservationId,
                            'EMAIL_SENT',
                            'Automatycznie wysłano e-mail do gościa',
                            'Szablon: '
                                . (string) ($template['name'] ?? '')
                                . ' · Odbiorca: '
                                . $recipient
                                . ' · Temat: '
                                . $subject
                        );
                    } catch (Throwable $historyException) {
                        error_log(
                            'Nie udało się zapisać historii '
                            . 'automatycznego e-maila: '
                            . $historyException->getMessage()
                        );
                    }

                    $result['sent']++;
                } catch (Throwable $exception) {
                    AutomaticMessageDeliveryRepository::markFailed(
                        $deliveryId,
                        $exception->getMessage()
                    );

                    error_log(
                        'Automatic message error: '
                        . $exception::class
                        . ': '
                        . $exception->getMessage()
                    );

                    $result['failed']++;
                }
            }
        }

        return $result;
    }
}
