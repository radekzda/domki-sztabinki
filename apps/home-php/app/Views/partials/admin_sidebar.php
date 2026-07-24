<?php

declare(strict_types=1);

/**
 * @var string|null $active
 */

$active = isset($active)
    && is_string($active)
        ? $active
        : '';

$isAdministrator =
    class_exists('Auth')
    && Auth::isAdministrator();

$items = [
    [
        'href' => '/admin',
        'label' => 'Dashboard',
        'active' => 'dashboard',
        'admin_only' => false,
    ],
    [
        'href' => '/admin/kalendarz',
        'label' => 'Kalendarz',
        'active' => 'calendar',
        'admin_only' => false,
    ],
    [
        'href' => '/admin/domki',
        'label' => 'Domki',
        'active' => 'cabins',
        'admin_only' => false,
    ],
    [
        'href' => '/admin/rezerwacje',
        'label' => 'Rezerwacje',
        'active' => 'reservations',
        'admin_only' => false,
    ],
    [
        'href' => '/admin/faktury',
        'label' => 'Faktury',
        'active' => 'invoices',
        'admin_only' => true,
    ],
    [
        'href' => '/admin/goscie',
        'label' => 'Goście',
        'active' => 'guests',
        'admin_only' => false,
    ],
    [
        'href' => '/admin/zapytania',
        'label' => 'Zapytania',
        'active' => 'inquiries',
        'admin_only' => false,
    ],
    [
        'href' => '/admin/raporty',
        'label' => 'Raporty',
        'active' => 'reports',
        'admin_only' => true,
    ],
    [
        'href' => '/admin/media',
        'label' => 'Media',
        'active' => 'media',
        'admin_only' => true,
    ],
    [
        'href' => '/admin/szablony',
        'label' => 'Szablony',
        'active' => 'templates',
        'admin_only' => true,
    ],
    [
        'href' => '/admin/sprzedawcy-faktur',
        'label' => 'Sprzedawcy faktur',
        'active' => 'invoice_sellers',
        'admin_only' => true,
    ],
    [
        'href' => '/admin/uzytkownicy',
        'label' => 'Użytkownicy',
        'active' => 'users',
        'admin_only' => true,
    ],
    [
        'href' => '/admin/ustawienia',
        'label' => 'Ustawienia',
        'active' => 'settings',
        'admin_only' => true,
    ],
    [
        'href' => '/admin/system',
        'label' => 'System',
        'active' => 'system',
        'admin_only' => true,
    ],
    [
        'href' => '/admin/system/importy',
        'label' => 'Importy',
        'active' => 'imports',
        'admin_only' => true,
    ],
];
?>

<aside class="admin-sidebar">
    <nav
        class="admin-sidebar__nav"
        aria-label="Menu administratora"
    >
        <?php foreach ($items as $item): ?>
            <?php
            $adminOnly = (bool) (
                $item['admin_only']
                ?? false
            );

            if (
                $adminOnly
                && !$isAdministrator
            ) {
                continue;
            }

            $itemHref = (string) $item['href'];
            $itemLabel = (string) $item['label'];
            $itemActive = (string) $item['active'];
            $isActive = $itemActive === $active;
            ?>

            <a
                class="admin-sidebar__link <?= $isActive ? 'admin-sidebar__link--active' : '' ?>"
                href="<?= htmlspecialchars(
                    $itemHref,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>
            >
                <?= htmlspecialchars(
                    $itemLabel,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
