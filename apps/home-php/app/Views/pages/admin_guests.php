<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array{
 *     id: int,
 *     first_name: string,
 *     last_name: string,
 *     email: string,
 *     phone: string|null,
 *     country: string|null,
 *     city: string|null,
 *     is_vip: int,
 *     source: string,
 *     created_at: string
 * }> $guests
 * @var string|null $databaseMessage
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'guests']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Goście</p>

                            <h1>Goście</h1>

                            <p>
                                Lista gości pobierana z bazy MySQL. W kolejnym kroku połączymy gości
                                z rezerwacjami oraz dodamy tworzenie i edycję kart gości.
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

                    <?php if ($guests === []): ?>
                        <div class="empty-state">
                            <strong>Brak gości do wyświetlenia</strong>

                            <p>
                                Po skonfigurowaniu MySQL i dodaniu pierwszych gości pojawią się tutaj dane kontaktowe,
                                źródło pozyskania oraz oznaczenie VIP.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Gość</th>
                                        <th>Kontakt</th>
                                        <th>Miejscowość</th>
                                        <th>Kraj</th>
                                        <th>VIP</th>
                                        <th>Źródło</th>
                                        <th>Utworzono</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($guests as $guest): ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($guest['first_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?= htmlspecialchars($guest['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <span>
                                                    <?= htmlspecialchars($guest['email'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>

                                                <?php if ($guest['phone'] !== null && $guest['phone'] !== ''): ?>
                                                    <br>

                                                    <span>
                                                        <?= htmlspecialchars($guest['phone'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($guest['city'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($guest['country'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?php if ($guest['is_vip'] === 1): ?>
                                                    <span class="status-pill status-pill--success">VIP</span>
                                                <?php else: ?>
                                                    <span class="status-pill status-pill--muted">Nie</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($guest['source'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(formatDateForDisplay($guest['created_at']), ENT_QUOTES, 'UTF-8') ?>
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