<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var int $id
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var string|null $databaseMessage
 * @var bool $canSave
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

                            <h1>Edytuj gościa</h1>

                            <p>
                                Zmień dane kontaktowe, źródło, notatki oraz oznaczenie VIP.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--secondary"
                                href="/admin/goscie/pokaz?id=<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>"
                            >
                                Szczegóły
                            </a>

                            <a class="button button--secondary" href="/admin/goscie">
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
                    View::partial('partials/guest_form', [
                        'form' => $form,
                        'errors' => $errors,
                        'canSave' => $canSave,
                        'action' => '/admin/goscie/edytuj?id=' . $id,
                        'submitLabel' => 'Zapisz zmiany',
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>