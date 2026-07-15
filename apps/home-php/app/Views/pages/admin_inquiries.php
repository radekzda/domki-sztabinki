<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array{
 *     id: int,
 *     full_name: string,
 *     first_name: string|null,
 *     last_name: string|null,
 *     phone: string,
 *     email: string|null,
 *     cabin_id: int|null,
 *     cabin_name: string|null,
 *     linked_cabin_name: string|null,
 *     date_from: string,
 *     date_to: string,
 *     guests: int,
 *     adults: int,
 *     children: int,
 *     city: string|null,
 *     country: string|null,
 *     notes: string|null,
 *     status: string,
 *     source: string,
 *     created_at: string
 * }> $inquiries
 * @var string|null $databaseMessage
 * @var string|null $successMessage
 */

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
                                Lista zapytań pobierana z bazy MySQL. Możesz zmieniać status,
                                podejrzeć szczegóły albo utworzyć rezerwację z zapytania.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/system/database">
                                Sprawdź bazę
                            </a>
                        </div>
                    </div>

                    <?php if (isset($successMessage) && is_string($successMessage) && $successMessage !== ''): ?>
                        <div class="alert alert--success">
                            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

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
                                        <th>Akcje</th>
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

                                            <td>
                                                <div class="table-actions">
                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/zapytania/pokaz?id=<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Szczegóły
                                                    </a>

                                                    <a
                                                        class="button button--primary button--small"
                                                        href="/admin/rezerwacje/nowa?inquiry_id=<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Rezerwacja
                                                    </a>

                                                    <form method="post" action="/admin/zapytania/status">
    <?= csrfField() ?>
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <select name="status">
                                                            <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                                                                <option
                                                                    value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>"
                                                                    <?= $inquiry['status'] === $statusValue ? 'selected' : '' ?>
                                                                >
                                                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>

                                                        <button class="button button--primary button--small" type="submit">
                                                            Status
                                                        </button>
                                                    </form>

                                                    <form
                                                        method="post"
                                                        action="/admin/zapytania/usun"
                                                        onsubmit="return confirm('Czy na pewno usunąć to zapytanie?')"
                                                    >
    <?= csrfField() ?>
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <button class="button button--secondary button--small" type="submit">
                                                            Usuń
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                                        <?php if ($inquiry['notes'] !== null && $inquiry['notes'] !== ''): ?>
                                            <tr>
                                                <td colspan="9">
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