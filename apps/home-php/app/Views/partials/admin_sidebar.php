<?php

declare(strict_types=1);

/**
 * @var string|null $active
 */

$active = isset($active) && is_string($active) ? $active : '';

$items = [
    [
        'href' => '/admin',
        'label' => 'Dashboard',
        'active' => 'dashboard',
    ],
    [
        'href' => '/admin/kalendarz',
        'label' => 'Kalendarz',
        'active' => 'calendar',
    ],
    [
        'href' => '/admin/domki',
        'label' => 'Domki',
        'active' => 'cabins',
    ],
    [
        'href' => '/admin/rezerwacje',
        'label' => 'Rezerwacje',
        'active' => 'reservations',
    ],
    [
        'href' => '/admin/faktury',
        'label' => 'Faktury',
        'active' => 'invoices',
    ],
    [
        'href' => '/admin/goscie',
        'label' => 'Goście',
        'active' => 'guests',
    ],
    [
        'href' => '/admin/zapytania',
        'label' => 'Zapytania',
        'active' => 'inquiries',
    ],
    [
        'href' => '/admin/media',
        'label' => 'Media',
        'active' => 'media',
    ],
    [
        'href' => '/admin/szablony',
        'label' => 'Szablony',
        'active' => 'templates',
    ],
    [
        'href' => '/admin/raporty',
        'label' => 'Raporty',
        'active' => 'reports',
    ],
    [
        'href' => '/admin/sprzedawcy-faktur',
        'label' => 'Sprzedawcy faktur',
        'active' => 'invoice_sellers',
    ],
    [
        'href' => '/admin/ustawienia',
        'label' => 'Ustawienia',
        'active' => 'settings',
    ],
    [
        'href' => '/admin/system',
        'label' => 'System',
        'active' => 'system',
    ],
    [
        'href' => '/admin/system/importy',
        'label' => 'Importy',
        'active' => 'imports',
    ],
];
?>

<aside class="admin-sidebar">
    <nav class="admin-sidebar__nav" aria-label="Menu administratora">
        <?php foreach ($items as $item): ?>
            <?php
            $itemHref = (string) $item['href'];
            $itemLabel = (string) $item['label'];
            $itemActive = (string) $item['active'];
            $isActive = $itemActive === $active;
            ?>

            <a
                class="admin-sidebar__link <?= $isActive ? 'admin-sidebar__link--active' : '' ?>"
                href="<?= htmlspecialchars($itemHref, ENT_QUOTES, 'UTF-8') ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>
            >
                <?= htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
</nav>
</aside>
