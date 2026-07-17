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
<style>
    .media-page {
        min-width: 0;
    }

    .media-panel {
        padding: 28px;
    }

    /*
     * Naglowek strony
     */
    .media-upload 0;
    }

    .media-panel {
        padding: 28px;
    }

    /*
     *-panel > .page-header {
        margin-bottom: 20px;
    }

    .media-upload-panel > .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .media-upload-panel > .page-header > div > p {
        max-width: 760px;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .media-upload-panel > .page-header h2 {
        margin: 26px 0 6px !important;
        font-size: 20px;
        line-height: 1.2;
        color: #111827;
    }

    .media-upload-panel > .page-header h2 + p {
        font-size: 12px;
        color: #9ca3af;
    }

    /*
     * Formularz dodawania zdjecia
     */
    .media-upload-form {
        max-width: none !important;
        margin-top: 0;
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #f8fafc;
    }

    .media-upload-form .form-grid {
        display: grid;
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
        gap: 16px;
    }

    .media-upload-form .form-field {
        margin: 0;
    }

    .media-upload-form .form-field label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        line-height: 1.25;
        font-weight: 700;
        color: #374151;
    }

    .media-upload-form input[type="text"],
    .media-upload-form input[type="number"],
    .media-upload-form select {
        width: 100%;
        min-height: 42px;
        padding: 9px 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #ffffff;
        font-size: 13px;
        color: #111827;
    }

    .media-upload-form input[type="file"] {
        width: 100%;
        min-height: 44px;
        padding: 7px;
        border: 1px dashed #cbd5e1;
        border-radius: 9px;
        background: #ffffff;
        font-size: 13px;
        color: #374151;
    }

    .media-upload-form input[type="file"]::file-selector-button {
        min-height: 30px;
        margin-right: 10px;
        padding: 5px 11px;
        border: 0;
        border-radius: 7px;
        background: #e5e7eb;
        color: #111827;
        font-weight: 600;
        cursor: pointer;
    }

    /*
     * Checkbox zdjecia glownego
     */
    .media-main-checkbox {
        padding: 11px 13px;
        display: flex;
        align-items: center;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        background: #ffffff;
    }

    .media-main-checkbox label {
        margin: 0 !important;
        display: inline-flex !important;
        align-items: center;
        gap: 9px;
        font-size: 13px !important;
        cursor: pointer;
    }

    .media-main-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin: 0;
        accent-color: #15803d;
    }

    .media-upload-form .form-actions {
        margin-top: 16px;
    }

    .media-upload-form .form-actions .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 9px;
        font-size: 13px;
    }

    /*
     * Sekcje galerii
     */
    .media-gallery-section {
        margin-top: 16px;
        padding: 24px 28px;
    }

    .media-gallery-section > .page-header {
        margin-bottom: 18px;
    }

    .media-gallery-section > .page-header .eyebrow {
        margin-bottom: 4px;
        font-size: 10px;
        color: #9ca3af;
    }

    .media-gallery-section > .page-header h2 {
        margin: 0;
        font-size: 20px;
        line-height: 1.2;
        color: #111827;
    }

    /*
     * Siatka zdjec
     */
    .media-gallery-grid {
        display: grid;
        grid-template-columns: repeat(
            auto-fill,
            minmax(220px, 1fr)
        );
        gap: 16px;
    }

    .media-image-card {
        min-width: 0;
        margin: 0 !important;
        padding: 12px !important;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.035);
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease;
    }

    .media-image-card:hover {
        transform: translateY(-2px);
        box-shadow:
            0 4px 8px rgba(15, 23, 42, 0.04),
            0 12px 26px rgba(15, 23, 42, 0.07);
    }

    .media-image-card__image {
        display: block;
        width: 100%;
        height: 160px;
        object-fit: cover;
        border: 0;
        border-radius: 9px;
        background: #f3f4f6;
    }

    .media-image-card__body {
        padding: 11px 2px 2px;
    }

    .media-image-card__title {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 13px;
        font-weight: 700;
        color: #111827;
    }

    .media-image-card__main {
        display: inline-flex;
        align-items: center;
        min-height: 22px;
        padding: 3px 7px;
        border-radius: 999px;
        background: #dcfce7;
        color: #166534;
        font-size: 10px;
        font-weight: 700;
    }

    .media-image-card__meta {
        margin: 5px 0 0;
        font-size: 12px;
        line-height: 1.35;
        color: #6b7280;
        overflow-wrap: anywhere;
    }

    /*
     * Akcje zdjecia
     */
    .media-image-actions {
        margin-top: 12px !important;
        display: grid !important;
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
        gap: 7px !important;
    }

    .media-image-actions form {
        margin: 0;
    }

    .media-image-actions .button {
        width: 100%;
        min-height: 34px;
        padding: 7px 9px;
        border-radius: 8px;
        font-size: 11px;
        line-height: 1.2;
    }

    .media-image-actions .button--danger {
        background: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }

    .media-image-actions .button--danger:hover {
        background: #dc2626;
        border-color: #dc2626;
    }

    /*
     * Puste sekcje
     */
    .media-gallery-section .empty-state {
        padding: 22px;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        background: #fafafa;
    }

    /*
     * Responsive
     */
    @media (max-width: 900px) {
        .media-panel {
            padding: 22px;
        }

        .media-upload-form .form-grid {
            grid-template-columns: 1fr;
        }

        .media-gallery-section {
            padding: 22px;
        }
    }

    @media (max-width: 600px) {
        .media-panel,
        .media-gallery-section {
            padding: 16px;
        }

        .media-upload-panel > .page-header h1 {
            font-size: 27px;
        }

        .media-gallery-grid {
            grid-template-columns: 1fr;
        }

        .media-image-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
    <?php View::partial('partials/admin_sidebar', ['active' => 'media']); ?>

    <main class="admin-main media-page">
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

        <section class="panel media-panel media-upload-panel">
            <div class="page-header">
                <div>
                    <h1>Media i galeria</h1>

                    <p>
                        Dodaj zdjęcia używane na stronie głównej, w galerii publicznej
                        oraz w sekcjach atrakcji i otoczenia.
                    </p>

                    <h2 style="margin-top: 28px;">Dodaj zdjęcie</h2>

                    <p>
                        Obsługiwane formaty: JPG, PNG, WEBP. Zdjęcia zostaną zapisane w katalogu
                        <strong>public/uploads/site</strong>.
                    </p>
                </div>
            </div>

            <form class="form form--wide media-upload-form" method="post" action="/admin/media" enctype="multipart/form-data">
    <?= csrfField() ?>
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
                            accept=".jpg,.jpeg,.jfif,.png,.webp,image/jpeg,image/jfif,image/png,image/webp"
                            required
                        >
                    </div>

                    <div class="form-field form-field--full media-main-checkbox">
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
            <section class="panel media-gallery-section">
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
                    <div class="media-gallery-grid">
                        <?php foreach ($imagesByType[$typeKey] as $image): ?>
                            <article class="media-image-card">
                                <img
                                    src="<?= htmlspecialchars($image['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($image['alt_text'] ?? 'Zdjęcie strony', ENT_QUOTES, 'UTF-8') ?>"
                                    class="media-image-card__image"
                                >

                                <div class="media-image-card__body">
                                    <div class="media-image-card__title">
                                        <span>
                                            <?= (int) $image['is_main'] === 1 ? 'Zdjęcie główne' : 'Zdjęcie' ?>
                                        </span>

                                        <?php if ((int) $image['is_main'] === 1): ?>
                                            <span class="media-image-card__main">
                                                Główne
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <p class="media-image-card__meta">
                                        <?= htmlspecialchars($image['alt_text'] ?? 'Bez opisu', ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <p class="media-image-card__meta">
                                        Kolejność:
                                        <?= htmlspecialchars((string) $image['sort_order'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>

                                <div class="form-actions media-image-actions">
                                    <form method="post" action="/admin/media">
    <?= csrfField() ?>
                                        <input type="hidden" name="action" value="set_main">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $image['id'], ENT_QUOTES, 'UTF-8') ?>">

                                        <button class="button button--secondary" type="submit">
                                            Ustaw jako główne
                                        </button>
                                    </form>

                                    <form method="post" action="/admin/media" onsubmit="return confirm('Usunąć to zdjęcie?');">
    <?= csrfField() ?>
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
    </div>
</section>
