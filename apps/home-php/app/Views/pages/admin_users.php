<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array<string, mixed>> $users
 * @var string|null $databaseMessage
 * @var string|null $successMessage
 * @var int|null $currentUserId
 */

$roleLabels = [
    'ADMIN' => 'Administrator',
    'PRACOWNIK' => 'Pracownik',
];
?>
<style>
    .users-panel {
        padding: 28px;
    }

    .users-panel > .page-header {
        margin-bottom: 22px;
    }

    .users-panel > .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .users-panel > .page-header p {
        max-width: 760px;
        margin: 0;
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
    }

    .user-badge {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 4px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 900;
    }

    .user-badge--admin {
        background: #ede9fe;
        color: #6d28d9;
    }

    .user-badge--employee {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .user-badge--active {
        background: #dcfce7;
        color: #166534;
    }

    .user-badge--inactive {
        background: #fee2e2;
        color: #b91c1c;
    }

    .user-current {
        margin-left: 6px;
        color: #15803d;
        font-size: 11px;
        font-weight: 900;
    }

    .user-meta {
        display: grid;
        gap: 3px;
    }

    .user-meta strong {
        color: #111827;
    }

    .user-meta span {
        color: #6b7280;
        font-size: 12px;
    }

    @media (max-width: 700px) {
        .users-panel {
            padding: 18px;
        }

        .users-panel > .page-header {
            flex-direction: column;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial(
                'partials/admin_sidebar',
                [
                    'active' => 'users',
                ]
            ); ?>

            <div class="admin-content">
                <div class="panel users-panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">
                                Bezpieczeństwo
                            </p>

                            <h1>Użytkownicy panelu</h1>

                            <p>
                                Administrator zarządza kontami i ustawieniami.
                                Pracownik ma dostęp do codziennej obsługi
                                rezerwacji, gości, faktur i kalendarza.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--primary"
                                href="/admin/uzytkownicy/nowy"
                            >
                                Dodaj użytkownika
                            </a>
                        </div>
                    </div>

                    <?php if (
                        isset($successMessage)
                        && is_string($successMessage)
                        && $successMessage !== ''
                    ): ?>
                        <div class="alert alert--success">
                            <?= htmlspecialchars(
                                $successMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        isset($databaseMessage)
                        && is_string($databaseMessage)
                        && $databaseMessage !== ''
                    ): ?>
                        <div class="alert alert--danger">
                            <?= htmlspecialchars(
                                $databaseMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($users === []): ?>
                        <div class="empty-state">
                            <strong>Brak użytkowników</strong>

                            <p>
                                Uruchom migrację użytkowników albo dodaj
                                pierwsze konto administratora.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Użytkownik</th>
                                        <th>Rola</th>
                                        <th>Status</th>
                                        <th>Ostatnie logowanie</th>
                                        <th>Utworzono</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <?php
                                        $userId = (int) (
                                            $user['id']
                                            ?? 0
                                        );

                                        $role = (string) (
                                            $user['role']
                                            ?? ''
                                        );

                                        $isActive =
                                            (int) (
                                                $user['is_active']
                                                ?? 0
                                            ) === 1;

                                        $lastLoginAt = trim(
                                            (string) (
                                                $user['last_login_at']
                                                ?? ''
                                            )
                                        );

                                        $createdAt = trim(
                                            (string) (
                                                $user['created_at']
                                                ?? ''
                                            )
                                        );
                                        ?>

                                        <tr>
                                            <td>
                                                <div class="user-meta">
                                                    <strong>
                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $user['name']
                                                                ?? ''
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                        <?php if (
                                                            $currentUserId
                                                            === $userId
                                                        ): ?>
                                                            <span class="user-current">
                                                                Twoje konto
                                                            </span>
                                                        <?php endif; ?>
                                                    </strong>

                                                    <span>
                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $user['email']
                                                                ?? ''
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <span class="user-badge <?= $role === 'ADMIN' ? 'user-badge--admin' : 'user-badge--employee' ?>">
                                                    <?= htmlspecialchars(
                                                        $roleLabels[$role]
                                                        ?? $role,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="user-badge <?= $isActive ? 'user-badge--active' : 'user-badge--inactive' ?>">
                                                    <?= $isActive
                                                        ? 'Aktywny'
                                                        : 'Zablokowany' ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= $lastLoginAt !== ''
                                                    ? htmlspecialchars(
                                                        $lastLoginAt,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    )
                                                    : 'Jeszcze się nie logował' ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $createdAt,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <div class="table-actions">
                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/uzytkownicy/edytuj?id=<?= $userId ?>"
                                                    >
                                                        Edytuj
                                                    </a>

                                                    <?php if (
                                                        $currentUserId
                                                        !== $userId
                                                    ): ?>
                                                        <form
                                                            method="post"
                                                            action="/admin/uzytkownicy/status"
                                                        >
                                                            <?= csrfField() ?>

                                                            <input
                                                                type="hidden"
                                                                name="id"
                                                                value="<?= $userId ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="is_active"
                                                                value="<?= $isActive ? '0' : '1' ?>"
                                                            >

                                                            <button
                                                                class="button button--small <?= $isActive ? 'button--secondary' : 'button--primary' ?>"
                                                                type="submit"
                                                            >
                                                                <?= $isActive
                                                                    ? 'Zablokuj'
                                                                    : 'Aktywuj' ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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
