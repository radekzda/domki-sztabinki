<?php

declare(strict_types=1);

/**
 * @var string $title
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
                    <a class="is-active" href="/admin/goscie">Goście</a>
                    <a href="/admin/zapytania">Zapytania</a>
                    <a href="/admin/kalendarz">Kalendarz</a>
                    <a href="/admin/ustawienia">Ustawienia</a>
                    <a href="/admin/system">System</a>
                </nav>
            </aside>

            <div class="admin-content">
                <div class="panel">
                    <p class="eyebrow">Goście</p>

                    <h1>Goście</h1>

                    <p>
                        Tu dodamy kartotekę gości, dane kontaktowe, historię pobytów i notatki.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>