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
 * @var array<int, array<string, mixed>> $inquiryMessageTemplates
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

                    <div class="empty-state">
                        <strong>Wiadomości do gościa</strong>

                        <p>
                            Wiadomości są przygotowane na podstawie aktywnych szablonów.
                            Możesz zmienić treść poniżej przed skopiowaniem.
                            Zmiana wykonana tutaj nie zmienia globalnego szablonu.
                        </p>
                    </div>

                    <?php if ($inquiryMessageTemplates === []): ?>
                        <div class="empty-state">
                            <strong>Brak aktywnych szablonów</strong>

                            <p>
                                Aktywne szablony dla zapytań możesz dodać
                                lub włączyć w sekcji Szablony.
                            </p>

                            <a
                                class="button button--secondary"
                                href="/admin/szablony"
                            >
                                Przejdź do szablonów
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($inquiryMessageTemplates as $messageTemplate): ?>
                        <?php
                        $messageTemplateId = (int) (
                            $messageTemplate['id']
                            ?? 0
                        );

                        $messageTemplateName = (string) (
                            $messageTemplate['name']
                            ?? 'Szablon wiadomości'
                        );

                        $renderedContent = (string) (
                            $messageTemplate['rendered_content']
                            ?? ''
                        );

                        $textareaId = 'inquiry-message-template-'
                            . $messageTemplateId;
                        ?>

                        <div class="empty-state">
                            <strong>
                                <?= htmlspecialchars(
                                    $messageTemplateName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                            <div class="form-field">
                                <textarea
                                    id="<?= htmlspecialchars(
                                        $textareaId,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    rows="18"
                                ><?= htmlspecialchars(
                                    $renderedContent,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?></textarea>
                            </div>

                            <div class="form-actions">
                                <button
                                    class="button button--primary js-copy-inquiry-template"
                                    data-copy-target="<?= htmlspecialchars(
                                        $textareaId,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    type="button"
                                >
                                    Kopiuj wiadomość
                                </button>

                                <a
                                    class="button button--secondary"
                                    href="/admin/szablony"
                                >
                                    Edytuj szablony
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="admin-actions">
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
    <?= csrfField() ?>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyButtons = document.querySelectorAll(
        '.js-copy-inquiry-template'
    );

    copyButtons.forEach(function (copyButton) {
        copyButton.addEventListener(
            'click',
            async function () {
                const targetId = copyButton.getAttribute(
                    'data-copy-target'
                );

                if (!targetId) {
                    return;
                }

                const textarea = document.getElementById(
                    targetId
                );

                if (!textarea) {
                    return;
                }

                const message = textarea.value;

                try {
                    if (
                        navigator.clipboard
                        && window.isSecureContext
                    ) {
                        await navigator.clipboard.writeText(
                            message
                        );
                    } else {
                        textarea.focus();
                        textarea.select();
                        document.execCommand(
                            'copy'
                        );
                    }

                    const originalText =
                        copyButton.textContent;

                    copyButton.textContent =
                        'Skopiowano';

                    window.setTimeout(
                        function () {
                            copyButton.textContent =
                                originalText;
                        },
                        1500
                    );
                } catch (error) {
                    textarea.focus();
                    textarea.select();
                }
            }
        );
    });
});
</script>
