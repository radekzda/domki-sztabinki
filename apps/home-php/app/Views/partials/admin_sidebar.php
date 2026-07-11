<?php

declare(strict_types=1);

/**
 * @var string $active
 */

$activeItem = isset($active) && is_string($active) ? $active : '';

$items = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'href' => '/admin',
    ],
    [
        'key' => 'cabins',
        'label' => 'Domki',
        'href' => '/admin/domki',
    ],
    [
        'key' => 'reservations',
        'label' => 'Rezerwacje',
        'href' => '/admin/rezerwacje',
    ],
    [
        'key' => 'guests',
        'label' => 'Goście',
        'href' => '/admin/goscie',
    ],
    [
        'key' => 'inquiries',
        'label' => 'Zapytania',
        'href' => '/admin/zapytania',
    ],
    [
        'key' => 'calendar',
        'label' => 'Kalendarz',
        'href' => '/admin/kalendarz',
    ],
    [
        'key' => 'settings',
        'label' => 'Ustawienia',
        'href' => '/admin/ustawienia',
    ],
    [
        'key' => 'system',
        'label' => 'System',
        'href' => '/admin/system',
    ],
];
?>
<aside class="admin-sidebar">
    <p class="admin-sidebar__title">Panel PMS</p>

    <nav class="admin-menu" aria-label="Nawigacja panelu">
        <?php foreach ($items as $item): ?>
            <?php
            $isActive = $item['key'] === $activeItem;
            ?>

            <a
                class="<?= $isActive ? 'is-active' : '' ?>"
                href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
            >
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>