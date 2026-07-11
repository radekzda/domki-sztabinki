<?php

declare(strict_types=1);

/**
 * @var string $title
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'inquiries']); ?>

            <div class="admin-content">
                <div class="panel">
                    <p class="eyebrow">Zapytania</p>

                    <h1>Zapytania</h1>

                    <p>
                        Tu dodamy zapytania z formularza WWW, statusy obsługi oraz szablony odpowiedzi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>