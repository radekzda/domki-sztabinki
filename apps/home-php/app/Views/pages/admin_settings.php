<?php

declare(strict_types=1);

/**
 * @var string $title
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'settings']); ?>

            <div class="admin-content">
                <div class="panel">
                    <p class="eyebrow">Ustawienia</p>

                    <h1>Ustawienia</h1>

                    <p>
                        Tu dodamy dane obiektu, sezon, minimalną długość pobytu, godziny zameldowania i wymeldowania.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>