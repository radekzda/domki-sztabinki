<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array<string, mixed>> $todayArrivals
 * @var array<int, array<string, mixed>> $todayDepartures
 * @var array<int, array<string, mixed>> $checkedInReservations
 * @var array<int, array<string, mixed>> $newInquiries
 * @var array<int, array<string, mixed>> $upcomingReservations
 * @var array<int, array<string, mixed>> $cleaningCabins
 * @var string|null $databaseMessage
 */

$reservationGuestName = static function (
    array $reservation
): string {
    $guestName = trim(
        (string) (
            $reservation['guest_name']
            ?? $reservation['linked_guest_name']
            ?? ''
        )
    );

    return $guestName !== ''
        ? $guestName
        : 'Gość';
};

$reservationCabinName = static function (
    array $reservation
): string {
    $cabinName = trim(
        (string) (
            $reservation['cabin_name']
            ?? ''
        )
    );

    return $cabinName !== ''
        ? $cabinName
        : 'Domek';
};

$inquiryGuestName = static function (
    array $inquiry
): string {
    $fullName = trim(
        (string) (
            $inquiry['full_name']
            ?? ''
        )
    );

    if ($fullName !== '') {
        return $fullName;
    }

    $firstName = trim(
        (string) (
            $inquiry['first_name']
            ?? ''
        )
    );

    $lastName = trim(
        (string) (
            $inquiry['last_name']
            ?? ''
        )
    );

    $guestName = trim(
        $firstName . ' ' . $lastName
    );

    return $guestName !== ''
        ? $guestName
        : 'Gość';
};
?>
<style>
    .dashboard-panel {
        padding: 28px;
    }

    .dashboard-panel .page-header {
        margin-bottom: 24px;
    }

    .dashboard-panel .page-header h1 {
        margin-bottom: 6px;
    }

    /*
     * Górne liczniki
     */
    .dashboard-today-grid {
        display: grid;
        grid-template-columns: repeat(
            6,
            minmax(0, 1fr)
        );
        gap: 12px;
        margin-top: 0;
        margin-bottom: 20px;
    }

    .dashboard-today-card {
        position: relative;
        min-width: 0;
        min-height: 74px;
        padding: 14px 16px;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.04);
        color: #111827;
        text-decoration: none;
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            border-color 0.15s ease;
    }

    .dashboard-today-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
    }

    .dashboard-today-card:nth-child(1)::before {
        background: #3b82f6;
    }

    .dashboard-today-card:nth-child(2)::before {
        background: #f97316;
    }

    .dashboard-today-card:nth-child(3)::before {
        background: #22c55e;
    }

    .dashboard-today-card:nth-child(4)::before {
        background: #a855f7;
    }

    .dashboard-today-card:nth-child(5)::before {
        background: #eab308;
    }

    .dashboard-today-card:nth-child(6)::before {
        background: #ef4444;
    }

    .dashboard-today-card:hover {
        transform: translateY(-2px);
        border-color: #d1d5db;
        box-shadow:
            0 4px 8px rgba(15, 23, 42, 0.04),
            0 12px 28px rgba(15, 23, 42, 0.08);
    }

    .dashboard-today-card strong {
        display: block;
        flex-shrink: 0;
        order: 2;
        font-size: 26px;
        line-height: 1;
        font-weight: 750;
        letter-spacing: -0.02em;
        color: #111827;
    }

    .dashboard-today-card span {
        display: block;
        min-width: 0;
        order: 1;
        font-size: 13px;
        line-height: 1.2;
        font-weight: 600;
        color: #6b7280;
    }

    /*
     * Dolne sekcje 3 x 2
     */
    .dashboard-today-sections {
        display: grid;
        grid-template-columns: repeat(
            3,
            minmax(0, 1fr)
        );
        gap: 16px;
        align-items: stretch;
        margin-top: 0;
    }

    .dashboard-today-section {
        min-width: 0;
        min-height: 210px;
        padding: 0;
        margin: 0;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-top-width: 3px;
        border-radius: 14px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.035);
    }

    .dashboard-today-section:nth-child(1) {
        border-top-color: #3b82f6;
    }

    .dashboard-today-section:nth-child(2) {
        border-top-color: #f97316;
    }

    .dashboard-today-section:nth-child(3) {
        border-top-color: #22c55e;
    }

    .dashboard-today-section:nth-child(4) {
        border-top-color: #a855f7;
    }

    .dashboard-today-section:nth-child(5) {
        border-top-color: #eab308;
    }

    .dashboard-today-section:nth-child(6) {
        border-top-color: #ef4444;
    }

    /*
     * Nagłówki kart
     */
    .dashboard-today-section__header {
        min-height: 60px;
        padding: 14px 18px 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        gap: 6px;
        border-bottom: 1px solid #f0f1f3;
    }

    .dashboard-today-section__header strong {
        display: block;
        margin: 0;
        font-size: 16px;
        line-height: 1.08;
        font-weight: 700;
        color: #111827;
    }

    .dashboard-today-section__header p {
        display: block;
        margin: 0;
        font-size: 13px;
        line-height: 1.45;
        color: #9ca3af;
    }

    /*
     * Listy wewnątrz kart
     */
    .dashboard-today-section .status-list {
        margin: 0;
        padding: 12px;
        display: grid;
        gap: 8px;
    }

    .dashboard-today-section .status-row {
        min-width: 0;
        padding: 11px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        border: 1px solid #edf0f2;
        border-radius: 10px;
        background: #fafafa;
        color: #374151;
        text-decoration: none;
        transition:
            background 0.15s ease,
            border-color 0.15s ease,
            transform 0.15s ease;
    }

    a.dashboard-today-section .status-row:hover,
    .dashboard-today-section a.status-row:hover {
        background: #f5f7f9;
        border-color: #dfe3e8;
        transform: translateY(-1px);
    }

    .dashboard-today-section .status-row span {
        min-width: 0;
        display: block;
        font-size: 13px;
        line-height: 1.4;
        font-weight: 500;
        color: #6b7280;
        overflow-wrap: anywhere;
    }

    .dashboard-today-section .status-row strong {
        min-width: 0;
        display: block;
        font-size: 13px;
        line-height: 1.4;
        font-weight: 700;
        text-align: right;
        color: #111827;
        overflow-wrap: anywhere;
    }

    /*
     * Puste karty
     */
    .dashboard-today-section__header:has(p) {
        border-bottom-color: transparent;
    }

    .dashboard-today-section__header p {
        max-width: 280px;
    }

    /*
     * Sprzątanie
     */
    .dashboard-cleaning-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .dashboard-cleaning-row__info {
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 7px;
    }

    .dashboard-cleaning-row__info strong {
        width: fit-content;
        padding: 4px 8px;
        border-radius: 999px;
        background: #fee2e2;
        color: #b91c1c;
        font-size: 11px;
        font-weight: 700;
        text-align: left;
    }

    .dashboard-cleaning-row form {
        flex-shrink: 0;
        margin: 0;
    }

    .dashboard-cleaning-row .button {
        min-height: 34px;
        padding-left: 13px;
        padding-right: 13px;
        white-space: nowrap;
        border-radius: 8px;
        font-size: 12px;
    }

    /*
     * Delikatne akcenty kolorystyczne nagłówków
     */
    .dashboard-today-section:nth-child(1)
    .dashboard-today-section__header strong {
        color: #1d4ed8;
    }

    .dashboard-today-section:nth-child(2)
    .dashboard-today-section__header strong {
        color: #c2410c;
    }

    .dashboard-today-section:nth-child(3)
    .dashboard-today-section__header strong {
        color: #15803d;
    }

    .dashboard-today-section:nth-child(4)
    .dashboard-today-section__header strong {
        color: #7e22ce;
    }

    .dashboard-today-section:nth-child(5)
    .dashboard-today-section__header strong {
        color: #a16207;
    }

    .dashboard-today-section:nth-child(6)
    .dashboard-today-section__header strong {
        color: #b91c1c;
    }

    /*
     * Responsive
     */
    @media (max-width: 1300px) {
        .dashboard-today-grid {
            grid-template-columns: repeat(
                3,
                minmax(0, 1fr)
            );
        }
    }

    @media (max-width: 1050px) {
        .dashboard-today-sections {
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
        }
    }

    @media (max-width: 750px) {
        .dashboard-panel {
            padding: 20px;
        }

        .dashboard-today-grid {
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
        }

        .dashboard-today-sections {
            grid-template-columns: 1fr;
        }

        .dashboard-today-section {
            min-height: 0;
        }
    }

    @media (max-width: 480px) {
        .dashboard-panel {
            padding: 16px;
        }

        .dashboard-today-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-cleaning-row {
            align-items: stretch;
            flex-direction: column;
        }

        .dashboard-cleaning-row form,
        .dashboard-cleaning-row .button {
            width: 100%;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial(
                'partials/admin_sidebar',
                ['active' => 'dashboard']
            ); ?>

            <div class="admin-content">
                <div class="panel dashboard-panel">
                    <?php if (
                        is_string($databaseMessage)
                        && $databaseMessage !== ''
                    ): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars(
                                $databaseMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <div class="dashboard-today-grid">
                        <a
                            class="dashboard-card dashboard-today-card"
                            href="/admin/rezerwacje"
                        >
                            <strong>
                                <?= count($todayArrivals) ?>
                            </strong>

                            <span>
                                Przyjazdy dzisiaj
                            </span>
                        </a>

                        <a
                            class="dashboard-card dashboard-today-card"
                            href="/admin/rezerwacje"
                        >
                            <strong>
                                <?= count($todayDepartures) ?>
                            </strong>

                            <span>
                                Wyjazdy dzisiaj
                            </span>
                        </a>

                        <a
                            class="dashboard-card dashboard-today-card"
                            href="/admin/rezerwacje"
                        >
                            <strong>
                                <?= count($checkedInReservations) ?>
                            </strong>

                            <span>
                                Aktualnie zameldowani
                            </span>
                        </a>

                        <a
                            class="dashboard-card dashboard-today-card"
                            href="/admin/zapytania"
                        >
                            <strong>
                                <?= count($newInquiries) ?>
                            </strong>

                            <span>
                                Nowe zapytania
                            </span>
                        </a>

                        <a
                            class="dashboard-card dashboard-today-card"
                            href="/admin/rezerwacje"
                        >
                            <strong>
                                <?= count($upcomingReservations) ?>
                            </strong>

                            <span>
                                Przyjazdy w ciągu 7 dni
                            </span>
                        </a>

                        <a
                            class="dashboard-card dashboard-today-card"
                            href="/admin/domki"
                        >
                            <strong>
                                <?= count($cleaningCabins) ?>
                            </strong>

                            <span>
                                Sprzątanie
                            </span>
                        </a>
                    </div>

                    <div class="dashboard-today-sections">
                    <div class="empty-state dashboard-today-section">
                        <div class="dashboard-today-section__header">
                            <strong>
                                Przyjazdy dzisiaj
                            </strong>

                            <?php if ($todayArrivals === []): ?>
                                <p>
                                    Brak zaplanowanych przyjazdów na dziś.
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($todayArrivals !== []): ?>
                            <div class="status-list">
                                <?php foreach (
                                    $todayArrivals
                                    as $reservation
                                ): ?>
                                    <a
                                        class="status-row"
                                        href="/admin/rezerwacje/pokaz?id=<?= (int) (
                                            $reservation['id']
                                            ?? 0
                                        ) ?>"
                                    >
                                        <span>
                                            <?= htmlspecialchars(
                                                $reservationCabinName(
                                                    $reservation
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $reservationGuestName(
                                                    $reservation
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="empty-state dashboard-today-section">
                        <div class="dashboard-today-section__header">
                            <strong>
                                Wyjazdy dzisiaj
                            </strong>

                            <?php if ($todayDepartures === []): ?>
                                <p>
                                    Brak zaplanowanych wyjazdów na dziś.
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($todayDepartures !== []): ?>
                            <div class="status-list">
                                <?php foreach (
                                    $todayDepartures
                                    as $reservation
                                ): ?>
                                    <a
                                        class="status-row"
                                        href="/admin/rezerwacje/pokaz?id=<?= (int) (
                                            $reservation['id']
                                            ?? 0
                                        ) ?>"
                                    >
                                        <span>
                                            <?= htmlspecialchars(
                                                $reservationCabinName(
                                                    $reservation
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $reservationGuestName(
                                                    $reservation
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="empty-state dashboard-today-section">
                        <div class="dashboard-today-section__header">
                            <strong>
                                Aktualnie zameldowani
                            </strong>

                            <?php if (
                                $checkedInReservations === []
                            ): ?>
                                <p>
                                    Aktualnie nikt nie ma statusu „Zameldowany”.
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if (
                            $checkedInReservations !== []
                        ): ?>
                            <div class="status-list">
                                <?php foreach (
                                    $checkedInReservations
                                    as $reservation
                                ): ?>
                                    <a
                                        class="status-row"
                                        href="/admin/rezerwacje/pokaz?id=<?= (int) (
                                            $reservation['id']
                                            ?? 0
                                        ) ?>"
                                    >
                                        <span>
                                            <?= htmlspecialchars(
                                                $reservationCabinName(
                                                    $reservation
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $reservationGuestName(
                                                    $reservation
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="empty-state dashboard-today-section">
                        <div class="dashboard-today-section__header">
                            <strong>
                                Nowe zapytania
                            </strong>

                            <?php if ($newInquiries === []): ?>
                                <p>
                                    Brak nowych zapytań wymagających obsługi.
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($newInquiries !== []): ?>
                            <div class="status-list">
                                <?php foreach (
                                    $newInquiries
                                    as $inquiry
                                ): ?>
                                    <a
                                        class="status-row"
                                        href="/admin/zapytania/pokaz?id=<?= (int) (
                                            $inquiry['id']
                                            ?? 0
                                        ) ?>"
                                    >
                                        <span>
                                            <?= htmlspecialchars(
                                                $inquiryGuestName(
                                                    $inquiry
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <strong>
                                            <?= htmlspecialchars(
                                                formatDateForDisplay(
                                                    (string) (
                                                        $inquiry[
                                                            'date_from'
                                                        ]
                                                        ?? ''
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="empty-state dashboard-today-section">
                        <div class="dashboard-today-section__header">
                            <strong>
                                Najbliższe przyjazdy — 7 dni
                            </strong>

                            <?php if (
                                $upcomingReservations === []
                            ): ?>
                                <p>
                                    Brak przyjazdów w ciągu najbliższych 7 dni.
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if (
                            $upcomingReservations !== []
                        ): ?>
                            <div class="status-list">
                                <?php foreach (
                                    $upcomingReservations
                                    as $reservation
                                ): ?>
                                    <a
                                        class="status-row"
                                        href="/admin/rezerwacje/pokaz?id=<?= (int) (
                                            $reservation['id']
                                            ?? 0
                                        ) ?>"
                                    >
                                        <span>
                                            <?= htmlspecialchars(
                                                formatDateForDisplay(
                                                    (string) (
                                                        $reservation[
                                                            'start_date'
                                                        ]
                                                        ?? ''
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                            ·

                                            <?= htmlspecialchars(
                                                $reservationCabinName(
                                                    $reservation
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $reservationGuestName(
                                                    $reservation
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="empty-state dashboard-today-section">
                        <div class="dashboard-today-section__header">
                            <strong>
                                Sprzątanie
                            </strong>

                            <?php if ($cleaningCabins === []): ?>
                                <p>
                                    Wszystkie domki są oznaczone jako gotowe.
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($cleaningCabins !== []): ?>
                            <div class="status-list">
                                <?php foreach (
                                    $cleaningCabins
                                    as $cabin
                                ): ?>
                                    <?php
                                    $cleaningStatus = (string) (
                                        $cabin['cleaning_status']
                                        ?? 'READY'
                                    );

                                    $cleaningLabel = $cleaningStatus
                                        === 'CLEANING'
                                            ? 'Sprzątanie w toku'
                                            : 'Do sprzątania';
                                    ?>

                                    <div
                                        class="status-row dashboard-cleaning-row"
                                    >
                                        <div class="dashboard-cleaning-row__info">
                                            <span>
                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $cabin['name']
                                                        ?? 'Domek'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </span>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $cleaningLabel,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </strong>
                                        </div>

                                        <form
                                            method="post"
                                            action="/admin/domki/sprzatanie"
                                        >
                                            <?= csrfField() ?>

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) (
                                                    $cabin['id']
                                                    ?? 0
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="cleaning_status"
                                                value="READY"
                                            >

                                            <input
                                                type="hidden"
                                                name="return_url"
                                                value="/admin"
                                            >

                                            <button
                                                class="button button--primary button--small"
                                                type="submit"
                                            >
                                                Sprzątnięty
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
