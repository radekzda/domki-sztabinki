<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var bool $canSeed
 * @var string|null $message
 * @var string $messageType
 * @var array<int, array{label: string, status: string, value: string}> $checks
 */

$statusLabels = [
    'success' => 'OK',
    'warning' => 'UWAGA',
    'danger' => 'BŁĄD',
    'neutral' => 'INFO',
];
?>
<?php View::partial('partials/admin_system_styles'); ?>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'system']); ?>

            <div class="admin-content">
                <div class="panel system-panel">
                    <p class="eyebrow">System</p>

                    <h1>Dane startowe</h1>

                    <p>
                        Ten ekran uruchamia plik <strong>database/seed.sql</strong> i dodaje
                        podstawowe domki startowe do systemu.
                    </p>

                    <?php if (isset($message) && is_string($message) && $message !== ''): ?>
                        <div class="alert alert--<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$canSeed): ?>
                        <div class="alert alert--warning">
                            Dane startowe są zablokowane, ponieważ konfiguracja bazy danych nie jest jeszcze kompletna
                            albo lokalne PHP nie ma wymaganego rozszerzenia <strong>pdo_mysql</strong>.
                        </div>
                    <?php else: ?>
                        <div class="alert alert--warning">
                            Uruchom dane startowe dopiero po instalacji struktury bazy.
                            Ponowne uruchomienie nie powinno zdublować domków startowych.
                        </div>
                    <?php endif; ?>

                    <div class="status-list system-status-list">
                        <?php foreach ($checks as $check): ?>
                            <?php
                            $status = $check['status'];
                            $statusLabel = $statusLabels[$status] ?? 'INFO';
                            ?>

                            <div class="status-row">
                                <span><?= htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8') ?></span>

                                <div class="status-row__right">
                                    <span class="status-badge status-badge--<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </span>

                                    <strong><?= htmlspecialchars($check['value'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form class="form system-form" method="post" action="/admin/system/database/seed">
    <?= csrfField() ?>
                        <div class="form-actions system-actions">
                            <button
                                class="button button--primary"
                                type="submit"
                                <?= $canSeed ? '' : 'disabled' ?>
                            >
                                Wgraj dane startowe
                            </button>

                            <a class="button button--secondary" href="/admin/system/database/install">
                                Instalator struktury bazy
                            </a>

                            <a class="button button--secondary" href="/admin/system/database">
                                Wróć do diagnostyki MySQL
                            </a>

                            <a class="button button--secondary" href="/admin">
                                Wróć do panelu
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
