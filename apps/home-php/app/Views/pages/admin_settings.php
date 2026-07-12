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
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'settings']); ?>

            <div class="admin-content">
                <div class="panel">
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

                    <form class="form form--wide" method="post" action="/admin/ustawienia">
                        <div class="form-grid">
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