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
<style>
    .templates-panel {
        padding: 28px;
    }

    /*
     * Naglowek strony
     */
    .templates-panel > .page-header {
        margin-bottom: 22px;
        align-items: flex-start;
    }

    .templates-panel > .page-header .eyebrow {
        display: none;
    }

    .templates-panel > .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .templates-panel > .page-header p {
        max-width: 760px;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .templates-panel > .page-header .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
    }

    /*
     * Wspolne karty
     */
    .template-vars-card,
    .template-create-card,
    .template-card {
        margin-top: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.03);
    }

    /*
     * Zmienne szablonow
     */
    .template-vars-card {
        background: #f8fafc;
    }

    .template-vars-card > strong {
        display: block;
        margin-bottom: 7px;
        font-size: 16px;
        line-height: 1.25;
        color: #111827;
    }

    .template-vars-card > p {
        margin: 0;
        max-width: none;
        font-size: 13px;
        line-height: 1.5;
        color: #6b7280;
    }

    .template-variable-list {
        margin-top: 14px !important;
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .template-variable-list code {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 4px 8px;
        border: 1px solid #dbe3ea;
        border-radius: 7px;
        background: #ffffff;
        color: #334155;
        font-family:
            Consolas,
            "Courier New",
            monospace;
        font-size: 11px;
        line-height: 1;
    }

    /*
     * Dodawanie nowego szablonu
     */
    .template-create-card > strong {
        display: block;
        margin-bottom: 16px;
        font-size: 17px;
        line-height: 1.25;
        color: #111827;
    }

    .template-create-form,
    .template-edit-form {
        max-width: none !important;
    }

    .template-create-form .form-grid,
    .template-edit-form .form-grid {
        display: grid;
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
        gap: 14px 16px;
    }

    .template-create-form .form-field,
    .template-edit-form .form-field {
        margin: 0;
    }

    .template-create-form .form-field > label,
    .template-edit-form .form-field > label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        line-height: 1.25;
        font-weight: 700;
        color: #374151;
    }

    .template-create-form input[type="text"],
    .template-create-form input[type="number"],
    .template-create-form select,
    .template-edit-form input[type="text"],
    .template-edit-form input[type="number"],
    .template-edit-form select {
        width: 100%;
        min-height: 42px;
        padding: 9px 11px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #ffffff;
        font-size: 13px;
        color: #111827;
    }

    .template-create-form textarea,
    .template-edit-form textarea {
        width: 100%;
        min-height: 190px;
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #ffffff;
        font-family:
            Inter,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            Arial,
            sans-serif;
        font-size: 13px;
        line-height: 1.5;
        color: #111827;
        resize: vertical;
    }

    /*
     * Status aktywny
     */
    .template-status-field {
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    .template-status-field > label:last-child {
        min-height: 42px;
        margin: 0 !important;
        padding: 10px 12px;
        display: flex !important;
        align-items: center;
        gap: 9px;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        background: #f8fafc;
        font-size: 13px !important;
        font-weight: 600 !important;
        cursor: pointer;
    }

    .template-status-field input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin: 0;
        accent-color: #15803d;
    }

    .template-create-form .form-actions,
    .template-edit-form .form-actions {
        margin-top: 16px;
    }

    .template-create-form .form-actions .button,
    .template-edit-form .form-actions .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 9px;
        font-size: 12px;
    }

    /*
     * Naglowek zapisanych szablonow
     */
    .templates-saved-section {
        margin-top: 26px;
    }

    .templates-saved-header {
        margin-bottom: 12px !important;
    }

    .templates-saved-header h2 {
        margin: 0 0 4px;
        font-size: 22px;
        line-height: 1.2;
        color: #111827;
    }

    .templates-saved-header p {
        margin: 0;
        font-size: 12px;
        color: #6b7280;
    }

    /*
     * Karta zapisanego szablonu
     */
    .template-card {
        margin-top: 12px;
    }

    .template-card__header {
        margin-bottom: 16px !important;
        padding-bottom: 14px;
        border-bottom: 1px solid #edf0f2;
    }

    .template-card__header strong {
        display: block;
        font-size: 16px;
        line-height: 1.25;
        color: #111827;
    }

    .template-card__header p {
        margin: 4px 0 0;
        font-size: 12px;
        line-height: 1.35;
        color: #6b7280;
    }

    .template-card__header code {
        padding: 3px 6px;
        border-radius: 5px;
        background: #f1f5f9;
        color: #475569;
        font-size: 11px;
    }

    /*
     * Usuwanie
     */
    .template-delete-form {
        margin-top: 8px;
    }

    .template-delete-form .button--danger {
        min-height: 34px;
        padding: 7px 13px;
        border-radius: 8px;
        background: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
        font-size: 12px;
    }

    .template-delete-form .button--danger:hover {
        background: #dc2626;
        border-color: #dc2626;
    }

    /*
     * Responsive
     */
    @media (max-width: 900px) {
        .templates-panel {
            padding: 22px;
        }

        .template-create-form .form-grid,
        .template-edit-form .form-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 600px) {
        .templates-panel {
            padding: 16px;
        }

        .templates-panel > .page-header {
            flex-direction: column;
            gap: 14px;
        }

        .templates-panel > .page-header h1 {
            font-size: 27px;
        }

        .template-vars-card,
        .template-create-card,
        .template-card {
            padding: 16px;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial(
                'partials/admin_sidebar',
                ['active' => 'templates']
            ); ?>

            <div class="admin-content">
                <div class="panel templates-panel">
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

                    <div class="empty-state template-vars-card">
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

                        <p class="template-variable-list">
                            <code>{{guest_name}}</code>
                            <code>{{first_name}}</code>
                            <code>{{cabin_name}}</code>
                            <code>{{start_date}}</code>
                            <code>{{end_date}}</code>
                            <code>{{nights}}</code>
                            <code>{{guests}}</code>
                            <code>{{total_price}}</code>
                            <code>{{deposit_amount}}</code>
                            <code>{{bank_account_holder}}</code>
                            <code>{{bank_account_number}}</code>
                            <code>{{payment_title}}</code>
                            <code>{{check_in_time}}</code>
                            <code>{{check_out_time}}</code>
                            <code>{{contact_phone}}</code>
                            <code>{{location}}</code>
                            <code>{{property_name}}</code>
                        </p>
                    </div>

                    <div class="empty-state template-create-card">
                        <strong>
                            Dodaj nowy szablon
                        </strong>

                        <?php if ($canSave): ?>
                            <form
                                class="form form--wide template-create-form"
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

                                    <div class="form-field template-status-field">
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

                    <div class="templates-saved-section">
                        <div class="page-header templates-saved-header">
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

                            <div class="empty-state template-card">
                                <div class="page-header template-card__header">
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
                                    class="form form--wide template-edit-form"
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
                                    class="template-delete-form"
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
