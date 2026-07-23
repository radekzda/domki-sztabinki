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

                            <h1>Import rezerwacji CSV</h1>

                            <p>
                                Neutralny import CSV bez zależności od Base44.
                                Domek jest rozpoznawany po skrócie lub nazwie,
                                a gość po e-mailu lub numerze telefonu.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/templates/import-rezerwacje.csv">
                                Pobierz wzór CSV
                            </a>

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

                    <form
                        method="post"
                        action="/admin/rezerwacje/import"
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
                                <code>cabin;first_name;last_name;email;check_in;check_out</code>.
                            </p>

                            <p>
                                W kolumnie <code>cabin</code> wpisz skrót domku,
                                np. <code>D5</code>, albo jego pełną nazwę.
                                Gość zostanie znaleziony po e-mailu lub telefonie.
                                Jeżeli gościa nie ma, zostanie utworzony.
                            </p>

                            <p>
                                Ponowny import aktualizuje rezerwację o tym samym
                                domku, gościu i terminie.
                                <code>room_id</code> i <code>guest_id</code>
                                nie są używane.
                            </p>

                            <p>
                                Jeżeli <code>total_price</code> jest puste,
                                importer wyliczy wartość z cennika domku.
                                Status płatności jest ustalany na podstawie
                                <code>paid_amount</code> i <code>total_price</code>.
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
