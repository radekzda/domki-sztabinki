<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, string> $checks
 */
?>
<?php View::partial('partials/admin_system_styles'); ?>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'system']); ?>

            <div class="admin-content">
                <div class="panel system-panel">
                    <p class="eyebrow">System</p>

                    <h1>Status środowiska</h1>

                    <p>
                        Ten ekran służy do sprawdzenia podstawowej konfiguracji wersji PHP.
                        Nie pokazuje haseł ani sekretów.
                    </p>

                    <div class="status-list system-status-list">
                        <?php foreach ($checks as $label => $value): ?>
                            <div class="status-row">
                                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                <strong><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="admin-actions system-actions">
                        <a class="button button--secondary" href="/admin/system/database">
                            Sprawdź bazę MySQL
                        </a>

                        <a class="button button--primary" href="/admin">
                            Wróć do panelu
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
