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
 * @var string|null $databaseMessage
 * @var bool $canSave
 * @var int|null $calculatedNights
 * @var int|null $calculatedTotalPrice
 */
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
                            </p>
                        </div>

                        <div class="page-header__actions">
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
                            Wyliczenie: <?= htmlspecialchars((string) $calculatedNights, ENT_QUOTES, 'UTF-8') ?>
                            noc. / <?= htmlspecialchars(number_format($calculatedTotalPrice, 0, ',', ' '), ENT_QUOTES, 'UTF-8') ?> zł
                        </div>
                    <?php endif; ?>

                    <form class="form form--wide" method="post" action="/admin/rezerwacje/nowa">
                        <div class="form-grid">
                            <div class="form-field form-field--full">
                                <label for="cabin_id">Domek</label>
                                <select id="cabin_id" name="cabin_id" required>
                                    <option value="">Wybierz domek</option>

                                    <?php foreach ($cabins as $cabin): ?>
                                        <option
                                            value="<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                            <?= $form['cabin_id'] === (string) $cabin['id'] ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($cabin['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <?php if (isset($errors['cabin_id'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['cabin_id'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="start_date">Od</label>
                                <input
                                    id="start_date"
                                    name="start_date"
                                    type="date"
                                    value="<?= htmlspecialchars($form['start_date'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['start_date'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['start_date'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="end_date">Do</label>
                                <input
                                    id="end_date"
                                    name="end_date"
                                    type="date"
                                    value="<?= htmlspecialchars($form['end_date'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['end_date'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['end_date'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field--full">
                                <label for="guest_name">Imię i nazwisko gościa</label>
                                <input
                                    id="guest_name"
                                    name="guest_name"
                                    type="text"
                                    value="<?= htmlspecialchars($form['guest_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['guest_name'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['guest_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="email">E-mail</label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="<?= htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['email'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="phone">Telefon</label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="text"
                                    value="<?= htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="adults">Dorośli</label>
                                <input
                                    id="adults"
                                    name="adults"
                                    type="number"
                                    min="1"
                                    max="30"
                                    step="1"
                                    value="<?= htmlspecialchars($form['adults'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['adults'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['adults'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="children">Dzieci</label>
                                <input
                                    id="children"
                                    name="children"
                                    type="number"
                                    min="0"
                                    max="30"
                                    step="1"
                                    value="<?= htmlspecialchars($form['children'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['children'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['children'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="status">Status rezerwacji</label>
                                <select id="status" name="status">
                                    <option value="PENDING" <?= $form['status'] === 'PENDING' ? 'selected' : '' ?>>
                                        Oczekuje
                                    </option>
                                    <option value="CONFIRMED" <?= $form['status'] === 'CONFIRMED' ? 'selected' : '' ?>>
                                        Potwierdzona
                                    </option>
                                    <option value="CHECKED_IN" <?= $form['status'] === 'CHECKED_IN' ? 'selected' : '' ?>>
                                        Zameldowany
                                    </option>
                                    <option value="CHECKED_OUT" <?= $form['status'] === 'CHECKED_OUT' ? 'selected' : '' ?>>
                                        Wymeldowany
                                    </option>
                                    <option value="CANCELLED" <?= $form['status'] === 'CANCELLED' ? 'selected' : '' ?>>
                                        Anulowana
                                    </option>
                                </select>

                                <?php if (isset($errors['status'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="payment_status">Status płatności</label>
                                <select id="payment_status" name="payment_status">
                                    <option value="PENDING" <?= $form['payment_status'] === 'PENDING' ? 'selected' : '' ?>>
                                        Oczekuje
                                    </option>
                                    <option value="PARTIAL" <?= $form['payment_status'] === 'PARTIAL' ? 'selected' : '' ?>>
                                        Częściowa
                                    </option>
                                    <option value="PAID" <?= $form['payment_status'] === 'PAID' ? 'selected' : '' ?>>
                                        Opłacona
                                    </option>
                                    <option value="REFUNDED" <?= $form['payment_status'] === 'REFUNDED' ? 'selected' : '' ?>>
                                        Zwrócona
                                    </option>
                                </select>

                                <?php if (isset($errors['payment_status'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['payment_status'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="paid_amount">Wpłacono</label>
                                <input
                                    id="paid_amount"
                                    name="paid_amount"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['paid_amount'], ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <?php if (isset($errors['paid_amount'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['paid_amount'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="source">Źródło</label>
                                <select id="source" name="source">
                                    <option value="MANUAL" <?= $form['source'] === 'MANUAL' ? 'selected' : '' ?>>
                                        Ręcznie
                                    </option>
                                    <option value="WWW" <?= $form['source'] === 'WWW' ? 'selected' : '' ?>>
                                        WWW
                                    </option>
                                    <option value="BOOKING" <?= $form['source'] === 'BOOKING' ? 'selected' : '' ?>>
                                        Booking
                                    </option>
                                    <option value="PHONE" <?= $form['source'] === 'PHONE' ? 'selected' : '' ?>>
                                        Telefon
                                    </option>
                                    <option value="AIRBNB" <?= $form['source'] === 'AIRBNB' ? 'selected' : '' ?>>
                                        Airbnb
                                    </option>
                                </select>
                            </div>

                            <div class="form-field form-field--full">
                                <label for="notes">Notatki</label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="4"
                                ><?= htmlspecialchars($form['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button
                                class="button button--primary"
                                type="submit"
                                <?= $canSave ? '' : 'disabled' ?>
                            >
                                Zapisz rezerwację
                            </button>

                            <a class="button button--secondary" href="/admin/rezerwacje">
                                Anuluj
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>