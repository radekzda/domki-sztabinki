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
            <?php View::partial('partials/admin_sidebar', ['active' => 'guests']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Goście</p>

                            <h1>Import gości CSV</h1>

                            <p>
                                Neutralny import CSV bez zależności od Base44.
                                Istniejący gość jest dopasowywany najpierw po
                                adresie e-mail, a następnie po numerze telefonu.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/templates/import-goscie.csv">
                                Pobierz wzór CSV
                            </a>

                            <a class="button button--secondary" href="/admin/goscie">
                                Wróć do gości
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
                        action="/admin/goscie/import"
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
                                Wymagane kolumny:
                                <code>first_name;last_name;email</code>.
                            </p>

                            <p>
                                Opcjonalnie możesz dodać:
                                <code>phone</code>,
                                <code>street</code>,
                                <code>postal_code</code>,
                                <code>city</code>,
                                <code>country</code>,
                                <code>full_address</code>,
                                PESEL, numer dokumentu, datę urodzenia,
                                notatki i źródło.
                            </p>

                            <p>
                                Przy ponownym imporcie gość zostanie rozpoznany
                                po e-mailu albo telefonie. Źródło istniejącego
                                gościa nie jest nadpisywane.
                            </p>
                        </div>

                        <div class="form-actions">
                            <button class="button button--primary" type="submit">
                                Importuj gości
                            </button>

                            <a class="button button--secondary" href="/admin/goscie">
                                Anuluj
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
