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
        'href' => '/admin/kalendarz',
        'label' => 'Kalendarz',
        'active' => 'calendar',
    ],
    [
        'href' => '/admin/media',
        'label' => 'Media',
        'active' => 'media',
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
];
?>

<aside
    class="admin-sidebar"
    style="
        width: 220px;
        min-width: 220px;
        align-self: flex-start;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0 28px 28px 0;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        padding: 22px 14px;
    "
>
    <div>
        <p
            style="
                margin: 0 0 22px;
                color: #6b7280;
                font-size: 13px;
                font-weight: 900;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            "
        >
            Panel PMS
        </p>

        <nav
            aria-label="Menu administratora"
            style="
                display: grid;
                gap: 8px;
            "
        >
            <?php foreach ($items as $item): ?>
                <?php
                $itemHref = (string) $item['href'];
                $itemLabel = (string) $item['label'];
                $itemActive = (string) $item['active'];
                $isActive = $itemActive === $active;
                ?>

                <a
                    href="<?= htmlspecialchars($itemHref, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $isActive ? 'aria-current="page"' : '' ?>
                    style="
                        display: flex;
                        align-items: center;
                        min-height: 40px;
                        padding: 0 12px;
                        border-radius: 14px;
                        color: <?= $isActive ? '#ffffff' : '#374151' ?>;
                        background: <?= $isActive ? '#0f7a3d' : 'transparent' ?>;
                        text-decoration: none;
                        font-size: 14px;
                        font-weight: 800;
                    "
                >
                    <?= htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</aside>