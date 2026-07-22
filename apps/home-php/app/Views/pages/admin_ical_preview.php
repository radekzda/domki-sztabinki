<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, mixed> $cabin
 * @var array<int, array<string, mixed>> $rows
 * @var array<string, int> $counts
 * @var string|null $errorMessage
 */

$cabinId = (int) (
    $cabin['id']
    ?? 0
);

$cabinName = (string) (
    $cabin['name']
    ?? ''
);

$source = (string) (
    $cabin['ical_source']
    ?? 'BOOKING'
);

$syncCompleted = (
    isset($_GET['synced'])
    && $_GET['synced'] === '1'
);

$syncTotal = isset($_GET['total'])
    && ctype_digit((string) $_GET['total'])
        ? (int) $_GET['total']
        : 0;

$syncExisting = isset($_GET['existing'])
    && ctype_digit((string) $_GET['existing'])
        ? (int) $_GET['existing']
        : 0;

$syncMatched = isset($_GET['matched'])
    && ctype_digit((string) $_GET['matched'])
        ? (int) $_GET['matched']
        : 0;

$syncConflicts = isset($_GET['conflicts'])
    && ctype_digit((string) $_GET['conflicts'])
        ? (int) $_GET['conflicts']
        : 0;

$syncNewBlocks = isset($_GET['new_blocks'])
    && ctype_digit((string) $_GET['new_blocks'])
        ? (int) $_GET['new_blocks']
        : 0;

$syncDeactivated = isset($_GET['deactivated'])
    && ctype_digit((string) $_GET['deactivated'])
        ? (int) $_GET['deactivated']
        : 0;

$linkCompleted = (
    isset($_GET['linked'])
    && $_GET['linked'] === '1'
);

$linkedReservationId = isset($_GET['reservation_id'])
    && ctype_digit((string) $_GET['reservation_id'])
        ? (int) $_GET['reservation_id']
        : 0;

$actionLabels = [
    'EXISTING_ICAL' =>
        'Już zapisane iCal',
    'MATCH_RESERVATION' =>
        'Pasuje do rezerwacji',
    'CONFLICT' =>
        'Konflikt terminu',
    'NEW_BLOCK' =>
        'Nowa blokada',
];
?>

<style>
    .ical-preview-panel {
        padding: 28px;
    }

    .ical-preview-summary {
        display: grid;
        grid-template-columns:
            repeat(
                4,
                minmax(0, 1fr)
            );
        gap: 10px;
        margin: 20px 0;
    }

    .ical-preview-card {
        padding: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #f8fafc;
    }

    .ical-preview-card span {
        display: block;
        margin-bottom: 5px;
        color: #6b7280;
        font-size: 12px;
        font-weight: 600;
    }

    .ical-preview-card strong {
        color: #111827;
        font-size: 22px;
    }

    .ical-preview-table-wrapper {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .ical-preview-table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
    }

    .ical-preview-table th {
        padding: 12px 14px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #6b7280;
        font-size: 11px;
        text-align: left;
        text-transform: uppercase;
    }

    .ical-preview-table td {
        padding: 13px 14px;
        border-bottom: 1px solid #edf0f2;
        color: #374151;
        font-size: 13px;
        vertical-align: top;
    }

    .ical-preview-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .ical-preview-uid {
        max-width: 260px;
        overflow-wrap: anywhere;
        color: #6b7280;
        font-size: 11px;
    }

    .ical-preview-status {
        display: inline-flex;
        padding: 5px 9px;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 11px;
        font-weight: 700;
    }

    .ical-preview-link-candidate {
        display: grid;
        gap: 8px;
        margin-top: 8px;
        padding: 10px;
        border: 1px solid #d1fae5;
        border-radius: 10px;
        background: #ecfdf5;
    }

    .ical-preview-link-candidate strong {
        color: #065f46;
    }

    .ical-preview-link-candidate form {
        margin: 0;
    }

    .ical-preview-link-candidate .button {
        width: 100%;
        justify-content: center;
    }

    @media (max-width: 900px) {
        .ical-preview-summary {
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
        }
    }

    @media (max-width: 600px) {
        .ical-preview-panel {
            padding: 16px;
        }

        .ical-preview-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php
            View::partial(
                'partials/admin_sidebar',
                [
                    'active' => 'cabins',
                ]
            );
            ?>

            <div class="admin-content">
                <div class="panel ical-preview-panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">
                                iCal
                            </p>

                            <h1>
                                Podgląd synchronizacji
                            </h1>

                            <p>
                                Domek:
                                <strong>
                                    <?= htmlspecialchars(
                                        $cabinName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                · Źródło:
                                <strong>
                                    <?= htmlspecialchars(
                                        $source,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <?php if (
                                trim(
                                    (string) (
                                        $cabin['ical_url']
                                        ?? ''
                                    )
                                ) !== ''
                            ): ?>
                                <form
                                    method="post"
                                    action="/admin/domki/ical-synchronizuj"
                                    style="margin: 0;"
                                >
                                    <?= csrfField() ?>

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= htmlspecialchars(
                                            (string) $cabinId,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                    <button
                                        class="button button--primary"
                                        type="submit"
                                    >
                                        Synchronizuj teraz
                                    </button>
                                </form>
                            <?php endif; ?>

                            <a
                                class="button button--secondary"
                                href="/admin/domki/edytuj?id=<?= htmlspecialchars(
                                    (string) $cabinId,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                Wróć do domku
                            </a>
                        </div>
                    </div>

                    <?php if ($linkCompleted): ?>
                        <div
                            class="alert alert--success"
                            style="margin-bottom: 16px;"
                        >
                            Blokada iCal została powiązana
                            z rezerwacją
                            #<?= htmlspecialchars(
                                (string) $linkedReservationId,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>.
                            Od tej chwili w kalendarzu
                            będzie widoczna jako jedna rezerwacja.
                        </div>
                    <?php endif; ?>

                    <?php if ($syncCompleted): ?>
                        <div
                            class="alert alert--success"
                            style="margin-bottom: 16px;"
                        >
                            <strong>
                                Synchronizacja zakończona.
                            </strong>

                            Pobrano:
                            <?= htmlspecialchars(
                                (string) $syncTotal,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>.

                            Powiązano z rezerwacjami:
                            <?= htmlspecialchars(
                                (string) $syncMatched,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>.

                            Nowe blokady:
                            <?= htmlspecialchars(
                                (string) $syncNewBlocks,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>.

                            Konflikty:
                            <?= htmlspecialchars(
                                (string) $syncConflicts,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>.

                            Dezaktywowano:
                            <?= htmlspecialchars(
                                (string) $syncDeactivated,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>.

                            Już znane:
                            <?= htmlspecialchars(
                                (string) $syncExisting,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>.
                        </div>
                    <?php endif; ?>

                    <div
                        class="alert alert--warning"
                        style="margin-bottom: 18px;"
                    >
                        To jest tylko podgląd.
                        Żadne rezerwacje ani wydarzenia iCal
                        nie są teraz zapisywane.
                    </div>

                    <?php if (
                        $errorMessage !== null
                        && $errorMessage !== ''
                    ): ?>
                        <div class="alert alert--danger">
                            <?= htmlspecialchars(
                                $errorMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    <?php else: ?>
                        <div class="ical-preview-summary">
                            <div class="ical-preview-card">
                                <span>
                                    Wszystkie wydarzenia
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        (string) array_sum(
                                            $counts
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </div>

                            <div class="ical-preview-card">
                                <span>
                                    Pasujące rezerwacje
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $counts[
                                                'MATCH_RESERVATION'
                                            ]
                                            ?? 0
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </div>

                            <div class="ical-preview-card">
                                <span>
                                    Nowe blokady
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $counts[
                                                'NEW_BLOCK'
                                            ]
                                            ?? 0
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </div>

                            <div class="ical-preview-card">
                                <span>
                                    Konflikty
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $counts[
                                                'CONFLICT'
                                            ]
                                            ?? 0
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </div>
                        </div>

                        <?php if ($rows === []): ?>
                            <div class="empty-state">
                                <strong>
                                    Brak wydarzeń
                                </strong>

                                <p>
                                    Pobrany kalendarz iCal
                                    nie zawiera wydarzeń VEVENT.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="ical-preview-table-wrapper">
                                <table class="ical-preview-table">
                                    <thead>
                                        <tr>
                                            <th>Termin</th>
                                            <th>Opis</th>
                                            <th>Klasyfikacja</th>
                                            <th>Powiązanie</th>
                                            <th>UID</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach (
                                            $rows
                                            as $row
                                        ): ?>
                                            <?php
                                            $event = is_array(
                                                $row['event']
                                                ?? null
                                            )
                                                ? $row['event']
                                                : [];

                                            $action = (string) (
                                                $row['action']
                                                ?? ''
                                            );

                                            $matchedReservation =
                                                is_array(
                                                    $row[
                                                        'matched_reservation'
                                                    ]
                                                    ?? null
                                                )
                                                    ? $row[
                                                        'matched_reservation'
                                                    ]
                                                    : null;

                                            $conflictingReservation =
                                                is_array(
                                                    $row[
                                                        'conflicting_reservation'
                                                    ]
                                                    ?? null
                                                )
                                                    ? $row[
                                                        'conflicting_reservation'
                                                    ]
                                                    : null;

                                            $existingIcalEvent =
                                                is_array(
                                                    $row[
                                                        'existing_ical_event'
                                                    ]
                                                    ?? null
                                                )
                                                    ? $row[
                                                        'existing_ical_event'
                                                    ]
                                                    : null;

                                            $linkCandidateReservation =
                                                is_array(
                                                    $row[
                                                        'link_candidate_reservation'
                                                    ]
                                                    ?? null
                                                )
                                                    ? $row[
                                                        'link_candidate_reservation'
                                                    ]
                                                    : null;

                                            $existingIcalEventId =
                                                (int) (
                                                    $existingIcalEvent[
                                                        'id'
                                                    ]
                                                    ?? 0
                                                );
                                            ?>

                                            <tr>
                                                <td>
                                                    <strong>
                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $event[
                                                                    'start_date'
                                                                ]
                                                                ?? ''
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </strong>

                                                    <br>

                                                    do

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $event[
                                                                'end_date'
                                                            ]
                                                            ?? ''
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $event[
                                                                'summary'
                                                            ]
                                                            ?? '—'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </td>

                                                <td>
                                                    <span class="ical-preview-status">
                                                        <?= htmlspecialchars(
                                                            $actionLabels[
                                                                $action
                                                            ]
                                                            ?? $action,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <?php if (
                                                        $matchedReservation
                                                        !== null
                                                    ): ?>
                                                        Rezerwacja
                                                        #<?= htmlspecialchars(
                                                            (string) (
                                                                $matchedReservation[
                                                                    'id'
                                                                ]
                                                                ?? ''
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                        <br>

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $matchedReservation[
                                                                    'guest_name'
                                                                ]
                                                                ?? ''
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    <?php elseif (
                                                        $conflictingReservation
                                                        !== null
                                                    ): ?>
                                                        Konflikt z
                                                        rezerwacją
                                                        #<?= htmlspecialchars(
                                                            (string) (
                                                                $conflictingReservation[
                                                                    'id'
                                                                ]
                                                                ?? ''
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                        <?php if (
                                                            $linkCandidateReservation
                                                            !== null
                                                        ): ?>
                                                            <div class="ical-preview-link-candidate">
                                                                <strong>
                                                                    Ten sam domek i dokładnie ten sam termin.
                                                                </strong>

                                                                <span>
                                                                    <?= htmlspecialchars(
                                                                        (string) (
                                                                            $linkCandidateReservation[
                                                                                'guest_name'
                                                                            ]
                                                                            ?? 'Gość'
                                                                        ),
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>
                                                                    ·
                                                                    <?= htmlspecialchars(
                                                                        sourceLabelForDisplay(
                                                                            (string) (
                                                                                $linkCandidateReservation[
                                                                                    'source'
                                                                                ]
                                                                                ?? ''
                                                                            )
                                                                        ),
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>
                                                                </span>

                                                                <?php if (
                                                                    $existingIcalEventId
                                                                    > 0
                                                                ): ?>
                                                                    <form
                                                                        method="post"
                                                                        action="/admin/domki/ical-powiaz-rezerwacje"
                                                                        onsubmit="return confirm('Powiązać tę blokadę iCal z istniejącą rezerwacją #<?= htmlspecialchars(
                                                                            (string) (
                                                                                $linkCandidateReservation[
                                                                                    'id'
                                                                                ]
                                                                                ?? ''
                                                                            ),
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        ) ?>? Źródło rezerwacji zostanie ustawione zgodnie ze źródłem iCal.');"
                                                                    >
                                                                        <?= csrfField() ?>

                                                                        <input
                                                                            type="hidden"
                                                                            name="ical_event_id"
                                                                            value="<?= htmlspecialchars(
                                                                                (string) $existingIcalEventId,
                                                                                ENT_QUOTES,
                                                                                'UTF-8'
                                                                            ) ?>"
                                                                        >

                                                                        <input
                                                                            type="hidden"
                                                                            name="reservation_id"
                                                                            value="<?= htmlspecialchars(
                                                                                (string) (
                                                                                    $linkCandidateReservation[
                                                                                        'id'
                                                                                    ]
                                                                                    ?? ''
                                                                                ),
                                                                                ENT_QUOTES,
                                                                                'UTF-8'
                                                                            ) ?>"
                                                                        >

                                                                        <button
                                                                            class="button button--primary"
                                                                            type="submit"
                                                                        >
                                                                            Powiąż z istniejącą rezerwacją
                                                                        </button>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <span>
                                                                        Najpierw kliknij „Synchronizuj teraz”.
                                                                        Po zapisaniu blokady pojawi się możliwość powiązania.
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>

                                                <td class="ical-preview-uid">
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $event[
                                                                'uid'
                                                            ]
                                                            ?? ''
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
