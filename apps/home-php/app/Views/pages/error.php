<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $message
 */
?>
<section class="page-section">
    <div class="container">
        <div class="panel">
            <p class="eyebrow">Błąd</p>

            <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>

            <p>
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p>
                <a class="button button--primary" href="/">
                    Wróć na stronę główną
                </a>
            </p>
        </div>
    </div>
</section>