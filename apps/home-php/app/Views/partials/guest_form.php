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
            <label for="nationality">Narodowość</label>

            <input
                id="nationality"
                name="nationality"
                type="text"
                value="<?= htmlspecialchars(
                    $form['nationality'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
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
                <option value="WWW" <?= $form['source'] === 'WWW' ? 'selected' : '' ?>>
                    WWW
                </option>
                <option value="BOOKING" <?= $form['source'] === 'BOOKING' ? 'selected' : '' ?>>
                    Booking
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
                <option value="BASE44" <?= $form['source'] === 'BASE44' ? 'selected' : '' ?>>
                    Base44
                </option>
            </select>

            <?php if (isset($errors['source'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['source'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field form-field--full">
            <label for="external_id">ID z Base44</label>
            <input
                id="external_id"
                name="external_id"
                type="text"
                value="<?= htmlspecialchars($form['external_id'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <small>
                To pole służy do dopasowania importu. Nie jest widoczne dla gościa.
            </small>
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
