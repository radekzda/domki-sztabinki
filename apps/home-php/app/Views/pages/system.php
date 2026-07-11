<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, string> $checks
 */
?>
<section class="page-section">
    <div class="container">
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
        </div>
    </div>
</section>