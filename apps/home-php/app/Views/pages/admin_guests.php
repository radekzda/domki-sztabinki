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
 *     full_address: string|null,
 *     is_vip: int,
 *     source: string,
 *     created_at: string
 * }> $guests
 * @var string|null $databaseMessage
 * @var string|null $successMessage
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
                                Lista gości pobierana z bazy MySQL. Goście mogą być ręcznie dodawani
                                albo tworzeni automatycznie podczas zapisu rezerwacji.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/goscie/import">
                                Import
                            </a>

                            <a class="button button--primary" href="/admin/goscie/nowy">
                                Dodaj gościa
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
                                        <th>Adres</th>
                                        <th>Kraj</th>
                                        <th>VIP</th>
                                        <th>Źródło</th>
                                        <th>Utworzono</th>
                                        <th>Akcje</th>
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
                                                <?php
                                                $guestAddress = trim((string) ($guest['full_address'] ?? ''));

                                                if ($guestAddress === '') {
                                                    $guestAddress = trim((string) ($guest['city'] ?? ''));
                                                }
                                                ?>

                                                <?= htmlspecialchars($guestAddress !== '' ? $guestAddress : '—', ENT_QUOTES, 'UTF-8') ?>
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

                                            <td>
                                                <div class="table-actions">
                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/goscie/pokaz?id=<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Szczegóły
                                                    </a>

                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/goscie/edytuj?id=<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Edytuj
                                                    </a>

                                                    <form method="post" action="/admin/goscie/vip">
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="is_vip"
                                                            value="<?= $guest['is_vip'] === 1 ? '0' : '1' ?>"
                                                        >

                                                        <button class="button button--primary button--small" type="submit">
                                                            <?= $guest['is_vip'] === 1 ? 'Usuń VIP' : 'VIP' ?>
                                                        </button>
                                                    </form>

                                                    <form
                                                        method="post"
                                                        action="/admin/goscie/usun"
                                                        onsubmit="return confirm('Czy na pewno usunąć tego gościa? Powiązane rezerwacje zostaną odłączone od karty gościa, ale nie zostaną usunięte.')"
                                                    >
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <button class="button button--secondary button--small" type="submit">
                                                            Usuń
                                                        </button>
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