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
 * @var string|null $successMessage
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
                                Lista domków pobierana jest z bazy MySQL. Z tego miejsca możesz edytować domek,
                                zarządzać zdjęciami oraz aktywować lub ukrywać domek na stronie publicznej.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--primary" href="/admin/domki/nowy">
                                Dodaj domek
                            </a>

                            <a class="button button--secondary" href="/admin/system/database">
                                Sprawdź bazę
                            </a>
                        </div>
                    </div>

                    <?php if (isset($successMessage) && is_string($successMessage) && $successMessage !== ''): ?>
                        <div class="alert alert--success">
                            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

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
                                <strong>.env</strong>, uruchom instalator struktury bazy i później dodasz pierwszy domek.
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
                                        <th>Akcje</th>
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

                                            <td>
                                                <div class="table-actions">
                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/domki/edytuj?id=<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Edytuj
                                                    </a>

                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/domki/zdjecia?id=<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Zdjęcia
                                                    </a>

                                                    <form method="post" action="/admin/domki/status">
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <?php if ($cabin['is_active'] === 1): ?>
                                                            <input type="hidden" name="is_active" value="0">

                                                            <button class="button button--secondary button--small" type="submit">
                                                                Ukryj
                                                            </button>
                                                        <?php else: ?>
                                                            <input type="hidden" name="is_active" value="1">

                                                            <button class="button button--primary button--small" type="submit">
                                                                Aktywuj
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                </div>
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