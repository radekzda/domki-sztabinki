<?php

declare(strict_types=1);

/**
 * @var string $title
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'guests']); ?>

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