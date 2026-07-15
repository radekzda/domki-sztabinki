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
            <?php View::partial('partials/admin_sidebar', ['active' => 'reservations']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Rezerwacje</p>

                            <h1>Import rezerwacji Base44</h1>

                            <p>
                                Wgraj plik CSV z eksportu Base44. Importer połączy rezerwacje z domkami
                                i gośćmi na podstawie identyfikatorów z Base44.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/rezerwacje">
                                Wróć do rezerwacji
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

                    <form method="post" action="/admin/rezerwacje/import" enctype="multipart/form-data" class="form-grid">
    <?= csrfField() ?>
                        <label>
                            Plik CSV z Base44

                            <input
                                type="file"
                                name="csv_file"
                                accept=".csv,text/csv"
                                required
                            >
                        </label>

                        <div class="empty-state" style="text-align: left;">
                            <strong>Ważne przed importem</strong>

                            <p>
                                Najpierw powinny być zaimportowane domki i goście z Base44.
                                Rezerwacje są dopasowywane do domków przez <code>room_id</code>
                                oraz do gości przez <code>guest_id</code>.
                            </p>

                            <p>
                                Import można uruchomić drugi raz. Istniejące rezerwacje zostaną zaktualizowane
                                po <code>external_id</code>, a nie zdublowane.
                            </p>
                        </div>

                        <div class="form-actions">
                            <button class="button button--primary" type="submit">
                                Importuj rezerwacje
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