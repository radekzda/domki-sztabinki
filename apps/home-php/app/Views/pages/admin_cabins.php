<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array{
 *     id: int,
 *     name: string,
 *     short_name: string|null,
 *     max_guests: int,
 *     bedrooms: int,
 *     bathrooms: int,
 *     price_per_night: int,
 *     price_one_night: int,
 *     price_two_nights: int,
 *     price_three_nights: int,
 *     price_four_nights: int,
 *     price_five_nights: int,
 *     price_six_nights: int,
 *     price_seven_plus_nights: int,
 *     is_active: int,
 *     sort_order: int,
 *     created_at: string
 * }> $cabins
 * @var string|null $databaseMessage
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'cabins']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Domki</p>

                            <h1>Domki</h1>

                            <p>
                                Lista domków będzie pobierana z bazy MySQL. To pierwszy ekran panelu
                                podłączony do warstwy danych.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/system/database">
                                Sprawdź bazę
                            </a>
                        </div>
                    </div>

                    <?php if (isset($databaseMessage) && is_string($databaseMessage) && $databaseMessage !== ''): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars($databaseMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($cabins === []): ?>
                        <div class="empty-state">
                            <strong>Brak domków do wyświetlenia</strong>

                            <p>
                                Jeżeli baza danych nie jest jeszcze skonfigurowana, ustaw dane MySQL w pliku
                                <strong>.env</strong>, uruchom instalator struktury bazy i później dodamy formularz
                                tworzenia domków.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nazwa</th>
                                        <th>Skrót</th>
                                        <th>Osoby</th>
                                        <th>Pokoje</th>
                                        <th>Cena domyślna</th>
                                        <th>7+ nocy</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($cabins as $cabin): ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($cabin['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($cabin['short_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars((string) $cabin['max_guests'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars((string) $cabin['bedrooms'], ENT_QUOTES, 'UTF-8') ?>
                                                syp. /
                                                <?= htmlspecialchars((string) $cabin['bathrooms'], ENT_QUOTES, 'UTF-8') ?>
                                                łaz.
                                            </td>

                                            <td>
                                                <?= htmlspecialchars((string) $cabin['price_per_night'], ENT_QUOTES, 'UTF-8') ?>
                                                zł
                                            </td>

                                            <td>
                                                <?= htmlspecialchars((string) $cabin['price_seven_plus_nights'], ENT_QUOTES, 'UTF-8') ?>
                                                zł
                                            </td>

                                            <td>
                                                <?php if ($cabin['is_active'] === 1): ?>
                                                    <span class="status-pill status-pill--success">Aktywny</span>
                                                <?php else: ?>
                                                    <span class="status-pill status-pill--muted">Ukryty</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>