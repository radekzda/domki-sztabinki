<?php

declare(strict_types=1);

/**
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var bool $canSave
 * @var string $action
 * @var string $submitLabel
 */

$actionValue = isset($action) && is_string($action) ? $action : '';
$submitLabelValue = isset($submitLabel) && is_string($submitLabel) ? $submitLabel : 'Zapisz gościa';
?>
<form class="form form--wide" method="post" action="<?= htmlspecialchars($actionValue, ENT_QUOTES, 'UTF-8') ?>">
    <?= csrfField() ?>

    <input
        id="address_sync_source"
        name="address_sync_source"
        type="hidden"
        value="structured"
    >
    <div class="form-grid">
        <div class="form-field">
            <label for="first_name">Imię</label>
            <input
                id="first_name"
                name="first_name"
                type="text"
                value="<?= htmlspecialchars($form['first_name'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <?php if (isset($errors['first_name'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['first_name'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="last_name">Nazwisko</label>
            <input
                id="last_name"
                name="last_name"
                type="text"
                value="<?= htmlspecialchars($form['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <?php if (isset($errors['last_name'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['last_name'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="email">E-mail</label>
            <input
                id="email"
                name="email"
                type="email"
                value="<?= htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <?php if (isset($errors['email'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="phone">Telefon</label>
            <input
                id="phone"
                name="phone"
                type="text"
                value="<?= htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-field">
            <label for="preferred_contact">
                Preferowany kontakt
            </label>

            <select
                id="preferred_contact"
                name="preferred_contact"
            >
                <option
                    value=""
                    <?= $form['preferred_contact'] === '' ? 'selected' : '' ?>
                >
                    Brak preferencji
                </option>

                <option
                    value="PHONE"
                    <?= $form['preferred_contact'] === 'PHONE' ? 'selected' : '' ?>
                >
                    Telefon
                </option>

                <option
                    value="EMAIL"
                    <?= $form['preferred_contact'] === 'EMAIL' ? 'selected' : '' ?>
                >
                    E-mail
                </option>

                <option
                    value="SMS"
                    <?= $form['preferred_contact'] === 'SMS' ? 'selected' : '' ?>
                >
                    SMS
                </option>

                <option
                    value="WHATSAPP"
                    <?= $form['preferred_contact'] === 'WHATSAPP' ? 'selected' : '' ?>
                >
                    WhatsApp
                </option>
            </select>

            <?php if (
                isset(
                    $errors['preferred_contact']
                )
            ): ?>
                <span class="form-error">
                    <?= htmlspecialchars(
                        $errors['preferred_contact'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="street">Ulica</label>
            <input
                id="street"
                name="street"
                type="text"
                value="<?= htmlspecialchars($form['street'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-field">
            <label for="postal_code">Kod pocztowy</label>
            <input
                id="postal_code"
                name="postal_code"
                type="text"
                value="<?= htmlspecialchars($form['postal_code'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-field">
            <label for="city">Miejscowość</label>
            <input
                id="city"
                name="city"
                type="text"
                value="<?= htmlspecialchars($form['city'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-field">
            <label for="country">Kraj</label>
            <input
                id="country"
                name="country"
                type="text"
                value="<?= htmlspecialchars($form['country'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-field form-field--full">
            <label for="full_address">Pełny adres</label>
            <input
                id="full_address"
                name="full_address"
                type="text"
                value="<?= htmlspecialchars($form['full_address'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-field">
            <label for="birth_date">Data urodzenia</label>
            <input
                id="birth_date"
                name="birth_date"
                type="date"
                value="<?= htmlspecialchars($form['birth_date'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <?php if (isset($errors['birth_date'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['birth_date'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="pesel">PESEL</label>
            <input
                id="pesel"
                name="pesel"
                type="text"
                value="<?= htmlspecialchars($form['pesel'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-field">
            <label for="document_number">Numer dokumentu</label>
            <input
                id="document_number"
                name="document_number"
                type="text"
                value="<?= htmlspecialchars($form['document_number'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>


        <div class="form-field">
            <label for="is_vip">VIP</label>
            <select id="is_vip" name="is_vip">
                <option value="0" <?= $form['is_vip'] === '0' ? 'selected' : '' ?>>
                    Nie
                </option>
                <option value="1" <?= $form['is_vip'] === '1' ? 'selected' : '' ?>>
                    Tak
                </option>
            </select>

            <?php if (isset($errors['is_vip'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['is_vip'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="source">Źródło</label>
            <select id="source" name="source">
                <option value="MANUAL" <?= $form['source'] === 'MANUAL' ? 'selected' : '' ?>>
                    Ręcznie
                </option>
                <option value="DIRECT" <?= $form['source'] === 'DIRECT' ? 'selected' : '' ?>>
                    Bezpośrednio
                </option>
                <option value="WWW" <?= $form['source'] === 'WWW' ? 'selected' : '' ?>>
                    Strona WWW
                </option>
                <option value="BOOKING" <?= $form['source'] === 'BOOKING' ? 'selected' : '' ?>>
                    Booking.com
                </option>
                <option value="PHONE" <?= $form['source'] === 'PHONE' ? 'selected' : '' ?>>
                    Telefon
                </option>
                <option value="AIRBNB" <?= $form['source'] === 'AIRBNB' ? 'selected' : '' ?>>
                    Airbnb
                </option>
                <option value="ICAL_OTHER" <?= $form['source'] === 'ICAL_OTHER' ? 'selected' : '' ?>>
                    iCal — inne
                </option>
            </select>

            <?php if (isset($errors['source'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['source'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field form-field--full">
            <label for="preferences">
                Preferencje pobytu
            </label>

            <textarea
                id="preferences"
                name="preferences"
                rows="4"
                placeholder="Np. preferowany domek, późniejszy przyjazd, łóżeczko dla dziecka, sposób przygotowania pobytu."
            ><?= htmlspecialchars(
                $form['preferences'],
                ENT_QUOTES,
                'UTF-8'
            ) ?></textarea>
        </div>

        <div class="form-field form-field--full">
            <label for="important_notes">
                Ważne informacje
            </label>

            <textarea
                id="important_notes"
                name="important_notes"
                rows="4"
                placeholder="Np. szczególne wymagania, ważne ustalenia, informacje wymagające uwagi przed kolejnym pobytem."
            ><?= htmlspecialchars(
                $form['important_notes'],
                ENT_QUOTES,
                'UTF-8'
            ) ?></textarea>

            <small>
                Informacje widoczne tylko w panelu administratora.
            </small>
        </div>

        <div class="form-field form-field--full">
            <label for="notes">Notatki wewnętrzne</label>
            <textarea
                id="notes"
                name="notes"
                rows="4"
            ><?= htmlspecialchars($form['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button
            class="button button--primary"
            type="submit"
            <?= $canSave ? '' : 'disabled' ?>
        >
            <?= htmlspecialchars($submitLabelValue, ENT_QUOTES, 'UTF-8') ?>
        </button>

        <a class="button button--secondary" href="/admin/goscie">
            Anuluj
        </a>
    </div>
</form>

<script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {
            var street =
                document.getElementById(
                    'street'
                );
            var postalCode =
                document.getElementById(
                    'postal_code'
                );
            var city =
                document.getElementById(
                    'city'
                );
            var country =
                document.getElementById(
                    'country'
                );
            var fullAddress =
                document.getElementById(
                    'full_address'
                );
            var syncSource =
                document.getElementById(
                    'address_sync_source'
                );

            if (
                !street
                || !postalCode
                || !city
                || !country
                || !fullAddress
                || !syncSource
            ) {
                return;
            }

            function composeAddress() {
                var cityLine = [
                    postalCode.value.trim(),
                    city.value.trim()
                ]
                    .filter(Boolean)
                    .join(' ');

                fullAddress.value = [
                    street.value.trim(),
                    cityLine,
                    country.value.trim()
                ]
                    .filter(Boolean)
                    .join(', ');

                syncSource.value =
                    'structured';
            }

            function parseFullAddress() {
                var parts =
                    fullAddress.value
                        .split(
                            /,|\r?\n/
                        )
                        .map(
                            function (part) {
                                return part.trim();
                            }
                        )
                        .filter(Boolean);

                if (
                    parts.length === 0
                ) {
                    return;
                }

                var parsedStreet =
                    parts[0] || '';
                var parsedPostal = '';
                var parsedCity = '';
                var parsedCountry = '';

                for (
                    var index = 1;
                    index < parts.length;
                    index++
                ) {
                    var part =
                        parts[index];

                    var postalCity =
                        part.match(
                            /^([A-Z]{0,2}\s*\d[\dA-Z -]{1,9})\s+(.+)$/i
                        );

                    if (postalCity) {
                        parsedPostal =
                            postalCity[1]
                                .trim();
                        parsedCity =
                            postalCity[2]
                                .trim();

                        break;
                    }

                    var postalOnly =
                        part.match(
                            /^([A-Z]{0,2}\s*\d[\dA-Z -]{1,9})$/i
                        );

                    if (postalOnly) {
                        parsedPostal =
                            postalOnly[1]
                                .trim();

                        if (
                            index > 1
                            && parsedCity === ''
                        ) {
                            parsedCity =
                                parts[
                                    index - 1
                                ];
                        }

                        break;
                    }
                }

                if (
                    parsedCity === ''
                    && parts[1]
                    && parts[1]
                        !== parsedPostal
                ) {
                    parsedCity =
                        parts[1];
                }

                if (
                    parts.length >= 3
                ) {
                    var last =
                        parts[
                            parts.length - 1
                        ];

                    if (
                        last !== parsedStreet
                        && last !== parsedCity
                        && last !== parsedPostal
                        && (
                            parsedPostal === ''
                            || !last.includes(
                                parsedPostal
                            )
                        )
                    ) {
                        parsedCountry =
                            last;
                    }
                }

                if (parsedStreet !== '') {
                    street.value =
                        parsedStreet;
                }

                if (parsedPostal !== '') {
                    postalCode.value =
                        parsedPostal;
                }

                if (parsedCity !== '') {
                    city.value =
                        parsedCity;
                }

                if (parsedCountry !== '') {
                    country.value =
                        parsedCountry;
                }

                syncSource.value = 'full';
            }

            [
                street,
                postalCode,
                city,
                country
            ].forEach(
                function (input) {
                    input.addEventListener(
                        'input',
                        composeAddress
                    );
                }
            );

            fullAddress.addEventListener(
                'change',
                parseFullAddress
            );

            fullAddress.addEventListener(
                'blur',
                parseFullAddress
            );
        }
    );
</script>

