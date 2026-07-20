<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $reservation
 * @var array<string, mixed> $cabin
 * @var array<string, mixed> $seller
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var string|null $databaseMessage
 * @var bool $canSave
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
        ? (string) $errors[$key]
        : null;
};
?>

<style>
    .invoice-form .form-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .invoice-form .form-field {
        min-width: 0;
    }

    .invoice-form .form-field--full {
        grid-column: 1 / -1;
    }

    .invoice-section {
        grid-column: 1 / -1;
        padding: 16px 18px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
    }

    .invoice-section h2 {
        margin: 0 0 5px;
        font-size: 17px;
    }

    .invoice-section p {
        margin: 0;
        color: #6b7280;
        font-size: 12px;
    }

    .invoice-form label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 700;
    }

    .invoice-form input,
    .invoice-form select,
    .invoice-form textarea {
        width: 100%;
        padding: 9px 11px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #fff;
    }

    .invoice-form input,
    .invoice-form select {
        min-height: 42px;
    }

    .invoice-form textarea {
        min-height: 90px;
        resize: vertical;
    }

    .invoice-seller-card {
        line-height: 1.55;
        color: #374151;
    }

    .invoice-form .form-error {
        display: block;
        margin-top: 5px;
        color: #dc2626;
        font-size: 11px;
    }

    .invoice-form .form-actions {
        margin-top: 22px;
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
    }

    @media (max-width: 800px) {
        .invoice-form .form-grid {
            grid-template-columns: 1fr;
        }

        .invoice-form .form-field--full,
        .invoice-section {
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
                        'reservations',
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
                                Wystaw fakturę
                            </h1>

                            <p>
                                Rezerwacja #<?= (int) (
                                    $reservation['id']
                                    ?? 0
                                ) ?>
                                —
                                <?= htmlspecialchars(
                                    (string) (
                                        $reservation[
                                            'guest_name'
                                        ]
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--secondary"
                                href="/admin/rezerwacje/pokaz?id=<?= (int) $reservation['id'] ?>"
                            >
                                Wróć do rezerwacji
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
                        class="invoice-form"
                        method="post"
                        action="/admin/faktury/nowa"
                    >
                        <?= csrfField() ?>

                        <input
                            type="hidden"
                            name="reservation_id"
                            value="<?= (int) $reservation['id'] ?>"
                        >

                        <div class="form-grid">
                            <div class="invoice-section">
                                <h2>
                                    Sprzedawca
                                </h2>

                                <p>
                                    Pobierany automatycznie
                                    z domku przypisanego
                                    do rezerwacji.
                                </p>
                            </div>

                            <div
                                class="form-field form-field--full invoice-seller-card"
                            >
                                <strong>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $seller['name']
                                            ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <br>

                                <?php if (
                                    !empty(
                                        $seller['tax_id']
                                    )
                                ): ?>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $seller[
                                                'tax_id_type'
                                            ]
                                            ?? 'NIP'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>:
                                    <?= htmlspecialchars(
                                        (string) $seller[
                                            'tax_id'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                <?php endif; ?>

                                <br>

                                <?= htmlspecialchars(
                                    trim(
                                        (string) (
                                            $seller['street']
                                            ?? ''
                                        )
                                        . ', '
                                        . (string) (
                                            $seller[
                                                'postal_code'
                                            ]
                                            ?? ''
                                        )
                                        . ' '
                                        . (string) (
                                            $seller['city']
                                            ?? ''
                                        ),
                                        ' ,'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>

                            <div class="form-field">
                                <label for="series">
                                    Seria faktury
                                </label>

                                <input
                                    id="series"
                                    name="series"
                                    type="text"
                                    value="<?= $value('series') ?>"
                                    required
                                >

                                <?php if (
                                    $error('series')
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'series'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="previous_sequence_number">
                                    Numer poprzedniej faktury
                                    w tym miesiącu
                                </label>

                                <input
                                    id="previous_sequence_number"
                                    name="previous_sequence_number"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= $value(
                                        'previous_sequence_number'
                                    ) ?>"
                                    required
                                >

                                <small>
                                    Wpisz część liczbową numeru
                                    poprzedniej faktury.
                                    Możesz wpisać także wcześniejszy
                                    numer, aby ponownie wykorzystać
                                    numer faktury usuniętej z systemu.
                                    Numer, który nadal istnieje,
                                    nie może zostać użyty ponownie.
                                </small>

                                <div
                                    id="invoice_number_preview"
                                    style="margin-top: 6px; font-size: 12px; color: #4b5563;"
                                ></div>

                                <?php if (
                                    $error(
                                        'previous_sequence_number'
                                    )
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'previous_sequence_number'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="form-field">
                                <label>
                                    Domek
                                </label>

                                <input
                                    type="text"
                                    value="<?= htmlspecialchars(
                                        (string) (
                                            $cabin['name']
                                            ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    readonly
                                >
                            </div>

                            <div class="invoice-section">
                                <h2>
                                    Daty
                                </h2>
                            </div>

                            <?php foreach (
                                [
                                    'issue_date' =>
                                        'Data i miejsce wystawienia',
                                    'sale_date' =>
                                        'Data wykonania usługi',
                                    'due_date' =>
                                        'Termin płatności (data)',
                                ]
                                as $key => $label
                            ): ?>
                                <div class="form-field">
                                    <label
                                        for="<?= $key ?>"
                                    >
                                        <?= $label ?>
                                    </label>

                                    <input
                                        id="<?= $key ?>"
                                        name="<?= $key ?>"
                                        type="date"
                                        value="<?= $value(
                                            $key
                                        ) ?>"
                                        <?= $key !== 'due_date'
                                            ? 'required'
                                            : '' ?>
                                    >

                                    <?php if (
                                        $key === 'issue_date'
                                        && trim(
                                            (string) (
                                                $seller['city']
                                                ?? ''
                                            )
                                        ) !== ''
                                    ): ?>
                                        <small>
                                            Miejsce wystawienia:
                                            <?= htmlspecialchars(
                                                (string) $seller['city'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </small>
                                    <?php endif; ?>

                                    <?php if (
                                        $error($key)
                                    ): ?>
                                        <span class="form-error">
                                            <?= htmlspecialchars(
                                                (string) $error(
                                                    $key
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <div class="invoice-section">
                                <h2>
                                    Nabywca
                                </h2>

                                <p>
                                    Dane można poprawić przed
                                    wystawieniem faktury.
                                </p>
                            </div>

                            <div class="form-field">
                                <label for="buyer_type">
                                    Typ nabywcy
                                </label>

                                <select
                                    id="buyer_type"
                                    name="buyer_type"
                                >
                                    <option
                                        value="PERSON"
                                        <?= $form[
                                            'buyer_type'
                                        ] === 'PERSON'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Osoba fizyczna
                                    </option>

                                    <option
                                        value="COMPANY"
                                        <?= $form[
                                            'buyer_type'
                                        ] === 'COMPANY'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Firma
                                    </option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="buyer_name">
                                    Nazwa / imię i nazwisko
                                </label>

                                <input
                                    id="buyer_name"
                                    name="buyer_name"
                                    type="text"
                                    value="<?= $value(
                                        'buyer_name'
                                    ) ?>"
                                    required
                                >

                                <?php if (
                                    $error('buyer_name')
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'buyer_name'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="buyer_tax_id_type">
                                    Typ identyfikatora
                                </label>

                                <select
                                    id="buyer_tax_id_type"
                                    name="buyer_tax_id_type"
                                >
                                    <?php foreach (
                                        [
                                            'NONE' => 'Brak',
                                            'NIP' => 'NIP',
                                            'VAT_EU' => 'VAT UE',
                                            'OTHER' => 'Inny',
                                        ]
                                        as $key => $label
                                    ): ?>
                                        <option
                                            value="<?= $key ?>"
                                            <?= $form[
                                                'buyer_tax_id_type'
                                            ] === $key
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="buyer_tax_id">
                                    NIP / VAT UE / identyfikator
                                </label>

                                <input
                                    id="buyer_tax_id"
                                    name="buyer_tax_id"
                                    type="text"
                                    value="<?= $value(
                                        'buyer_tax_id'
                                    ) ?>"
                                >
                            </div>

                            <div
                                class="form-field form-field--full"
                            >
                                <label for="buyer_street">
                                    Ulica i numer
                                </label>

                                <input
                                    id="buyer_street"
                                    name="buyer_street"
                                    type="text"
                                    value="<?= $value(
                                        'buyer_street'
                                    ) ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="buyer_postal_code">
                                    Kod pocztowy
                                </label>

                                <input
                                    id="buyer_postal_code"
                                    name="buyer_postal_code"
                                    type="text"
                                    value="<?= $value(
                                        'buyer_postal_code'
                                    ) ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="buyer_city">
                                    Miejscowość
                                </label>

                                <input
                                    id="buyer_city"
                                    name="buyer_city"
                                    type="text"
                                    value="<?= $value(
                                        'buyer_city'
                                    ) ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="buyer_country">
                                    Kraj
                                </label>

                                <input
                                    id="buyer_country"
                                    name="buyer_country"
                                    type="text"
                                    value="<?= $value(
                                        'buyer_country'
                                    ) ?>"
                                >
                            </div>

                            <div class="form-field">
                                <label for="buyer_email">
                                    E-mail
                                </label>

                                <input
                                    id="buyer_email"
                                    name="buyer_email"
                                    type="email"
                                    value="<?= $value(
                                        'buyer_email'
                                    ) ?>"
                                >
                            </div>

                            <div class="invoice-section">
                                <h2>
                                    Pozycja faktury
                                </h2>
                            </div>

                            <div
                                class="form-field form-field--full"
                            >
                                <label>
                                    Nazwa usługi
                                </label>

                                <input
                                    type="text"
                                    value="wynajem domku wczasowego"
                                    readonly
                                >

                                <input
                                    type="hidden"
                                    name="item_name"
                                    value="wynajem domku wczasowego"
                                >
                            </div>

                            <div class="form-field">
                                <label>
                                    JM
                                </label>

                                <input
                                    type="text"
                                    value="usł."
                                    readonly
                                >
                            </div>

                            <div class="form-field">
                                <label>
                                    Ilość / liczba nocy
                                </label>

                                <input
                                    type="number"
                                    value="<?= htmlspecialchars(
                                        (string) max(
                                            1,
                                            (int) (
                                                $reservation['nights']
                                                ?? 1
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    readonly
                                >
                            </div>

                            <div class="form-field">
                                <label for="gross_amount">
                                    Kwota brutto
                                </label>

                                <input
                                    id="gross_amount"
                                    name="gross_amount"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value="<?= $value(
                                        'gross_amount'
                                    ) ?>"
                                    required
                                >

                                <?php if (
                                    $error('gross_amount')
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'gross_amount'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="vat_rate_code">
                                    VAT
                                </label>

                                <select
                                    id="vat_rate_code"
                                    name="vat_rate_code"
                                    required
                                >
                                    <option value="">
                                        — wybierz —
                                    </option>

                                    <?php foreach (
                                        [
                                            '23',
                                            '8',
                                            '5',
                                            '0',
                                            'ZW',
                                            'NP',
                                        ]
                                        as $vat
                                    ): ?>
                                        <option
                                            value="<?= $vat ?>"
                                            <?= $form[
                                                'vat_rate_code'
                                            ] === $vat
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= htmlspecialchars(
                                                $vat,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?><?= ctype_digit(
                                                $vat
                                            )
                                                ? '%'
                                                : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <?php if (
                                    $error(
                                        'vat_rate_code'
                                    )
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'vat_rate_code'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div
                                class="form-field form-field--full"
                                style="
                                    padding: 16px;
                                    border: 1px solid #d1d5db;
                                    border-radius: 12px;
                                    background: #f8fafc;
                                "
                            >
                                <strong>
                                    Podsumowanie pozycji
                                </strong>

                                <div
                                    style="
                                        display: grid;
                                        grid-template-columns: repeat(
                                            auto-fit,
                                            minmax(140px, 1fr)
                                        );
                                        gap: 12px;
                                        margin-top: 14px;
                                    "
                                >
                                    <div>
                                        <small>
                                            Cena brutto
                                        </small>
                                        <div>
                                            <strong id="invoice_unit_gross">
                                                0,00 zł
                                            </strong>
                                        </div>
                                    </div>

                                    <div>
                                        <small>
                                            Wartość netto
                                        </small>
                                        <div>
                                            <strong id="invoice_net">
                                                0,00 zł
                                            </strong>
                                        </div>
                                    </div>

                                    <div>
                                        <small>
                                            VAT
                                        </small>
                                        <div>
                                            <strong id="invoice_vat_rate">
                                                8%
                                            </strong>
                                        </div>
                                    </div>

                                    <div>
                                        <small>
                                            Kwota VAT
                                        </small>
                                        <div>
                                            <strong id="invoice_vat">
                                                0,00 zł
                                            </strong>
                                        </div>
                                    </div>

                                    <div>
                                        <small>
                                            Wartość brutto
                                        </small>
                                        <div>
                                            <strong id="invoice_gross">
                                                0,00 zł
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="form-field form-field--full"
                            >
                                <label for="tax_exemption_basis">
                                    Podstawa / informacja podatkowa
                                </label>

                                <input
                                    id="tax_exemption_basis"
                                    name="tax_exemption_basis"
                                    type="text"
                                    value="<?= $value(
                                        'tax_exemption_basis'
                                    ) ?>"
                                >

                                <?php if (
                                    $error(
                                        'tax_exemption_basis'
                                    )
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'tax_exemption_basis'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="invoice-section">
                                <h2>
                                    Płatność
                                </h2>
                            </div>

                            <div class="form-field">
                                <label for="payment_method">
                                    Metoda płatności
                                </label>

                                <select
                                    id="payment_method"
                                    name="payment_method"
                                >
                                    <option value="">
                                        — wybierz —
                                    </option>

                                    <option
                                        value="TRANSFER"
                                        <?= $form[
                                            'payment_method'
                                        ] === 'TRANSFER'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Przelew
                                    </option>

                                    <option
                                        value="CASH"
                                        <?= $form[
                                            'payment_method'
                                        ] === 'CASH'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Gotówka
                                    </option>

                                    <option
                                        value="CARD"
                                        <?= $form[
                                            'payment_method'
                                        ] === 'CARD'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Karta
                                    </option>

                                    <option
                                        value="PLATFORM"
                                        <?= $form[
                                            'payment_method'
                                        ] === 'PLATFORM'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Platforma rezerwacyjna
                                    </option>
                                </select>

                                <?php if (
                                    $error('payment_method')
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'payment_method'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div
                                class="form-field"
                                id="payment_days_field"
                                hidden
                            >
                                <label for="payment_days">
                                    Termin płatności (dni)
                                </label>

                                <input
                                    id="payment_days"
                                    name="payment_days"
                                    type="number"
                                    min="0"
                                    max="3650"
                                    step="1"
                                    value="<?= $value(
                                        'payment_days'
                                    ) ?>"
                                >

                                <small>
                                    0 oznacza płatność
                                    w dniu wystawienia.
                                </small>

                                <?php if (
                                    $error('payment_days')
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'payment_days'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="form-field">
                                <label for="paid_amount">
                                    Zapłacono
                                </label>

                                <input
                                    id="paid_amount"
                                    name="paid_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value="<?= $value(
                                        'paid_amount'
                                    ) ?>"
                                    required
                                >

                                <?php if (
                                    $error('paid_amount')
                                ): ?>
                                    <span class="form-error">
                                        <?= htmlspecialchars(
                                            (string) $error(
                                                'paid_amount'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="remaining_amount">
                                    Pozostało do zapłaty
                                </label>

                                <input
                                    id="remaining_amount"
                                    type="text"
                                    value="0,00 zł"
                                    readonly
                                >
                            </div>

                            <div class="form-field">
                                <label for="payment_status">
                                    Status płatności
                                </label>

                                <input
                                    type="hidden"
                                    name="payment_status"
                                    value="<?= $value(
                                        'payment_status'
                                    ) ?>"
                                >

                                <select
                                    id="payment_status"
                                    disabled
                                >
                                    <option
                                        value="UNPAID"
                                        <?= $form[
                                            'payment_status'
                                        ] === 'UNPAID'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Nieopłacona
                                    </option>

                                    <option
                                        value="PARTIALLY_PAID"
                                        <?= $form[
                                            'payment_status'
                                        ] === 'PARTIALLY_PAID'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Częściowo opłacona
                                    </option>

                                    <option
                                        value="PAID"
                                        <?= $form[
                                            'payment_status'
                                        ] === 'PAID'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Opłacona
                                    </option>
                                </select>
                            </div>

                            <div
                                class="form-field form-field--full"
                            >
                                <label for="notes">
                                    Informacje dodatkowe
                                </label>

                                <textarea
                                    id="notes"
                                    name="notes"
                                ><?= $value('notes') ?></textarea>
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
                                Wystaw fakturę
                            </button>

                            <a
                                class="button button--secondary"
                                href="/admin/rezerwacje/pokaz?id=<?= (int) $reservation['id'] ?>"
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


<script>
(function () {
    const grossInput =
        document.getElementById(
            'gross_amount'
        );

    const paidInput =
        document.getElementById(
            'paid_amount'
        );

    const remainingInput =
        document.getElementById(
            'remaining_amount'
        );

    const vatSelect =
        document.getElementById(
            'vat_rate_code'
        );

    const paymentStatusInput =
        document.querySelector(
            'input[name="payment_status"]'
        );

    const paymentStatusSelect =
        document.getElementById(
            'payment_status'
        );

    if (
        !grossInput
        || !paidInput
        || !remainingInput
        || !vatSelect
        || !paymentStatusInput
        || !paymentStatusSelect
    ) {
        return;
    }

    const unitGrossOutput =
        document.getElementById(
            'invoice_unit_gross'
        );

    const netOutput =
        document.getElementById(
            'invoice_net'
        );

    const vatRateOutput =
        document.getElementById(
            'invoice_vat_rate'
        );

    const vatOutput =
        document.getElementById(
            'invoice_vat'
        );

    const grossOutput =
        document.getElementById(
            'invoice_gross'
        );

    function numberValue(input) {
        return Number.parseFloat(
            String(
                input.value
            ).replace(',', '.')
        ) || 0;
    }

    function formatMoney(value) {
        return Math.max(
            0,
            value
        ).toLocaleString(
            'pl-PL',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ) + ' zł';
    }

    function calculateAmounts() {
        const gross =
            Math.max(
                0,
                numberValue(
                    grossInput
                )
            );

        const vatCode =
            vatSelect.value;

        let net = gross;
        let vat = 0;

        if (
            vatCode === '23'
            || vatCode === '8'
            || vatCode === '5'
        ) {
            const rate =
                Number.parseFloat(
                    vatCode
                );

            net =
                gross
                / (
                    1
                    + rate / 100
                );

            vat =
                gross - net;
        }

        unitGrossOutput.textContent =
            formatMoney(
                gross
            );

        netOutput.textContent =
            formatMoney(
                net
            );

        vatRateOutput.textContent =
            /^\d+$/.test(
                vatCode
            )
                ? vatCode + '%'
                : vatCode;

        vatOutput.textContent =
            formatMoney(
                vat
            );

        grossOutput.textContent =
            formatMoney(
                gross
            );
    }

    function calculatePayment() {
        const gross =
            Math.max(
                0,
                numberValue(
                    grossInput
                )
            );

        const paid =
            Math.max(
                0,
                numberValue(
                    paidInput
                )
            );

        const remaining =
            Math.max(
                0,
                gross - paid
            );

        paidInput.max =
            gross.toFixed(2);

        remainingInput.value =
            formatMoney(
                remaining
            );

        let paymentStatus =
            'UNPAID';

        if (
            gross > 0
            && paid >= gross
        ) {
            paymentStatus =
                'PAID';
        } else if (paid > 0) {
            paymentStatus =
                'PARTIALLY_PAID';
        }

        paymentStatusInput.value =
            paymentStatus;

        paymentStatusSelect.value =
            paymentStatus;
    }

    function syncPaidToGross() {
        const gross =
            Math.max(
                0,
                numberValue(
                    grossInput
                )
            );

        paidInput.value =
            gross.toFixed(2);

        calculatePayment();
    }

    grossInput.addEventListener(
        'input',
        function () {
            calculateAmounts();
            syncPaidToGross();
        }
    );

    vatSelect.addEventListener(
        'change',
        calculateAmounts
    );

    paidInput.addEventListener(
        'input',
        calculatePayment
    );

    calculateAmounts();

    if (
        String(
            paidInput.value
        ).trim() === ''
    ) {
        syncPaidToGross();
    } else {
        calculatePayment();
    }
})();
</script>
<script>
(function () {
    const paymentMethod =
        document.getElementById(
            'payment_method'
        );

    const paymentDaysField =
        document.getElementById(
            'payment_days_field'
        );

    const paymentDaysInput =
        document.getElementById(
            'payment_days'
        );

    const issueDateInput =
        document.getElementById(
            'issue_date'
        );

    const dueDateInput =
        document.getElementById(
            'due_date'
        );

    if (
        !paymentMethod
        || !paymentDaysField
        || !paymentDaysInput
        || !issueDateInput
        || !dueDateInput
    ) {
        return;
    }

    function parseDate(value) {
        const parts =
            String(value)
                .split('-')
                .map(Number);

        if (
            parts.length !== 3
            || !parts[0]
            || !parts[1]
            || !parts[2]
        ) {
            return null;
        }

        return new Date(
            parts[0],
            parts[1] - 1,
            parts[2],
            12,
            0,
            0
        );
    }

    function formatDate(date) {
        const year =
            String(
                date.getFullYear()
            );

        const month =
            String(
                date.getMonth() + 1
            ).padStart(2, '0');

        const day =
            String(
                date.getDate()
            ).padStart(2, '0');

        return year
            + '-'
            + month
            + '-'
            + day;
    }

    function syncDueDate() {
        if (
            paymentMethod.value
            !== 'TRANSFER'
        ) {
            return;
        }

        const issueDate =
            parseDate(
                issueDateInput.value
            );

        if (!issueDate) {
            return;
        }

        let days =
            Number.parseInt(
                paymentDaysInput.value,
                10
            );

        if (
            !Number.isInteger(days)
            || days < 0
        ) {
            days = 0;
        }

        paymentDaysInput.value =
            String(days);

        issueDate.setDate(
            issueDate.getDate()
            + days
        );

        dueDateInput.value =
            formatDate(
                issueDate
            );
    }

    function updatePaymentDaysVisibility() {
        const isTransfer =
            paymentMethod.value
            === 'TRANSFER';

        paymentDaysField.hidden =
            !isTransfer;

        dueDateInput.readOnly =
            isTransfer;

        if (isTransfer) {
            if (
                String(
                    paymentDaysInput.value
                ).trim() === ''
            ) {
                paymentDaysInput.value =
                    '0';
            }

            syncDueDate();
        }
    }

    paymentMethod.addEventListener(
        'change',
        updatePaymentDaysVisibility
    );

    paymentDaysInput.addEventListener(
        'input',
        syncDueDate
    );

    issueDateInput.addEventListener(
        'change',
        syncDueDate
    );

    updatePaymentDaysVisibility();
})();
</script>
<script>
(function () {
    const seriesInput =
        document.getElementById(
            'series'
        );

    const issueDateInput =
        document.getElementById(
            'issue_date'
        );

    const previousNumberInput =
        document.getElementById(
            'previous_sequence_number'
        );

    const preview =
        document.getElementById(
            'invoice_number_preview'
        );

    if (
        !seriesInput
        || !issueDateInput
        || !previousNumberInput
        || !preview
    ) {
        return;
    }

    function updatePreview() {
        const series =
            String(
                seriesInput.value
            ).trim().toUpperCase();

        const dateParts =
            String(
                issueDateInput.value
            ).split('-');

        let previousNumber =
            Number.parseInt(
                previousNumberInput.value,
                10
            );

        if (
            !Number.isInteger(
                previousNumber
            )
            || previousNumber < 0
        ) {
            previousNumber = 0;
        }

        if (
            series === ''
            || dateParts.length !== 3
        ) {
            preview.textContent = '';
            return;
        }

        const year = dateParts[0];
        const month = dateParts[1];
        const nextNumber =
            previousNumber + 1;

        preview.textContent =
            'Następna faktura: '
            + series
            + '/'
            + nextNumber
            + '/'
            + month
            + '/'
            + year;
    }

    seriesInput.addEventListener(
        'input',
        updatePreview
    );

    issueDateInput.addEventListener(
        'change',
        updatePreview
    );

    previousNumberInput.addEventListener(
        'input',
        updatePreview
    );

    updatePreview();
})();
</script>
