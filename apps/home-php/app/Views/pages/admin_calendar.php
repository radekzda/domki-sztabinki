<?php

declare(strict_types=1);

/**
 * @var string $title
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'calendar']); ?>

            <div class="admin-content">
                <div class="panel">
                    <p class="eyebrow">Kalendarz</p>

                    <h1>Kalendarz</h1>

                    <p>
                        Tu dodamy widok zajętości domków, przyjazdy, wyjazdy i blokowanie terminów.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>