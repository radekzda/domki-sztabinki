<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array<string, mixed>> $templates
 * @var string|null $successMessage
 * @var string|null $errorMessage
 * @var string|null $databaseMessage
 * @var bool $canSave
 */

$contextLabels = [
    'INQUIRY' => 'Zapytania',
    'RESERVATION' => 'Rezerwacje',
    'GENERAL' => 'Ogólne',
];
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial(
                'partials/admin_sidebar',
                ['active' => 'templates']
            ); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">
                                Wiadomości
                            </p>

                            <h1>
                                Szablony wiadomości
                            </h1>

                            <p>
                                Dodawaj, edytuj i usuwaj szablony wiadomości
                                wykorzystywane przy obsłudze zapytań
                                i rezerwacji.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--secondary"
                                href="/admin/ustawienia"
                            >
                                Ustawienia
                            </a>
                        </div>
                    </div>

                    <?php if (
                        is_string($successMessage)
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
                        is_string($errorMessage)
                        && $errorMessage !== ''
                    ): ?>
                        <div class="alert alert--danger">
                            <?= htmlspecialchars(
                                $errorMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        is_string($databaseMessage)
                        && $databaseMessage !== ''
                    ): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars(
                                $databaseMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <div class="empty-state">
                        <strong>
                            Zmienne w szablonach
                        </strong>

                        <p>
                            W treści możesz pozostawiać zmienne zapisane
                            w podwójnych nawiasach klamrowych.
                            System w kolejnym etapie będzie automatycznie
                            podstawiał dane konkretnego gościa,
                            zapytania lub rezerwacji.
                        </p>

                        <p>
                            <code>{{guest_name}}</code>,
                            <code>{{first_name}}</code>,
                            <code>{{cabin_name}}</code>,
                            <code>{{start_date}}</code>,
                            <code>{{end_date}}</code>,
                            <code>{{nights}}</code>,
                            <code>{{guests}}</code>,
                            <code>{{total_price}}</code>,
                            <code>{{deposit_amount}}</code>,
                            <code>{{bank_account_holder}}</code>,
                            <code>{{bank_account_number}}</code>,
                            <code>{{payment_title}}</code>,
                            <code>{{check_in_time}}</code>,
                            <code>{{check_out_time}}</code>,
                            <code>{{contact_phone}}</code>,
                            <code>{{location}}</code>,
                            <code>{{property_name}}</code>
                        </p>
                    </div>

                    <div class="empty-state">
                        <strong>
                            Dodaj nowy szablon
                        </strong>

                        <?php if ($canSave): ?>
                            <form
                                class="form form--wide"
                                method="post"
                                action="/admin/szablony/dodaj"
                            >
                                <?= csrfField() ?>

                                <div class="form-grid">
                                    <div class="form-field">
                                        <label for="new-template-name">
                                            Nazwa szablonu
                                        </label>

                                        <input
                                            id="new-template-name"
                                            name="name"
                                            type="text"
                                            maxlength="150"
                                            required
                                        >
                                    </div>

                                    <div class="form-field">
                                        <label for="new-template-context">
                                            Zastosowanie
                                        </label>

                                        <select
                                            id="new-template-context"
                                            name="template_context"
                                            required
                                        >
                                            <option value="RESERVATION">
                                                Rezerwacje
                                            </option>

                                            <option value="INQUIRY">
                                                Zapytania
                                            </option>

                                            <option value="GENERAL">
                                                Ogólne
                                            </option>
                                        </select>
                                    </div>

                                    <div class="form-field">
                                        <label for="new-template-sort-order">
                                            Kolejność
                                        </label>

                                        <input
                                            id="new-template-sort-order"
                                            name="sort_order"
                                            type="number"
                                            min="0"
                                            max="9999"
                                            value="100"
                                        >
                                    </div>

                                    <div class="form-field">
                                        <label>
                                            Status
                                        </label>

                                        <label>
                                            <input
                                                name="is_active"
                                                type="checkbox"
                                                value="1"
                                                checked
                                            >
                                            Szablon aktywny
                                        </label>
                                    </div>

                                    <div class="form-field form-field--full">
                                        <label for="new-template-content">
                                            Treść szablonu
                                        </label>

                                        <textarea
                                            id="new-template-content"
                                            name="content"
                                            rows="12"
                                            required
                                        ></textarea>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button
                                        class="button button--primary"
                                        type="submit"
                                    >
                                        Dodaj szablon
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <p>
                                Dodawanie szablonów jest niedostępne
                                do czasu skonfigurowania bazy danych.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div class="page-header">
                            <div>
                                <h2>
                                    Zapisane szablony
                                </h2>

                                <p>
                                    Liczba szablonów:
                                    <strong>
                                        <?= count($templates) ?>
                                    </strong>
                                </p>
                            </div>
                        </div>

                        <?php if ($templates === []): ?>
                            <div class="empty-state">
                                <strong>
                                    Brak szablonów
                                </strong>

                                <p>
                                    Dodaj pierwszy szablon wiadomości.
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($templates as $template): ?>
                            <?php
                            $templateId = (int) (
                                $template['id']
                                ?? 0
                            );

                            $templateContext = (string) (
                                $template['template_context']
                                ?? 'GENERAL'
                            );

                            $templateKey = isset(
                                $template['template_key']
                            )
                                ? trim(
                                    (string) $template['template_key']
                                )
                                : '';

                            $isActive = !empty(
                                $template['is_active']
                            );
                            ?>

                            <div class="empty-state">
                                <div class="page-header">
                                    <div>
                                        <strong>
                                            <?= htmlspecialchars(
                                                (string) (
                                                    $template['name']
                                                    ?? ''
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>

                                        <p>
                                            <?= htmlspecialchars(
                                                $contextLabels[
                                                    $templateContext
                                                ]
                                                ?? $templateContext,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                            ·

                                            <?= $isActive
                                                ? 'Aktywny'
                                                : 'Nieaktywny' ?>
                                        </p>

                                        <?php if ($templateKey !== ''): ?>
                                            <p>
                                                Klucz systemowy:
                                                <code>
                                                    <?= htmlspecialchars(
                                                        $templateKey,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </code>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <form
                                    class="form form--wide"
                                    method="post"
                                    action="/admin/szablony/edytuj"
                                >
                                    <?= csrfField() ?>

                                    <input
                                        name="id"
                                        type="hidden"
                                        value="<?= $templateId ?>"
                                    >

                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label
                                                for="template-name-<?= $templateId ?>"
                                            >
                                                Nazwa szablonu
                                            </label>

                                            <input
                                                id="template-name-<?= $templateId ?>"
                                                name="name"
                                                type="text"
                                                maxlength="150"
                                                value="<?= htmlspecialchars(
                                                    (string) (
                                                        $template['name']
                                                        ?? ''
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                required
                                            >
                                        </div>

                                        <div class="form-field">
                                            <label
                                                for="template-context-<?= $templateId ?>"
                                            >
                                                Zastosowanie
                                            </label>

                                            <select
                                                id="template-context-<?= $templateId ?>"
                                                name="template_context"
                                                required
                                            >
                                                <?php foreach (
                                                    $contextLabels
                                                    as $contextValue
                                                    => $contextLabel
                                                ): ?>
                                                    <option
                                                        value="<?= htmlspecialchars(
                                                            $contextValue,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>"
                                                        <?= $templateContext
                                                            === $contextValue
                                                            ? 'selected'
                                                            : '' ?>
                                                    >
                                                        <?= htmlspecialchars(
                                                            $contextLabel,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-field">
                                            <label
                                                for="template-sort-order-<?= $templateId ?>"
                                            >
                                                Kolejność
                                            </label>

                                            <input
                                                id="template-sort-order-<?= $templateId ?>"
                                                name="sort_order"
                                                type="number"
                                                min="0"
                                                max="9999"
                                                value="<?= (int) (
                                                    $template['sort_order']
                                                    ?? 0
                                                ) ?>"
                                            >
                                        </div>

                                        <div class="form-field">
                                            <label>
                                                Status
                                            </label>

                                            <label>
                                                <input
                                                    name="is_active"
                                                    type="checkbox"
                                                    value="1"
                                                    <?= $isActive
                                                        ? 'checked'
                                                        : '' ?>
                                                >
                                                Szablon aktywny
                                            </label>
                                        </div>

                                        <div class="form-field form-field--full">
                                            <label
                                                for="template-content-<?= $templateId ?>"
                                            >
                                                Treść szablonu
                                            </label>

                                            <textarea
                                                id="template-content-<?= $templateId ?>"
                                                name="content"
                                                rows="14"
                                                required
                                            ><?= htmlspecialchars(
                                                (string) (
                                                    $template['content']
                                                    ?? ''
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?></textarea>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <button
                                            class="button button--primary"
                                            type="submit"
                                        >
                                            Zapisz zmiany
                                        </button>
                                    </div>
                                </form>

                                <form
                                    method="post"
                                    action="/admin/szablony/usun"
                                    onsubmit="return confirm('Czy na pewno usunąć ten szablon?');"
                                >
                                    <?= csrfField() ?>

                                    <input
                                        name="id"
                                        type="hidden"
                                        value="<?= $templateId ?>"
                                    >

                                    <button
                                        class="button button--danger"
                                        type="submit"
                                    >
                                        Usuń szablon
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
