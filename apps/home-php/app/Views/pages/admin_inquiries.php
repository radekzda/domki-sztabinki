<?php

declare(strict_types=1);

/**
 * @var string $title
 */

require_once dirname(__DIR__, 2) . '/Repositories/InquiryRepository.php';

$inquiries = [];
$databaseMessage = null;

if (!Database::canAttemptConnection()) {
    $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Lista zapytań zostanie pokazana po ustawieniu danych MySQL w pliku .env.';
} else {
    try {
        $inquiries = InquiryRepository::all();
    } catch (Throwable $exception) {
        $databaseMessage = 'Nie udało się pobrać listy zapytań z bazy: ' . $exception->getMessage();
    }
}

$statusLabels = [
    'NEW' => 'Nowe',
    'IN_PROGRESS' => 'W trakcie',
    'RESOLVED' => 'Obsłużone',
    'CANCELLED' => 'Anulowane',
];

$getStatusClass = static function (string $status): string {
    if ($status === 'RESOLVED') {
        return 'status-pill status-pill--success';
    }

    return 'status-pill status-pill--muted';
};
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'inquiries']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Zapytania</p>

                            <h1>Zapytania</h1>

                            <p>
                                Lista zapytań pobierana z bazy MySQL. W kolejnym kroku dodamy szczegóły,
                                zmianę statusu oraz tworzenie rezerwacji z zapytania.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/system/database">
                                Sprawdź bazę
                            </a>
                        </div>
                    </div>

                    <?php if (isset($databaseMessage) && is_string($databaseMessage) && $databaseMessage !== ''): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars($databaseMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($inquiries === []): ?>
                        <div class="empty-state">
                            <strong>Brak zapytań do wyświetlenia</strong>

                            <p>
                                Po uruchomieniu publicznego formularza WWW nowe zapytania będą pojawiały się tutaj.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="dashboard-grid">
                            <div class="stat-card">
                                <span>Wszystkie zapytania</span>
                                <strong><?= htmlspecialchars((string) count($inquiries), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>

                            <div class="stat-card">
                                <span>Nowe</span>
                                <strong>
                                    <?= htmlspecialchars((string) count(array_filter($inquiries, static fn (array $inquiry): bool => $inquiry['status'] === 'NEW')), ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </div>

                            <div class="stat-card">
                                <span>W trakcie</span>
                                <strong>
                                    <?= htmlspecialchars((string) count(array_filter($inquiries, static fn (array $inquiry): bool => $inquiry['status'] === 'IN_PROGRESS')), ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </div>

                            <div class="stat-card">
                                <span>Obsłużone</span>
                                <strong>
                                    <?= htmlspecialchars((string) count(array_filter($inquiries, static fn (array $inquiry): bool => $inquiry['status'] === 'RESOLVED')), ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </div>
                        </div>

                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Data zapytania</th>
                                        <th>Gość</th>
                                        <th>Kontakt</th>
                                        <th>Termin</th>
                                        <th>Domek</th>
                                        <th>Osoby</th>
                                        <th>Status</th>
                                        <th>Źródło</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($inquiries as $inquiry): ?>
                                        <?php
                                        $status = $inquiry['status'];
                                        $cabinName = $inquiry['linked_cabin_name']
                                            ?? $inquiry['cabin_name']
                                            ?? 'Dowolny / nie wybrano';
                                        ?>

                                        <tr>
                                            <td>
                                                <?= htmlspecialchars(formatDateForDisplay($inquiry['created_at']), ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($inquiry['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <?php if ($inquiry['city'] !== null && $inquiry['city'] !== ''): ?>
                                                    <br>

                                                    <span>
                                                        <?= htmlspecialchars($inquiry['city'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($inquiry['country'] !== null && $inquiry['country'] !== ''): ?>
                                                    <br>

                                                    <span>
                                                        <?= htmlspecialchars($inquiry['country'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <span>
                                                    <?= htmlspecialchars($inquiry['phone'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>

                                                <?php if ($inquiry['email'] !== null && $inquiry['email'] !== ''): ?>
                                                    <br>

                                                    <span>
                                                        <?= htmlspecialchars($inquiry['email'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(formatDateForDisplay($inquiry['date_from']), ENT_QUOTES, 'UTF-8') ?>
                                                    —
                                                    <?= htmlspecialchars(formatDateForDisplay($inquiry['date_to']), ENT_QUOTES, 'UTF-8') ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($cabinName, ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars((string) $inquiry['guests'], ENT_QUOTES, 'UTF-8') ?>
                                                os.

                                                <br>

                                                <span>
                                                    dorośli:
                                                    <?= htmlspecialchars((string) $inquiry['adults'], ENT_QUOTES, 'UTF-8') ?>,
                                                    dzieci:
                                                    <?= htmlspecialchars((string) $inquiry['children'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="<?= htmlspecialchars($getStatusClass($status), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($inquiry['source'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                        </tr>

                                        <?php if ($inquiry['notes'] !== null && $inquiry['notes'] !== ''): ?>
                                            <tr>
                                                <td colspan="8">
                                                    <strong>Notatka:</strong>
                                                    <?= nl2br(htmlspecialchars($inquiry['notes'], ENT_QUOTES, 'UTF-8')) ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>