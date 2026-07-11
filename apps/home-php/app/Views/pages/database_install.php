<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var bool $canInstall
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
<section class="page-section">
    <div class="container">
        <div class="panel">
            <p class="eyebrow">System</p>

            <h1>Instalator bazy MySQL</h1>

            <p>
                Ten ekran uruchamia plik <strong>database/schema.sql</strong> i tworzy
                podstawowe tabele systemu Domki Sztabinki PMS.
            </p>

            <?php if (isset($message) && is_string($message) && $message !== ''): ?>
                <div class="alert alert--<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!$canInstall): ?>
                <div class="alert alert--warning">
                    Instalator jest zablokowany, ponieważ konfiguracja bazy danych nie jest jeszcze kompletna
                    albo lokalne PHP nie ma wymaganego rozszerzenia <strong>pdo_mysql</strong>.
                </div>
            <?php else: ?>
                <div class="alert alert--warning">
                    Kliknij przycisk tylko wtedy, gdy dane bazy w pliku <strong>.env</strong> są poprawne.
                    Operacja wykona polecenia SQL z pliku <strong>database/schema.sql</strong>.
                </div>
            <?php endif; ?>

            <div class="status-list">
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

            <form class="form" method="post" action="/admin/system/database/install">
                <div class="form-actions">
                    <button
                        class="button button--primary"
                        type="submit"
                        <?= $canInstall ? '' : 'disabled' ?>
                    >
                        Uruchom instalator bazy
                    </button>

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
</section>