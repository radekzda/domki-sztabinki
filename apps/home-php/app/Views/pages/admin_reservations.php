<?php

declare(strict_types=1);

/**
 * @var string $title
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'reservations']); ?>

            <div class="admin-content">
                <div class="panel">
                    <p class="eyebrow">Rezerwacje</p>

                    <h1>Rezerwacje</h1>

                    <p>
                        Tu dodamy listę rezerwacji, dodawanie pobytu, statusy, płatności i automatyczne liczenie ceny.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>