<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var string|null $databaseMessage
 * @var bool $canSave
 */

$invoiceSellers =
    invoiceSellersForCabinForm();
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

                    <?php
                    View::partial('partials/cabin_form', [
                        'form' => $form,
                        'errors' => $errors,
                        'invoiceSellers' =>
                            $invoiceSellers,
                        'canSave' => $canSave,
                        'action' => '/admin/domki/nowy',
                        'submitLabel' => 'Zapisz domek',
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>
