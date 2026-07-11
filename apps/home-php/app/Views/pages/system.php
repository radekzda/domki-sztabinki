<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, string> $checks
 */
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

                    <h1>Status środowiska</h1>

                    <p>
                        Ten ekran służy do sprawdzenia podstawowej konfiguracji wersji PHP.
                        Nie pokazuje haseł ani sekretów.
                    </p>

                    <div class="status-list">
                        <?php foreach ($checks as $label => $value): ?>
                            <div class="status-row">
                                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                <strong><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="admin-actions">
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