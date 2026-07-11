<?php

declare(strict_types=1);

/**
 * @var string $title
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
        <div class="admin-shell">
            <aside class="admin-sidebar">
                <p class="admin-sidebar__title">Panel PMS</p>

                <nav class="admin-menu" aria-label="Nawigacja panelu">
                    <a href="/admin">Dashboard</a>
                    <a href="/admin/domki">Domki</a>
                    <a href="/admin/rezerwacje">Rezerwacje</a>
                    <a href="/admin/goscie">Goście</a>
                    <a href="/admin/zapytania">Zapytania</a>
                    <a href="/admin/kalendarz">Kalendarz</a>
                    <a href="/admin/ustawienia">Ustawienia</a>
                    <a class="is-active" href="/admin/system">System</a>
                </nav>
            </aside>

            <div class="admin-content">
                <div class="panel">
                    <p class="eyebrow">System</p>

                    <h1>Połączenie z bazą MySQL</h1>

                    <p>
                        Ten ekran sprawdza konfigurację bazy danych dla wersji PHP.
                        Hasło i dane wrażliwe nie są tutaj wyświetlane.
                    </p>

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

                    <div class="admin-actions">
                        <a class="button button--primary" href="/admin/system/database/install">
                            Instalator struktury bazy
                        </a>

                        <a class="button button--secondary" href="/admin/system">
                            Wróć do statusu środowiska
                        </a>

                        <a class="button button--secondary" href="/admin">
                            Wróć do panelu
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>