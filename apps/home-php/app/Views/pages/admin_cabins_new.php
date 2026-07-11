<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var string|null $databaseMessage
 * @var bool $canSave
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'cabins']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Domki</p>

                            <h1>Dodaj domek</h1>

                            <p>
                                Uzupełnij dane domku. Po podłączeniu bazy MySQL formularz zapisze dane
                                bezpośrednio do tabeli <strong>cabins</strong>.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/domki">
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

                    <form class="form form--wide" method="post" action="/admin/domki/nowy">
                        <div class="form-grid">
                            <div class="form-field form-field--full">
                                <label for="name">Nazwa domku</label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    value="<?= htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['name'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="short_name">Skrót</label>
                                <input
                                    id="short_name"
                                    name="short_name"
                                    type="text"
                                    value="<?= htmlspecialchars($form['short_name'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="sort_order">Kolejność</label>
                                <input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['sort_order'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-field form-field--full">
                                <label for="description">Opis</label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="5"
                                    required
                                ><?= htmlspecialchars($form['description'], ENT_QUOTES, 'UTF-8') ?></textarea>

                                <?php if (isset($errors['description'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['description'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="max_guests">Maksymalna liczba osób</label>
                                <input
                                    id="max_guests"
                                    name="max_guests"
                                    type="number"
                                    min="1"
                                    max="30"
                                    step="1"
                                    value="<?= htmlspecialchars($form['max_guests'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['max_guests'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['max_guests'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="bedrooms">Sypialnie</label>
                                <input
                                    id="bedrooms"
                                    name="bedrooms"
                                    type="number"
                                    min="0"
                                    max="20"
                                    step="1"
                                    value="<?= htmlspecialchars($form['bedrooms'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-field">
                                <label for="bathrooms">Łazienki</label>
                                <input
                                    id="bathrooms"
                                    name="bathrooms"
                                    type="number"
                                    min="0"
                                    max="20"
                                    step="1"
                                    value="<?= htmlspecialchars($form['bathrooms'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-field">
                                <label for="is_active">Widoczność</label>
                                <select id="is_active" name="is_active">
                                    <option value="1" <?= $form['is_active'] === '1' ? 'selected' : '' ?>>
                                        Aktywny
                                    </option>
                                    <option value="0" <?= $form['is_active'] === '0' ? 'selected' : '' ?>>
                                        Ukryty
                                    </option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="price_per_night">Cena domyślna</label>
                                <input
                                    id="price_per_night"
                                    name="price_per_night"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['price_per_night'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-field">
                                <label for="price_one_night">1 noc</label>
                                <input
                                    id="price_one_night"
                                    name="price_one_night"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['price_one_night'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-field">
                                <label for="price_two_nights">2 noce</label>
                                <input
                                    id="price_two_nights"
                                    name="price_two_nights"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['price_two_nights'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-field">
                                <label for="price_three_nights">3 noce</label>
                                <input
                                    id="price_three_nights"
                                    name="price_three_nights"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['price_three_nights'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-field">
                                <label for="price_four_nights">4 noce</label>
                                <input
                                    id="price_four_nights"
                                    name="price_four_nights"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['price_four_nights'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-field">
                                <label for="price_five_nights">5 nocy</label>
                                <input
                                    id="price_five_nights"
                                    name="price_five_nights"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['price_five_nights'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-field">
                                <label for="price_six_nights">6 nocy</label>
                                <input
                                    id="price_six_nights"
                                    name="price_six_nights"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['price_six_nights'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-field">
                                <label for="price_seven_plus_nights">7+ nocy</label>
                                <input
                                    id="price_seven_plus_nights"
                                    name="price_seven_plus_nights"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['price_seven_plus_nights'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-actions">
                            <button
                                class="button button--primary"
                                type="submit"
                                <?= $canSave ? '' : 'disabled' ?>
                            >
                                Zapisz domek
                            </button>

                            <a class="button button--secondary" href="/admin/domki">
                                Anuluj
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>