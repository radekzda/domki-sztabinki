<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var string|null $databaseMessage
 * @var int|null $userId
 * @var bool $isCurrentUser
 */

$isEdit = $userId !== null;
$action = $isEdit
    ? '/admin/uzytkownicy/edytuj'
    : '/admin/uzytkownicy/nowy';
?>
<style>
    .user-form-panel {
        max-width: 860px;
        padding: 28px;
    }

    .user-form-panel > .page-header {
        margin-bottom: 22px;
    }

    .user-form-panel > .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .user-form-panel > .page-header p {
        margin: 0;
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
    }

    .user-form {
        max-width: none !important;
    }

    .user-form .form-grid {
        display: grid;
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
        gap: 16px;
    }

    .user-form .form-field--full {
        grid-column: 1 / -1;
    }

    .user-form-help {
        display: block;
        margin-top: 6px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
    }

    @media (max-width: 700px) {
        .user-form-panel {
            padding: 18px;
        }

        .user-form .form-grid {
            grid-template-columns: 1fr;
        }

        .user-form .form-field--full {
            grid-column: 1;
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
                <div class="panel user-form-panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">
                                Użytkownicy
                            </p>

                            <h1>
                                <?= $isEdit
                                    ? 'Edytuj użytkownika'
                                    : 'Dodaj użytkownika' ?>
                            </h1>

                            <p>
                                Hasła są przechowywane wyłącznie jako
                                bezpieczny hash. Minimalna długość hasła
                                wynosi 12 znaków.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--secondary"
                                href="/admin/uzytkownicy"
                            >
                                Wróć do listy
                            </a>
                        </div>
                    </div>

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

                    <?php if ($errors !== []): ?>
                        <div class="alert alert--danger">
                            Popraw błędy w formularzu.
                        </div>
                    <?php endif; ?>

                    <?php if ($isCurrentUser): ?>
                        <div class="alert alert--warning">
                            Edytujesz własne konto. Nie możesz odebrać
                            sobie roli Administrator ani zablokować konta.
                        </div>
                    <?php endif; ?>

                    <form
                        class="form form--wide user-form"
                        method="post"
                        action="<?= htmlspecialchars(
                            $action,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        <?= csrfField() ?>

                        <?php if ($isEdit): ?>
                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $userId ?>"
                            >
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-field form-field--full">
                                <label for="name">
                                    Imię i nazwisko lub nazwa
                                </label>

                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    maxlength="120"
                                    autocomplete="name"
                                    value="<?= htmlspecialchars(
                                        $form['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    required
                                >

                                <?php if (
                                    isset($errors['name'])
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            $errors['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field--full">
                                <label for="email">
                                    Adres e-mail
                                </label>

                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    maxlength="190"
                                    autocomplete="username"
                                    value="<?= htmlspecialchars(
                                        $form['email'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    required
                                >

                                <?php if (
                                    isset($errors['email'])
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            $errors['email'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="role">Rola</label>

                                <select
                                    id="role"
                                    name="role"
                                    required
                                >
                                    <option
                                        value="ADMIN"
                                        <?= $form['role'] === 'ADMIN'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Administrator
                                    </option>

                                    <option
                                        value="PRACOWNIK"
                                        <?= $form['role'] === 'PRACOWNIK'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Pracownik
                                    </option>
                                </select>

                                <span class="user-form-help">
                                    Administrator zarządza ustawieniami,
                                    użytkownikami i operacjami wysokiego ryzyka.
                                </span>

                                <?php if (
                                    isset($errors['role'])
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            $errors['role'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="is_active">
                                    Status konta
                                </label>

                                <select
                                    id="is_active"
                                    name="is_active"
                                    required
                                >
                                    <option
                                        value="1"
                                        <?= $form['is_active'] === '1'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Aktywne
                                    </option>

                                    <option
                                        value="0"
                                        <?= $form['is_active'] === '0'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Zablokowane
                                    </option>
                                </select>

                                <?php if (
                                    isset($errors['is_active'])
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            $errors['is_active'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="password">
                                    <?= $isEdit
                                        ? 'Nowe hasło'
                                        : 'Hasło' ?>
                                </label>

                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    minlength="12"
                                    autocomplete="new-password"
                                    <?= $isEdit ? '' : 'required' ?>
                                >

                                <span class="user-form-help">
                                    <?= $isEdit
                                        ? 'Pozostaw puste, aby zachować obecne hasło.'
                                        : 'Co najmniej 12 znaków.' ?>
                                </span>

                                <?php if (
                                    isset($errors['password'])
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            $errors['password'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="password_confirmation">
                                    Powtórz hasło
                                </label>

                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    minlength="12"
                                    autocomplete="new-password"
                                    <?= $isEdit ? '' : 'required' ?>
                                >

                                <?php if (
                                    isset(
                                        $errors[
                                            'password_confirmation'
                                        ]
                                    )
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            $errors[
                                                'password_confirmation'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button
                                class="button button--primary"
                                type="submit"
                            >
                                <?= $isEdit
                                    ? 'Zapisz zmiany'
                                    : 'Dodaj użytkownika' ?>
                            </button>

                            <a
                                class="button button--secondary"
                                href="/admin/uzytkownicy"
                            >
                                Anuluj
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
