<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array{inserted:int,updated:int,skipped:int,total:int}|null $result
 * @var string|null $errorMessage
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

                            <h1>Import domków CSV</h1>

                            <p>
                                Neutralny import CSV bez zależności od Base44.
                                Istniejący domek jest rozpoznawany najpierw po
                                <code>short_name</code>, a następnie po nazwie.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/templates/import-domki.csv">
                                Pobierz wzór CSV
                            </a>

                            <a class="button button--secondary" href="/admin/domki">
                                Wróć do domków
                            </a>
                        </div>
                    </div>

                    <?php if (isset($errorMessage) && is_string($errorMessage) && $errorMessage !== ''): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (is_array($result)): ?>
                        <div class="alert alert--success">
                            Import zakończony.
                            Przetworzono:
                            <strong><?= htmlspecialchars((string) $result['total'], ENT_QUOTES, 'UTF-8') ?></strong>,
                            dodano:
                            <strong><?= htmlspecialchars((string) $result['inserted'], ENT_QUOTES, 'UTF-8') ?></strong>,
                            zaktualizowano:
                            <strong><?= htmlspecialchars((string) $result['updated'], ENT_QUOTES, 'UTF-8') ?></strong>,
                            pominięto:
                            <strong><?= htmlspecialchars((string) $result['skipped'], ENT_QUOTES, 'UTF-8') ?></strong>.
                        </div>
                    <?php endif; ?>

                    <form
                        method="post"
                        action="/admin/domki/import"
                        enctype="multipart/form-data"
                        class="form-grid"
                    >
                        <?= csrfField() ?>

                        <label>
                            Plik CSV

                            <input
                                type="file"
                                name="csv_file"
                                accept=".csv,text/csv"
                                required
                            >
                        </label>

                        <div class="empty-state" style="text-align: left;">
                            <strong>Jak przygotować CSV</strong>

                            <p>
                                Separator: <code>;</code>, kodowanie: UTF-8.
                                Wymagane są tylko kolumny:
                                <code>short_name;name</code>.
                            </p>

                            <p>
                                Opcjonalnie możesz dodać:
                                <code>description</code>,
                                <code>max_guests</code>,
                                <code>area_sqm</code>,
                                <code>bedrooms</code>,
                                <code>bathrooms</code>,
                                ceny, wyposażenie, lokalizację i status.
                            </p>

                            <p>
                                Przy ponownym imporcie domek zostanie zaktualizowany
                                po <code>short_name</code> albo nazwie.
                            </p>
                        </div>

                        <div class="form-actions">
                            <button class="button button--primary" type="submit">
                                Importuj domki
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
