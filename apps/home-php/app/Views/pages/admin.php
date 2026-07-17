<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array<string, mixed>> $todayArrivals
 * @var array<int, array<string, mixed>> $todayDepartures
 * @var array<int, array<string, mixed>> $checkedInReservations
 * @var array<int, array<string, mixed>> $newInquiries
 * @var array<int, array<string, mixed>> $upcomingReservations
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
    .dashboard-today-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
        margin-top: 16px;
        margin-bottom: 16px;
    }

    .dashboard-today-card {
        min-height: 70px;
        padding: 12px 14px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 3px;
    }

    .dashboard-today-card strong {
        font-size: 24px;
        line-height: 1;
    }

    .dashboard-today-card span {
        font-size: 13px;
        line-height: 1.25;
    }

    .dashboard-today-sections {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
    }

    .dashboard-today-section {
        min-width: 0;
        min-height: 120px;
        padding: 12px 14px;
        margin: 0;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
    }

    .dashboard-today-section:nth-child(1) {
        background: #eff6ff;
        border-top: 4px solid #3b82f6;
    }

    .dashboard-today-section:nth-child(2) {
        background: #fff7ed;
        border-top: 4px solid #f97316;
    }

    .dashboard-today-section:nth-child(3) {
        background: #f0fdf4;
        border-top: 4px solid #22c55e;
    }

    .dashboard-today-section:nth-child(4) {
        background: #faf5ff;
        border-top: 4px solid #a855f7;
    }

    .dashboard-today-section:nth-child(5) {
        background: #fefce8;
        border-top: 4px solid #eab308;
    }

    .dashboard-today-section__header {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
        min-height: 42px;
    }

    .dashboard-today-section__header strong {
        display: block;
        margin: 0;
        font-size: 14px;
        line-height: 1.2;
        font-weight: 700;
    }

    .dashboard-today-section__header p {
        display: block;
        margin: 0;
        font-size: 12px;
        line-height: 1.35;
        opacity: 0.72;
    }

    .dashboard-today-section .status-list {
        margin-top: 10px;
        display: grid;
        gap: 5px;
    }

    .dashboard-today-section .status-row {
        padding: 7px 8px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.7);
        font-size: 12px;
        line-height: 1.3;
    }

    .dashboard-today-section .status-row span,
    .dashboard-today-section .status-row strong {
        font-size: 12px;
    }

    @media (max-width: 1200px) {
        .dashboard-today-grid,
        .dashboard-today-sections {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 800px) {
        .dashboard-today-grid,
        .dashboard-today-sections {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 520px) {
        .dashboard-today-grid,
        .dashboard-today-sections {
            grid-template-columns: 1fr;
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
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">
                                Dashboard
                            </p>

                            <h1>
                                Co dziś
                            </h1>

                            <p>
                                <?= htmlspecialchars(
                                    (new DateTimeImmutable())
                                        ->format('d.m.Y'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>
                        </div>
                    </div>

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
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
