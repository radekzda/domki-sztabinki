<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $pageHeading
 * @var string $formAction
 * @var string $submitLabel
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var string|null $databaseMessage
 * @var bool $canSave
 * @var bool $isEdit
 */

$value = static function (
    string $key
) use ($form): string {
    return htmlspecialchars(
        $form[$key] ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
};

$error = static function (
    string $key
) use ($errors): ?string {
    return isset($errors[$key])
        && is_string($errors[$key])
            ? $errors[$key]
            : null;
};
?>

<style>
    .invoice-seller-form {
        max-width: none;
    }

    .invoice-seller-form .form-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .invoice-seller-form .form-field {
        min-width: 0;
    }

    .invoice-seller-form .form-field--full {
        grid-column: 1 / -1;
    }

    .invoice-seller-section {
        grid-column: 1 / -1;
        padding: 16px 18px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
    }

    .invoice-seller-section h2 {
        margin: 0 0 5px;
        font-size: 17px;
    }

    .invoice-seller-section p {
        margin: 0;
        font-size: 12px;
        line-height: 1.5;
        color: #6b7280;
    }

    .invoice-seller-form label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #374151;
    }

    .invoice-seller-form input[type="text"],
    .invoice-seller-form input[type="email"],
    .invoice-seller-form input[type="tel"],
    .invoice-seller-form select {
        width: 100%;
        min-height: 42px;
        padding: 9px 11px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #ffffff;
        color: #111827;
        font-size: 13px;
    }

    .invoice-seller-form input:focus,
    .invoice-seller-form select:focus {
        outline: none;
        border-color: #15803d;
        box-shadow:
            0 0 0 3px rgba(
                21,
                128,
                61,
                0.1
            );
    }

    .invoice-seller-checkbox {
        display: flex;
        align-items: center;
        gap: 9px;
        min-height: 42px;
    }

    .invoice-seller-checkbox label {
        margin: 0;
    }

    .invoice-seller-help {
        display: block;
        margin-top: 5px;
        font-size: 11px;
        line-height: 1.4;
        color: #6b7280;
    }

    .invoice-seller-form .form-error {
        display: block;
        margin-top: 5px;
        font-size: 11px;
        line-height: 1.35;
        color: #dc2626;
    }

    .invoice-seller-form .form-actions {
        margin-top: 22px;
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }

    @media (max-width: 800px) {
        .invoice-seller-form .form-grid {
            grid-template-columns: 1fr;
        }

        .invoice-seller-form .form-field--full,
        .invoice-seller-section {
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
                    'active' =>
                        'invoice_sellers',
                ]
            ); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">
                                Faktury
                            </p>

                            <h1>
                                <?= htmlspecialchars(
                                    $pageHeading,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h1>

                            <p>
                                Dane tego profilu będą
                                automatycznie podstawiane
                                podczas tworzenia faktury.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--secondary"
                                href="/admin/sprzedawcy-faktur"
                            >
                                Wróć do listy
                            </a>
                        </div>
                    </div>

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

                    <form
                        class="invoice-seller-form"
                        method="post"
                        action="<?= htmlspecialchars(
                            $formAction,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        <?= csrfField() ?>

                        <div class="form-grid">
                            <div class="invoice-seller-section">
                                <h2>
                                    Dane sprzedawcy
                                </h2>

                                <p>
                                    Imię i nazwisko albo pełna
                                    nazwa podmiotu wystawiającego
                                    fakturę.
                                </p>
                            </div>

                            <div
                                class="form-field form-field--full"
                            >
                                <label for="name">
                                    Nazwa / imię i nazwisko
                                </label>

                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    maxlength="190"
                                    value="<?= $value('name') ?>"
                                    required
                                >

                                <?php if (
                                    $error('name') !== null
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error('name'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="tax_id_type">
                                    Typ identyfikatora
                                </label>

                                <select
                                    id="tax_id_type"
                                    name="tax_id_type"
                                >
                                    <?php
                                    $taxOptions = [
                                        'NIP' => 'NIP',
                                        'VAT_EU' => 'VAT UE',
                                        'OTHER' =>
                                            'Inny identyfikator',
                                        'NONE' => 'Brak',
                                    ];
                                    ?>

                                    <?php foreach (
                                        $taxOptions
                                        as $optionValue => $optionLabel
                                    ): ?>
                                        <option
                                            value="<?= $optionValue ?>"
                                            <?= (
                                                $form['tax_id_type']
                                                ?? ''
                                            ) === $optionValue
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= htmlspecialchars(
                                                $optionLabel,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <?php if (
                                    $error('tax_id_type') !== null
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'tax_id_type'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="tax_id">
                                    NIP / VAT UE / identyfikator
                                </label>

                                <input
                                    id="tax_id"
                                    name="tax_id"
                                    type="text"
                                    maxlength="40"
                                    value="<?= $value('tax_id') ?>"
                                >

                                <?php if (
                                    $error('tax_id') !== null
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error('tax_id'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="invoice-seller-section">
                                <h2>
                                    Adres sprzedawcy
                                </h2>

                                <p>
                                    Adres zapisywany na fakturze.
                                </p>
                            </div>

                            <div
                                class="form-field form-field--full"
                            >
                                <label for="street">
                                    Ulica i numer
                                </label>

                                <input
                                    id="street"
                                    name="street"
                                    type="text"
                                    maxlength="190"
                                    value="<?= $value('street') ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="postal_code">
                                    Kod pocztowy
                                </label>

                                <input
                                    id="postal_code"
                                    name="postal_code"
                                    type="text"
                                    maxlength="40"
                                    value="<?= $value(
                                        'postal_code'
                                    ) ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="city">
                                    Miejscowość
                                </label>

                                <input
                                    id="city"
                                    name="city"
                                    type="text"
                                    maxlength="120"
                                    value="<?= $value('city') ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="country">
                                    Kraj
                                </label>

                                <input
                                    id="country"
                                    name="country"
                                    type="text"
                                    maxlength="120"
                                    value="<?= $value('country') ?>"
                                    required
                                >

                                <?php if (
                                    $error('country') !== null
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error('country'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="invoice_series">
                                    Seria faktur
                                </label>

                                <input
                                    id="invoice_series"
                                    name="invoice_series"
                                    type="text"
                                    maxlength="40"
                                    value="<?= $value(
                                        'invoice_series'
                                    ) ?>"
                                    required
                                >

                                <span class="invoice-seller-help">
                                    Przykład: FV. Numer:
                                    FV/1/02/2026.
                                </span>

                                <?php if (
                                    $error(
                                        'invoice_series'
                                    ) !== null
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'invoice_series'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="invoice-seller-section">
                                <h2>
                                    Kontakt
                                </h2>

                                <p>
                                    Dane kontaktowe sprzedawcy.
                                </p>
                            </div>

                            <div class="form-field">
                                <label for="email">
                                    E-mail
                                </label>

                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    maxlength="190"
                                    value="<?= $value('email') ?>"
                                >

                                <?php if (
                                    $error('email') !== null
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error('email'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="phone">
                                    Telefon
                                </label>

                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    maxlength="60"
                                    value="<?= $value('phone') ?>"
                                >
                            </div>

                            <div class="invoice-seller-section">
                                <h2>
                                    Rachunek bankowy
                                </h2>

                                <p>
                                    Dane używane przy płatności
                                    przelewem.
                                </p>
                            </div>

                            <div
                                class="form-field form-field--full"
                            >
                                <label for="bank_account_holder">
                                    Odbiorca przelewu
                                </label>

                                <input
                                    id="bank_account_holder"
                                    name="bank_account_holder"
                                    type="text"
                                    maxlength="190"
                                    value="<?= $value(
                                        'bank_account_holder'
                                    ) ?>"
                                >
                            </div>

                            <div
                                class="form-field form-field--full"
                            >
                                <label for="bank_account_number">
                                    Numer rachunku
                                </label>

                                <input
                                    id="bank_account_number"
                                    name="bank_account_number"
                                    type="text"
                                    maxlength="80"
                                    value="<?= $value(
                                        'bank_account_number'
                                    ) ?>"
                                >
                            </div>

                            <div
                                class="form-field form-field--full"
                            >
                                <div class="invoice-seller-checkbox">
                                    <input
                                        id="is_active"
                                        name="is_active"
                                        type="checkbox"
                                        value="1"
                                        <?= (
                                            $form['is_active']
                                            ?? '0'
                                        ) === '1'
                                            ? 'checked'
                                            : '' ?>
                                    >

                                    <label for="is_active">
                                        Sprzedawca aktywny
                                    </label>
                                </div>

                                <span class="invoice-seller-help">
                                    Nieaktywny sprzedawca
                                    pozostanie w historii,
                                    ale nie będzie używany
                                    przy nowych fakturach.
                                </span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button
                                class="button button--primary"
                                type="submit"
                                <?= $canSave
                                    ? ''
                                    : 'disabled' ?>
                            >
                                <?= htmlspecialchars(
                                    $submitLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </button>

                            <a
                                class="button button--secondary"
                                href="/admin/sprzedawcy-faktur"
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