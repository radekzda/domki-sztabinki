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
                    <a class="is-active" href="/admin">Dashboard</a>
                    <a href="/admin/domki">Domki</a>
                    <a href="/admin/rezerwacje">Rezerwacje</a>
                    <a href="/admin/goscie">Goście</a>
                    <a href="/admin/zapytania">Zapytania</a>
                    <a href="/admin/kalendarz">Kalendarz</a>
                    <a href="/admin/ustawienia">Ustawienia</a>
                    <a href="/admin/system">System</a>
                </nav>
            </aside>

            <div class="admin-content">
                <div class="panel">
                    <p class="eyebrow">Dashboard</p>

                    <h1>Panel PMS</h1>

                    <p>
                        To jest wersja PHP systemu Domki Sztabinki PMS przygotowana pod zwykły hosting home.pl.
                    </p>

                    <div class="dashboard-grid">
                        <a class="dashboard-card" href="/admin/domki">
                            <strong>Domki</strong>
                            <span>Lista domków, ceny i zdjęcia.</span>
                        </a>

                        <a class="dashboard-card" href="/admin/rezerwacje">
                            <strong>Rezerwacje</strong>
                            <span>Pobyty, statusy i płatności.</span>
                        </a>

                        <a class="dashboard-card" href="/admin/goscie">
                            <strong>Goście</strong>
                            <span>Dane kontaktowe i historia pobytów.</span>
                        </a>

                        <a class="dashboard-card" href="/admin/zapytania">
                            <strong>Zapytania</strong>
                            <span>Formularz WWW i odpowiedzi.</span>
                        </a>

                        <a class="dashboard-card" href="/admin/kalendarz">
                            <strong>Kalendarz</strong>
                            <span>Widok zajętości domków.</span>
                        </a>

                        <a class="dashboard-card" href="/admin/system">
                            <strong>System</strong>
                            <span>Diagnostyka PHP i MySQL.</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>