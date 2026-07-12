<?php

declare(strict_types=1);

/**
 * @var array<int, array{
 *     id: int,
 *     image_url: string,
 *     alt_text: string|null,
 *     image_type: string,
 *     sort_order: int,
 *     is_main: int,
 *     created_at: string
 * }> $images
 * @var array<string, string> $typeLabels
 * @var string|null $databaseMessage
 * @var string|null $successMessage
 * @var string|null $errorMessage
 */

$typeLabels = isset($typeLabels) && is_array($typeLabels)
    ? $typeLabels
    : SiteImageRepository::typeLabels();

$images = isset($images) && is_array($images)
    ? $images
    : [];

$imagesByType = [];

foreach ($typeLabels as $typeKey => $typeLabel) {
    $imagesByType[$typeKey] = [];
}

foreach ($images as $image) {
    $imageType = (string) ($image['image_type'] ?? 'GALLERY');

    if (!isset($imagesByType[$imageType])) {
        $imagesByType[$imageType] = [];
    }

    $imagesByType[$imageType][] = $image;
}
?>

<div class="admin-shell">
    <?php View::partial('partials/admin_sidebar', ['active' => 'media']); ?>

    <main class="admin-main">
        <div class="page-header">
            <div>
                <p class="eyebrow">Strona publiczna</p>

                <h1>Media i galeria</h1>

                <p>
                    Tutaj dodasz zdjęcia używane na stronie głównej, w galerii publicznej
                    oraz w sekcjach atrakcji i otoczenia.
                </p>
            </div>
        </div>

        <?php if (isset($databaseMessage) && is_string($databaseMessage) && $databaseMessage !== ''): ?>
            <div class="alert alert--warning">
                <?= htmlspecialchars($databaseMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (isset($successMessage) && is_string($successMessage) && $successMessage !== ''): ?>
            <div class="alert alert--success">
                <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage) && is_string($errorMessage) && $errorMessage !== ''): ?>
            <div class="alert alert--danger">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2>Dodaj zdjęcie</h2>

                    <p>
                        Obsługiwane formaty: JPG, PNG, WEBP. Zdjęcia zostaną zapisane w katalogu
                        <strong>public/uploads/site</strong>.
                    </p>
                </div>
            </div>

            <form class="form form--wide" method="post" action="/admin/media" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">

                <div class="form-grid">
                    <div class="form-field">
                        <label for="image_type">Miejsce użycia</label>

                        <select id="image_type" name="image_type" required>
                            <?php foreach ($typeLabels as $typeKey => $typeLabel): ?>
                                <option value="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="sort_order">Kolejność</label>

                        <input
                            id="sort_order"
                            name="sort_order"
                            type="number"
                            min="0"
                            step="1"
                            value="0"
                        >
                    </div>

                    <div class="form-field form-field--full">
                        <label for="alt_text">Opis alternatywny zdjęcia</label>

                        <input
                            id="alt_text"
                            name="alt_text"
                            type="text"
                            value=""
                            placeholder="np. Pomost nad jeziorem Sztabinki"
                        >
                    </div>

                    <div class="form-field form-field--full">
                        <label for="image_file">Plik zdjęcia</label>

                        <input
                            id="image_file"
                            name="image_file"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                        >
                    </div>

                    <div class="form-field form-field--full">
                        <label>
                            <input type="checkbox" name="is_main" value="1">
                            Ustaw jako główne zdjęcie dla tej sekcji
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button button--primary" type="submit">
                        Dodaj zdjęcie
                    </button>
                </div>
            </form>
        </section>

        <?php foreach ($typeLabels as $typeKey => $typeLabel): ?>
            <section class="panel">
                <div class="page-header">
                    <div>
                        <p class="eyebrow">
                            <?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <h2><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                </div>

                <?php if (($imagesByType[$typeKey] ?? []) === []): ?>
                    <div class="empty-state">
                        <strong>Brak zdjęć w tej sekcji</strong>

                        <p>
                            Dodaj zdjęcie formularzem powyżej i wybierz odpowiednie miejsce użycia.
                        </p>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 18px;">
                        <?php foreach ($imagesByType[$typeKey] as $image): ?>
                            <article class="panel" style="margin: 0; padding: 14px;">
                                <img
                                    src="<?= htmlspecialchars($image['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($image['alt_text'] ?? 'Zdjęcie strony', ENT_QUOTES, 'UTF-8') ?>"
                                    style="width: 100%; height: 150px; object-fit: cover; border-radius: 12px; border: 1px solid #e5e7eb;"
                                >

                                <div style="margin-top: 12px;">
                                    <strong>
                                        <?= (int) $image['is_main'] === 1 ? 'Zdjęcie główne' : 'Zdjęcie' ?>
                                    </strong>

                                    <p style="margin: 6px 0 0; color: #6b7280;">
                                        <?= htmlspecialchars($image['alt_text'] ?? 'Bez opisu', ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <p style="margin: 6px 0 0; color: #6b7280;">
                                        Kolejność:
                                        <?= htmlspecialchars((string) $image['sort_order'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>

                                <div class="form-actions" style="display: grid; gap: 8px; margin-top: 12px;">
                                    <form method="post" action="/admin/media">
                                        <input type="hidden" name="action" value="set_main">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $image['id'], ENT_QUOTES, 'UTF-8') ?>">

                                        <button class="button button--secondary" type="submit">
                                            Ustaw jako główne
                                        </button>
                                    </form>

                                    <form method="post" action="/admin/media" onsubmit="return confirm('Usunąć to zdjęcie?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $image['id'], ENT_QUOTES, 'UTF-8') ?>">

                                        <button class="button button--danger" type="submit">
                                            Usuń
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </main>
</div>