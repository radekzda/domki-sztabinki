<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var array<int, array{
 *     id: int,
 *     name: string,
 *     short_name: string|null,
 *     max_guests: int,
 *     bedrooms: int,
 *     bathrooms: int,
 *     price_per_night: int,
 *     price_one_night: int,
 *     price_two_nights: int,
 *     price_three_nights: int,
 *     price_four_nights: int,
 *     price_five_nights: int,
 *     price_six_nights: int,
 *     price_seven_plus_nights: int,
 *     is_active: int,
 *     sort_order: int,
 *     created_at: string
 * }> $cabins
 * @var array<int, array{
 *     id: int,
 *     first_name: string,
 *     last_name: string,
 *     email: string,
 *     phone: string|null,
 *     country: string|null,
 *     city: string|null,
 *     is_vip: int,
 *     source: string,
 *     created_at: string
 * }> $guests
 * @var string|null $databaseMessage
 * @var bool $canSave
 * @var int|null $calculatedNights
 * @var int|null $calculatedTotalPrice
 */

$returnUrl = isset($_GET['return']) && is_string($_GET['return']) ? $_GET['return'] : '';

if ($returnUrl === '' && isset($_POST['return_url']) && is_string($_POST['return_url'])) {
    $returnUrl = $_POST['return_url'];
}

$canReturnToCalendar = str_starts_with($returnUrl, '/admin/kalendarz');

$inquiryId = filter_var(
    $_GET['inquiry_id'] ?? null,
    FILTER_VALIDATE_INT
);

$reservationCreateAction = '/admin/rezerwacje/nowa';

if (is_int($inquiryId) && $inquiryId > 0) {
    $reservationCreateAction .= '?inquiry_id='
        . $inquiryId;
}
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'reservations']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Rezerwacje</p>

                            <h1>Dodaj rezerwację</h1>

                            <p>
                                Dodaj pobyt ręcznie. System wyliczy liczbę nocy i domyślną cenę według cennika domku.
                                Jeżeli nie wybierzesz gościa, zostanie znaleziony po e-mailu albo utworzony automatycznie.
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
                            <a class="button button--secondary" href="/admin/rezerwacje">
                                Wróć do listy
                            </a>
                        </div>
                    </div>

                    <?php if (isset($databaseMessage) && is_string($databaseMessage) && $databaseMessage !== ''): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars($databaseMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($errors !== []): ?>
                        <div class="alert alert--danger">
                            Popraw błędy w formularzu.
                        </div>
                    <?php endif; ?>

                    <?php if ($calculatedNights !== null && $calculatedTotalPrice !== null): ?>
                        <div class="alert alert--success">
                            Wyliczenie:
                            <?= htmlspecialchars((string) $calculatedNights, ENT_QUOTES, 'UTF-8') ?>
                            noc. /
                            <?= htmlspecialchars(number_format($calculatedTotalPrice, 0, ',', ' '), ENT_QUOTES, 'UTF-8') ?>
                            zł
                        </div>
                    <?php endif; ?>

                    <?php
                    View::partial('partials/reservation_form', [
                        'form' => $form,
                        'errors' => $errors,
                        'cabins' => $cabins,
                        'guests' => $guests,
                        'canSave' => $canSave,
                        'action' => $reservationCreateAction,
                        'submitLabel' => 'Zapisz rezerwację',
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>