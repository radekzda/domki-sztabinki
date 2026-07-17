<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array{
 *     id: int,
 *     cabin_id: int,
 *     guest_id: int|null,
 *     cabin_name: string|null,
 *     linked_guest_name: string|null,
 *     guest_name: string,
 *     email: string,
 *     phone: string|null,
 *     start_date: string,
 *     end_date: string,
 *     nights: int,
 *     guests: int,
 *     adults: int,
 *     children: int,
 *     status: string,
 *     source: string,
 *     payment_status: string|null,
 *     total_price: string|null,
 *     paid_amount: string|null,
 *     notes: string|null,
 *     created_at: string
 * } $reservation
 * @var string $reservationConfirmationTemplate
 * @var string $preArrivalTemplate
 * @var string $depositPaymentTemplate
 */

$statusLabels = [
    'PENDING' => 'Oczekuje',
    'CONFIRMED' => 'Potwierdzona',
    'CHECKED_IN' => 'Zameldowany',
    'CHECKED_OUT' => 'Wymeldowany',
    'CANCELLED' => 'Anulowana',
];

$paymentLabels = [
    'PENDING' => 'Oczekuje',
    'PAID' => 'Opłacona',
    'PARTIAL' => 'Częściowa',
    'REFUNDED' => 'Zwrócona',
];

$paymentStatus = $reservation['payment_status'] ?? '';

$returnUrl = isset($_GET['return']) && is_string($_GET['return']) ? $_GET['return'] : '';
$canReturnToCalendar = str_starts_with($returnUrl, '/admin/kalendarz');
$quickActionReturnUrl = $canReturnToCalendar ? $returnUrl : '/admin/rezerwacje/pokaz?id=' . (string) $reservation['id'];

$displayValue = static function (mixed $value): string {
    if ($value === null) {
        return '—';
    }

    $value = trim((string) $value);

    return $value !== '' ? $value : '—';
};

$displayDateTime = static function (mixed $value): string {
    if ($value === null || trim((string) $value) === '') {
        return '—';
    }

    try {
        return (new DateTimeImmutable((string) $value))->format('d.m.Y H:i');
    } catch (Throwable $exception) {
        return (string) $value;
    }
};
?>

<style>
    .reservation-action-panel {
        margin: 18px 0 22px;
        border: 1px solid var(--color-border);
        border-radius: 18px;
        background: #ffffff;
        padding: 16px;
    }

    .reservation-action-panel__header {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 14px;
    }

    .reservation-action-panel__header strong {
        display: block;
        font-size: 17px;
        font-weight: 900;
    }

    .reservation-action-panel__header span {
        display: block;
        margin-top: 3px;
        color: var(--color-muted);
        font-size: 13px;
        line-height: 1.4;
    }

    .reservation-action-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .reservation-action-grid form {
        margin: 0;
    }

    .reservation-action-inline {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .reservation-action-inline select,
    .reservation-action-inline input {
        min-height: 44px;
        border: 1px solid var(--color-border);
        border-radius: 999px;
        background: #ffffff;
        padding: 0 14px;
    }

    .reservation-action-inline input {
        width: 150px;
    }

    .button--danger {
        background: #dc2626;
        color: #ffffff;
    }

    .button--danger:hover {
        background: #991b1b;
    }
</style>


<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'reservations']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Rezerwacje</p>

                            <h1>Szczegóły rezerwacji</h1>

                            <p>
                                Podgląd pełnych danych rezerwacji, statusu i płatności.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <?php if ($canReturnToCalendar): ?>
                                <a
                                    class="button button--secondary"
                                    href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    Wróć do kalendarza
                                </a>
                            <?php endif; ?>
                            <a
                                class="button button--primary"
                                href="/admin/rezerwacje/edytuj?id=<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?><?= $canReturnToCalendar ? '&return=' . urlencode($returnUrl) : '' ?>"
                            >
                                Edytuj
                            </a>

                            <a class="button button--secondary" href="/admin/rezerwacje">
                                Wróć do listy
                            </a>
                        </div>
                    </div>


                    <!-- M13.65 reservation action panel -->
                    <div class="reservation-action-panel">
                        <div class="reservation-action-panel__header">
                            <div>
                                <strong>Akcje rezerwacji</strong>
                                <span>Szybka obsługa statusu, płatności, wpłaty, anulowania i usuwania.</span>
                            </div>
                        </div>

                        <div class="reservation-action-grid">
                            <form method="post" action="/admin/rezerwacje/szybki-status">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="status" value="CONFIRMED">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($quickActionReturnUrl, ENT_QUOTES, 'UTF-8') ?>">

                                <button class="button button--primary" type="submit">
                                    Oznacz jako potwierdzona
                                </button>
                            </form>

                            <form method="post" action="/admin/rezerwacje/szybki-status">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="status" value="CHECKED_IN">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($quickActionReturnUrl, ENT_QUOTES, 'UTF-8') ?>">

                                <button class="button button--primary" type="submit">
                                    Oznacz jako zameldowany
                                </button>
                            </form>

                            <form method="post" action="/admin/rezerwacje/szybki-status">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="status" value="CHECKED_OUT">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($quickActionReturnUrl, ENT_QUOTES, 'UTF-8') ?>">

                                <button class="button button--secondary" type="submit">
                                    Oznacz jako wymeldowany
                                </button>
                            </form>

                            <form method="post" action="/admin/rezerwacje/szybka-platnosc">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="payment_status" value="PAID">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($quickActionReturnUrl, ENT_QUOTES, 'UTF-8') ?>">

                                <button class="button button--primary" type="submit">
                                    Oznacz jako opłacona
                                </button>
                            </form>

                            <form method="post" action="/admin/rezerwacje/status" class="reservation-action-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($quickActionReturnUrl, ENT_QUOTES, 'UTF-8') ?>">

                                <select name="status">
                                    <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                                        <option
                                            value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>"
                                            <?= $reservation['status'] === $statusValue ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button class="button button--primary" type="submit">
                                    Zmień status
                                </button>
                            </form>

                            <form method="post" action="/admin/rezerwacje/platnosc" class="reservation-action-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($quickActionReturnUrl, ENT_QUOTES, 'UTF-8') ?>">

                                <select name="payment_status">
                                    <?php foreach ($paymentLabels as $paymentValue => $paymentLabel): ?>
                                        <option
                                            value="<?= htmlspecialchars($paymentValue, ENT_QUOTES, 'UTF-8') ?>"
                                            <?= $reservation['payment_status'] === $paymentValue ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button class="button button--primary" type="submit">
                                    Zmień płatność
                                </button>
                            </form>

                            <form method="post" action="/admin/rezerwacje/wplata" class="reservation-action-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($quickActionReturnUrl, ENT_QUOTES, 'UTF-8') ?>">

                                <input
                                    name="amount"
                                    type="number"
                                    min="1"
                                    step="1"
                                    placeholder="Kwota wpłaty"
                                    required
                                >

                                <button class="button button--primary" type="submit">
                                    Dodaj wpłatę
                                </button>
                            </form>

                            <?php if ($reservation['status'] !== 'CANCELLED'): ?>
                                <form
                                    method="post"
                                    action="/admin/rezerwacje/anuluj"
                                    onsubmit="return confirm('Czy na pewno anulować tę rezerwację?')"
                                >
                                <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($quickActionReturnUrl, ENT_QUOTES, 'UTF-8') ?>">

                                    <button class="button button--danger" type="submit">
                                        Anuluj rezerwację
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form
                                method="post"
                                action="/admin/rezerwacje/usun"
                                onsubmit="return confirm('Czy na pewno trwale usunąć tę rezerwację? Tej operacji nie można cofnąć.')"
                            >
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($quickActionReturnUrl, ENT_QUOTES, 'UTF-8') ?>">

                                <button class="button button--danger" type="submit">
                                    Usuń trwale
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="status-list">
                        
                        <div class="status-row">
                            <span>ID z Base44</span>
                            <strong><?= htmlspecialchars($displayValue($reservation['external_id'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

<div class="status-row">
                            <span>Gość z rezerwacji</span>
                            <strong><?= htmlspecialchars($reservation['guest_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Powiązana karta gościa</span>
                            <strong>
                                <?php if ($reservation['guest_id'] !== null): ?>
                                    <a href="/admin/goscie/pokaz?id=<?= htmlspecialchars((string) $reservation['guest_id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($reservation['linked_guest_name'] ?? 'Gość #' . $reservation['guest_id'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </strong>
                        </div>

                        <div class="status-row">
                            <span>E-mail</span>
                            <strong><?= htmlspecialchars($reservation['email'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Telefon</span>
                            <strong><?= htmlspecialchars($reservation['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Domek</span>
                            <strong><?= htmlspecialchars($reservation['cabin_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="status-row">
                            <span>Powiązany domek</span>
                            <strong>
                                <a href="/admin/domki/edytuj?id=<?= htmlspecialchars((string) $reservation['cabin_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    Domek #<?= htmlspecialchars((string) $reservation['cabin_id'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </strong>
                        </div>


                        <div class="status-row">
                            <span>Termin</span>
                            <strong>
                                <?= htmlspecialchars(formatDateForDisplay($reservation['start_date']), ENT_QUOTES, 'UTF-8') ?>
                                —
                                <?= htmlspecialchars(formatDateForDisplay($reservation['end_date']), ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>
                        <div class="status-row">
                            <span>Godzina przyjazdu</span>
                            <strong><?= htmlspecialchars($displayDateTime($reservation['check_in_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Godzina wyjazdu</span>
                            <strong><?= htmlspecialchars($displayDateTime($reservation['check_out_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>


                        <div class="status-row">
                            <span>Liczba nocy</span>
                            <strong><?= htmlspecialchars((string) $reservation['nights'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Liczba osób</span>
                            <strong>
                                <?= htmlspecialchars((string) $reservation['guests'], ENT_QUOTES, 'UTF-8') ?>
                                os. /
                                dorośli:
                                <?= htmlspecialchars((string) $reservation['adults'], ENT_QUOTES, 'UTF-8') ?>,
                                dzieci:
                                <?= htmlspecialchars((string) $reservation['children'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>

                        <div class="status-row">
                            <span>Status</span>
                            <strong><?= htmlspecialchars($statusLabels[$reservation['status']] ?? $reservation['status'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Płatność</span>
                            <strong><?= htmlspecialchars($paymentLabels[$paymentStatus] ?? ($paymentStatus !== '' ? $paymentStatus : '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Kwota</span>
                            <strong><?= htmlspecialchars(formatMoneyForDisplay($reservation['total_price']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Wpłacono</span>
                            <strong><?= htmlspecialchars(formatMoneyForDisplay($reservation['paid_amount']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Źródło</span>
                            <strong><?= htmlspecialchars($reservation['source'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Utworzono</span>
                            <strong><?= htmlspecialchars(formatDateForDisplay($reservation['created_at']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>

                    <div class="empty-state">
                        <strong>Potwierdzenie rezerwacji</strong>

                        <p>
                            Gotowa wiadomość przygotowana na podstawie danych zapisanej rezerwacji.
                        </p>

                        <div class="form-field">
                            <textarea
                                id="reservation-confirmation-template"
                                rows="18"
                                readonly
                            ><?= htmlspecialchars($reservationConfirmationTemplate, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button
                                class="button button--primary"
                                id="copy-reservation-confirmation"
                                type="button"
                            >
                                Kopiuj wiadomość
                            </button>
                        </div>
                    </div>

                    <div class="empty-state">
                        <strong>Dane do wpłaty zadatku</strong>

                        <p>
                            Gotowa wiadomość z kwotą zadatku i danymi do przelewu z Ustawień systemu.
                        </p>

                        <div class="form-field">
                            <textarea
                                id="deposit-payment-template"
                                rows="18"
                                readonly
                            ><?= htmlspecialchars($depositPaymentTemplate, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button
                                class="button button--primary"
                                id="copy-deposit-payment"
                                type="button"
                            >
                                Kopiuj wiadomość
                            </button>
                        </div>
                    </div>

                    <div class="empty-state">
                        <strong>Wiadomość przed przyjazdem</strong>

                        <p>
                            Gotowa wiadomość z terminem pobytu, godzinami zameldowania i danymi kontaktowymi.
                        </p>

                        <div class="form-field">
                            <textarea
                                id="pre-arrival-template"
                                rows="18"
                                readonly
                            ><?= htmlspecialchars($preArrivalTemplate, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button
                                class="button button--primary"
                                id="copy-pre-arrival"
                                type="button"
                            >
                                Kopiuj wiadomość
                            </button>
                        </div>
                    </div>

                    <?php if ($reservation['notes'] !== null && $reservation['notes'] !== ''): ?>
                        <div class="empty-state">
                            <strong>Notatki</strong>

                            <p>
                                <?= nl2br(htmlspecialchars($reservation['notes'], ENT_QUOTES, 'UTF-8')) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    

                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyButton = document.getElementById(
        'copy-reservation-confirmation'
    );

    const textarea = document.getElementById(
        'reservation-confirmation-template'
    );

    if (!copyButton || !textarea) {
        return;
    }

    copyButton.addEventListener('click', async function () {
        const message = textarea.value;

        try {
            if (
                navigator.clipboard
                && window.isSecureContext
            ) {
                await navigator.clipboard.writeText(message);
            } else {
                textarea.focus();
                textarea.select();
                document.execCommand('copy');
            }

            const originalText = copyButton.textContent;

            copyButton.textContent = 'Skopiowano';

            window.setTimeout(function () {
                copyButton.textContent = originalText;
            }, 1500);
        } catch (error) {
            textarea.focus();
            textarea.select();
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyButton = document.getElementById(
        'copy-pre-arrival'
    );

    const textarea = document.getElementById(
        'pre-arrival-template'
    );

    if (!copyButton || !textarea) {
        return;
    }

    copyButton.addEventListener('click', async function () {
        const message = textarea.value;

        try {
            if (
                navigator.clipboard
                && window.isSecureContext
            ) {
                await navigator.clipboard.writeText(message);
            } else {
                textarea.focus();
                textarea.select();
                document.execCommand('copy');
            }

            const originalText = copyButton.textContent;

            copyButton.textContent = 'Skopiowano';

            window.setTimeout(function () {
                copyButton.textContent = originalText;
            }, 1500);
        } catch (error) {
            textarea.focus();
            textarea.select();
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const depositCopyButton = document.getElementById(
        'copy-deposit-payment'
    );

    const depositTextarea = document.getElementById(
        'deposit-payment-template'
    );

    if (!depositCopyButton || !depositTextarea) {
        return;
    }

    depositCopyButton.addEventListener('click', async function () {
        const message = depositTextarea.value;

        try {
            if (
                navigator.clipboard
                && window.isSecureContext
            ) {
                await navigator.clipboard.writeText(message);
            } else {
                depositTextarea.focus();
                depositTextarea.select();
                document.execCommand('copy');
            }

            const originalText = depositCopyButton.textContent;

            depositCopyButton.textContent = 'Skopiowano';

            window.setTimeout(function () {
                depositCopyButton.textContent = originalText;
            }, 1500);
        } catch (error) {
            depositTextarea.focus();
            depositTextarea.select();
        }
    });
});
</script>
