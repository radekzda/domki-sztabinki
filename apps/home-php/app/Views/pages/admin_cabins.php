<?php

declare(strict_types=1);

/**
 * @var string $title
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'cabins']); ?>

            <div class="admin-content">
                <div class="panel">
                    <p class="eyebrow">Domki</p>

                    <h1>Domki</h1>

                    <p>
                        Tu dodamy listę domków, ceny, zdjęcia, widoczność publiczną i edycję opisu.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>