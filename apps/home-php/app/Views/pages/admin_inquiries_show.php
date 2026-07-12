<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array{
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
 * } $inquiry
 */

$statusLabels = [
    'NEW' => 'Nowe',
    'IN_PROGRESS' => 'W trakcie',
    'RESOLVED' => 'Obsłużone',
    'CANCELLED' => 'Anulowane',
];

$cabinName = $inquiry['linked_cabin_name']
    ?? $inquiry['cabin_name']
    ?? 'Dowolny / nie wybrano';
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

                            <h1>Szczegóły zapytania</h1>

                            <p>
                                Podgląd pełnych danych zapytania oraz szybka obsługa statusu.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--primary"
                                href="/admin/rezerwacje/nowa?inquiry_id=<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                Utwórz rezerwację
                            </a>

                            <a class="button button--secondary" href="/admin/zapytania">
                                Wróć do listy
                            </a>
                        </div>
                    </div>

                    <div class="status-list">
                        <div class="status-row">
                            <span>Gość</span>
                            <strong><?= htmlspecialchars($inquiry['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Telefon</span>
                            <strong><?= htmlspecialchars($inquiry['phone'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>E-mail</span>
                            <strong><?= htmlspecialchars($inquiry['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Miejscowość</span>
                            <strong><?= htmlspecialchars($inquiry['city'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Kraj</span>
                            <strong><?= htmlspecialchars($inquiry['country'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Termin</span>
                            <strong>
                                <?= htmlspecialchars(formatDateForDisplay($inquiry['date_from']), ENT_QUOTES, 'UTF-8') ?>
                                —
                                <?= htmlspecialchars(formatDateForDisplay($inquiry['date_to']), ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>

                        <div class="status-row">
                            <span>Domek</span>
                            <strong><?= htmlspecialchars($cabinName, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Liczba osób</span>
                            <strong>
                                <?= htmlspecialchars((string) $inquiry['guests'], ENT_QUOTES, 'UTF-8') ?>
                                os. /
                                dorośli:
                                <?= htmlspecialchars((string) $inquiry['adults'], ENT_QUOTES, 'UTF-8') ?>,
                                dzieci:
                                <?= htmlspecialchars((string) $inquiry['children'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>

                        <div class="status-row">
                            <span>Status</span>
                            <strong><?= htmlspecialchars($statusLabels[$inquiry['status']] ?? $inquiry['status'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Źródło</span>
                            <strong><?= htmlspecialchars($inquiry['source'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Utworzono</span>
                            <strong><?= htmlspecialchars(formatDateForDisplay($inquiry['created_at']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>

                    <?php if ($inquiry['notes'] !== null && $inquiry['notes'] !== ''): ?>
                        <div class="empty-state">
                            <strong>Notatki</strong>

                            <p>
                                <?= nl2br(htmlspecialchars($inquiry['notes'], ENT_QUOTES, 'UTF-8')) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="admin-actions">
                        <form method="post" action="/admin/zapytania/status">
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

                            <button class="button button--primary" type="submit">
                                Zmień status
                            </button>
                        </form>

                        <a
                            class="button button--primary"
                            href="/admin/rezerwacje/nowa?inquiry_id=<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            Utwórz rezerwację
                        </a>

                        <form
                            method="post"
                            action="/admin/zapytania/usun"
                            onsubmit="return confirm('Czy na pewno usunąć to zapytanie?')"
                        >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <button class="button button--secondary" type="submit">
                                Usuń zapytanie
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>