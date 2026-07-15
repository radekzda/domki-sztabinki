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

                            <h1>Import domków Base44</h1>

                            <p>
                                Wgraj plik CSV z eksportu Base44. Importer dopasuje domki po ID Base44,
                                skrócie D1–D4 albo nazwie domku.
                            </p>
                        </div>

                        <div class="page-header__actions">
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

                    <form method="post" action="/admin/domki/import" enctype="multipart/form-data" class="form-grid">
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
                            <strong>Mapowanie danych</strong>

                            <p>
                                Base44 <code>id</code> zapisujemy jako <code>external_id</code>.
                                Numer <code>01</code> zostanie dopasowany do skrótu <code>D1</code>,
                                <code>02</code> do <code>D2</code> itd.
                            </p>

                            <p>
                                Import drugi raz nie powinien tworzyć duplikatów. Zaktualizuje istniejące domki
                                po <code>external_id</code>, <code>short_name</code> albo nazwie.
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