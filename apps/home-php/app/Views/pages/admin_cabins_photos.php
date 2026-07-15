<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array{
 *     id: int,
 *     name: string,
 *     short_name: string|null,
 *     description: string,
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
 * } $cabin
 * @var array<int, array{
 *     id: int,
 *     cabin_id: int,
 *     image_path: string,
 *     alt_text: string|null,
 *     sort_order: int,
 *     is_main: int,
 *     created_at: string
 * }> $images
 * @var string|null $databaseMessage
 * @var string|null $successMessage
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

                            <h1>Zdjęcia domku</h1>

                            <p>
                                Zarządzanie zdjęciami dla:
                                <strong><?= htmlspecialchars($cabin['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--secondary"
                                href="/admin/domki/edytuj?id=<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                Edytuj domek
                            </a>

                            <a class="button button--secondary" href="/admin/domki">
                                Wróć do domków
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

                    <form
                        class="form form--wide"
                        method="post"
                        action="/admin/domki/zdjecia/dodaj"
                        enctype="multipart/form-data"
                    >
    <?= csrfField() ?>
                        <input
                            type="hidden"
                            name="cabin_id"
                            value="<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                        >

                        <div class="form-grid">
                            <div class="form-field">
                                <label for="image">Zdjęcie</label>
                                <input
                                    id="image"
                                    name="image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,image/gif"
                                    required
                                >
                            </div>

                            <div class="form-field">
                                <label for="alt_text">Opis alternatywny</label>
                                <input
                                    id="alt_text"
                                    name="alt_text"
                                    type="text"
                                    value="<?= htmlspecialchars($cabin['name'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="is_main">Zdjęcie główne</label>
                                <select id="is_main" name="is_main">
                                    <option value="0">Nie</option>
                                    <option value="1" <?= $images === [] ? 'selected' : '' ?>>Tak</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button class="button button--primary" type="submit">
                                Dodaj zdjęcie
                            </button>
                        </div>
                    </form>

                    <?php if ($images === []): ?>
                        <div class="empty-state">
                            <strong>Brak zdjęć</strong>

                            <p>
                                Dodaj pierwsze zdjęcie domku. Jeżeli nie wybierzesz inaczej,
                                pierwsze zdjęcie będzie ustawione jako główne.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Podgląd</th>
                                        <th>Ścieżka</th>
                                        <th>Opis</th>
                                        <th>Status</th>
                                        <th>Dodano</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($images as $image): ?>
                                        <tr>
                                            <td>
                                                <img
                                                    src="<?= htmlspecialchars($image['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                                    alt="<?= htmlspecialchars($image['alt_text'] ?? $cabin['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                    style="width: 140px; height: 90px; object-fit: cover; border-radius: 12px; border: 1px solid #e5e7eb;"
                                                >
                                            </td>

                                            <td>
                                                <code><?= htmlspecialchars($image['image_path'], ENT_QUOTES, 'UTF-8') ?></code>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($image['alt_text'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?php if ($image['is_main'] === 1): ?>
                                                    <span class="status-pill status-pill--success">Główne</span>
                                                <?php else: ?>
                                                    <span class="status-pill status-pill--muted">Dodatkowe</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(formatDateForDisplay($image['created_at']), ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <div class="table-actions">
                                                    <?php if ($image['is_main'] !== 1): ?>
                                                        <form method="post" action="/admin/domki/zdjecia/glowne">
    <?= csrfField() ?>
                                                            <input
                                                                type="hidden"
                                                                name="cabin_id"
                                                                value="<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="image_id"
                                                                value="<?= htmlspecialchars((string) $image['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                            >

                                                            <button class="button button--primary button--small" type="submit">
                                                                Ustaw główne
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form
                                                        method="post"
                                                        action="/admin/domki/zdjecia/usun"
                                                        onsubmit="return confirm('Czy na pewno usunąć to zdjęcie?')"
                                                    >
    <?= csrfField() ?>
                                                        <input
                                                            type="hidden"
                                                            name="cabin_id"
                                                            value="<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="image_id"
                                                            value="<?= htmlspecialchars((string) $image['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <button class="button button--secondary button--small" type="submit">
                                                            Usuń
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
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