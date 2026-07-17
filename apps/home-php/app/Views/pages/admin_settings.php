<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var string|null $databaseMessage
 * @var string|null $successMessage
 * @var bool $canSave
 */
?>
<style>
    .settings-panel {
        padding: 28px;
    }

    /*
     * Naglowek strony
     */
    .settings-panel > .page-header {
        margin-bottom: 22px;
        align-items: flex-start;
    }

    .settings-panel > .page-header .eyebrow {
        display: none;
 > .page-header {
        margin-bottom: 22px;
        align-items: flex-start;
    }

    .settings-panel > .    }

    .settings-panel > .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .settings-panel > .page-header p {
        max-width: 760px;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .settings-panel > .page-header .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
    }

    /*
     * Glowny formularz
     */
    .settings-form {
        max-width: none !important;
    }

    .settings-form .form-grid {
        display: grid;
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
        gap: 14px 16px;
    }

    .settings-form .form-field {
        min-width: 0;
        margin: 0;
    }

    .settings-form .form-field--full {
        grid-column: 1 / -1;
    }

    /*
     * Sekcje formularza
     */
    .settings-section-heading {
        margin-top: 10px !important;
        padding: 16px 18px !important;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
    }

    .settings-section-heading:first-child {
        margin-top: 0 !important;
    }

    .settings-section-heading h2 {
        margin: 0 0 5px !important;
        font-size: 17px;
        line-height: 1.2;
        color: #111827;
    }

    .settings-section-heading p {
        margin: 0 !important;
        max-width: 820px;
        font-size: 12px;
        line-height: 1.45;
        color: #6b7280;
    }

    /*
     * Pola formularza
     */
    .settings-form .form-field > label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        line-height: 1.25;
        font-weight: 700;
        color: #374151;
    }

    .settings-form input[type="text"],
    .settings-form input[type="email"],
    .settings-form input[type="number"],
    .settings-form input[type="time"],
    .settings-form textarea,
    .settings-form select {
        width: 100%;
        min-width: 0;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #ffffff;
        color: #111827;
        font-size: 13px;
        transition:
            border-color 0.15s ease,
            box-shadow 0.15s ease;
    }

    .settings-form input[type="text"],
    .settings-form input[type="email"],
    .settings-form input[type="number"],
    .settings-form input[type="time"],
    .settings-form select {
        min-height: 42px;
        padding: 9px 11px;
    }

    .settings-form textarea {
        padding: 11px 12px;
        line-height: 1.5;
        resize: vertical;
    }

    .settings-form input:focus,
    .settings-form textarea:focus,
    .settings-form select:focus {
        outline: none;
        border-color: #15803d;
        box-shadow:
            0 0 0 3px rgba(
                21,
                128,
                61,
                0.1
            );
    }

    /*
     * Bledy
     */
    .settings-form .form-error {
        display: block;
        margin-top: 5px;
        font-size: 11px;
        line-height: 1.35;
        color: #dc2626;
    }

    /*
     * Akcje na dole
     */
    .settings-form > .form-actions {
        position: sticky;
        bottom: 12px;
        z-index: 10;
        margin-top: 20px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: rgba(
            255,
            255,
            255,
            0.96
        );
        box-shadow:
            0 8px 24px rgba(
                15,
                23,
                42,
                0.08
            );
        backdrop-filter: blur(8px);
    }

    .settings-form > .form-actions .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 9px;
        font-size: 12px;
    }

    /*
     * Delikatne grupowanie pol miedzy naglowkami
     */
    .settings-section-heading
    + .form-field {
        margin-top: 2px;
    }

    /*
     * Responsive
     */
    @media (max-width: 900px) {
        .settings-panel {
            padding: 22px;
        }

        .settings-form .form-grid {
            grid-template-columns: 1fr;
        }

        .settings-form .form-field--full {
            grid-column: 1;
        }
    }

    @media (max-width: 600px) {
        .settings-panel {
            padding: 16px;
        }

        .settings-panel > .page-header {
            flex-direction: column;
            gap: 14px;
        }

        .settings-panel > .page-header h1 {
            font-size: 27px;
        }

        .settings-section-heading {
            padding: 14px !important;
        }

        .settings-form > .form-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .settings-form > .form-actions .button {
            width: 100%;
            text-align: center;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'settings']); ?>

            <div class="admin-content">
                <div class="panel settings-panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Ustawienia</p>

                            <h1>Ustawienia systemu</h1>

                            <p>
                                Dane obiektu, kontakt, zasady pobytu oraz podstawowe ceny dodatkowe.
                                Te ustawienia będą później wykorzystywane na publicznej stronie PHP.
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

                    <?php if ($errors !== []): ?>
                        <div class="alert alert--danger">
                            Popraw błędy w formularzu.
                        </div>
                    <?php endif; ?>

                    <form class="form form--wide settings-form" method="post" action="/admin/ustawienia">
    <?= csrfField() ?>
                        <div class="form-grid">
                            <div class="form-field form-field--full settings-section-heading">
                                <h2>Dane obiektu</h2>

                                <p>
                                    Podstawowe dane kontaktowe, lokalizacja oraz zasady obsługi pobytu.
                                </p>
                            </div>

                            <div class="form-field form-field--full">
                                <label for="property_name">Nazwa obiektu</label>
                                <input
                                    id="property_name"
                                    name="property_name"
                                    type="text"
                                    value="<?= htmlspecialchars($form['property_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['property_name'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['property_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="contact_email">E-mail kontaktowy</label>
                                <input
                                    id="contact_email"
                                    name="contact_email"
                                    type="email"
                                    value="<?= htmlspecialchars($form['contact_email'], ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <?php if (isset($errors['contact_email'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['contact_email'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="contact_phone">Telefon kontaktowy</label>
                                <input
                                    id="contact_phone"
                                    name="contact_phone"
                                    type="text"
                                    value="<?= htmlspecialchars($form['contact_phone'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-field form-field--full">
                                <label for="address_line">Adres / lokalizacja</label>
                                <input
                                    id="address_line"
                                    name="address_line"
                                    type="text"
                                    value="<?= htmlspecialchars($form['address_line'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="postal_code">Kod pocztowy</label>
                                <input
                                    id="postal_code"
                                    name="postal_code"
                                    type="text"
                                    value="<?= htmlspecialchars($form['postal_code'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="city">Miejscowość</label>
                                <input
                                    id="city"
                                    name="city"
                                    type="text"
                                    value="<?= htmlspecialchars($form['city'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="country">Kraj</label>
                                <input
                                    id="country"
                                    name="country"
                                    type="text"
                                    value="<?= htmlspecialchars($form['country'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="check_in_time">Zameldowanie od</label>
                                <input
                                    id="check_in_time"
                                    name="check_in_time"
                                    type="time"
                                    value="<?= htmlspecialchars($form['check_in_time'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['check_in_time'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['check_in_time'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="check_out_time">Wymeldowanie do</label>
                                <input
                                    id="check_out_time"
                                    name="check_out_time"
                                    type="time"
                                    value="<?= htmlspecialchars($form['check_out_time'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['check_out_time'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['check_out_time'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="minimum_nights">Minimum nocy</label>
                                <input
                                    id="minimum_nights"
                                    name="minimum_nights"
                                    type="number"
                                    min="1"
                                    step="1"
                                    value="<?= htmlspecialchars($form['minimum_nights'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['minimum_nights'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['minimum_nights'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="currency">Waluta</label>
                                <input
                                    id="currency"
                                    name="currency"
                                    type="text"
                                    maxlength="3"
                                    value="<?= htmlspecialchars($form['currency'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['currency'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['currency'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field--full settings-section-heading">
                                <h2>Cennik podstawowy</h2>
                                <p>
                                    Cena za jedną noc zależnie od całkowitej długości pobytu.
                                    Ceny dotyczą całego domku za dobę.
                                </p>
                            </div>

                            <div class="form-field">
                                <label for="price_one_night">Pobyt 1 noc — cena za noc</label>
                                <input id="price_one_night" name="price_one_night" type="number" min="1" step="1"
                                    value="<?= htmlspecialchars($form['price_one_night'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="price_two_nights">Pobyt 2 noce — cena za noc</label>
                                <input id="price_two_nights" name="price_two_nights" type="number" min="1" step="1"
                                    value="<?= htmlspecialchars($form['price_two_nights'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="price_three_nights">Pobyt 3 noce — cena za noc</label>
                                <input id="price_three_nights" name="price_three_nights" type="number" min="1" step="1"
                                    value="<?= htmlspecialchars($form['price_three_nights'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="price_four_nights">Pobyt 4 noce — cena za noc</label>
                                <input id="price_four_nights" name="price_four_nights" type="number" min="1" step="1"
                                    value="<?= htmlspecialchars($form['price_four_nights'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="price_five_nights">Pobyt 5 nocy — cena za noc</label>
                                <input id="price_five_nights" name="price_five_nights" type="number" min="1" step="1"
                                    value="<?= htmlspecialchars($form['price_five_nights'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="price_six_nights">Pobyt 6 nocy — cena za noc</label>
                                <input id="price_six_nights" name="price_six_nights" type="number" min="1" step="1"
                                    value="<?= htmlspecialchars($form['price_six_nights'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="price_seven_plus_nights">Pobyt 7+ nocy — cena za noc</label>
                                <input id="price_seven_plus_nights" name="price_seven_plus_nights" type="number" min="1" step="1"
                                    value="<?= htmlspecialchars($form['price_seven_plus_nights'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="fishing_price">Łowienie w jeziorze / dzień</label>
                                <input
                                    id="fishing_price"
                                    name="fishing_price"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['fishing_price'], ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <?php if (isset($errors['fishing_price'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['fishing_price'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="hot_tub_price">Balia / kubil</label>
                                <input
                                    id="hot_tub_price"
                                    name="hot_tub_price"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['hot_tub_price'], ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <?php if (isset($errors['hot_tub_price'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['hot_tub_price'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field--full settings-section-heading">
                                <h2>Płatności i zadatek</h2>

                                <p>
                                    Dane wykorzystywane przy przygotowywaniu wiadomości z prośbą o wpłatę zadatku.
                                </p>
                            </div>

                            <div class="form-field">
                                <label for="deposit_amount">Kwota zadatku</label>

                                <input
                                    id="deposit_amount"
                                    name="deposit_amount"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['deposit_amount'], ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <?php if (isset($errors['deposit_amount'])): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars($errors['deposit_amount'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="bank_account_holder">Odbiorca przelewu</label>

                                <input
                                    id="bank_account_holder"
                                    name="bank_account_holder"
                                    type="text"
                                    value="<?= htmlspecialchars($form['bank_account_holder'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-field form-field--full">
                                <label for="bank_account_number">Numer rachunku bankowego</label>

                                <input
                                    id="bank_account_number"
                                    name="bank_account_number"
                                    type="text"
                                    value="<?= htmlspecialchars($form['bank_account_number'], ENT_QUOTES, 'UTF-8') ?>"
                                    placeholder="PL00 0000 0000 0000 0000 0000 0000"
                                >
                            </div>

                            <div class="form-field form-field--full settings-section-heading">
                                <h2>Treści publiczne i zasady</h2>

                                <p>
                                    Informacje wykorzystywane na stronie publicznej oraz przy prezentowaniu zasad pobytu.
                                </p>
                            </div>

                            <div class="form-field form-field--full">
                                <label for="public_short_description">Krótki opis publiczny</label>
                                <textarea
                                    id="public_short_description"
                                    name="public_short_description"
                                    rows="4"
                                ><?= htmlspecialchars($form['public_short_description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="form-field form-field--full">
                                <label for="booking_rules">Zasady pobytu</label>
                                <textarea
                                    id="booking_rules"
                                    name="booking_rules"
                                    rows="5"
                                ><?= htmlspecialchars($form['booking_rules'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button
                                class="button button--primary"
                                type="submit"
                                <?= $canSave ? '' : 'disabled' ?>
                            >
                                Zapisz ustawienia
                            </button>

                            <a class="button button--secondary" href="/admin">
                                Wróć do panelu
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
