<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, string>|null $inquiryForm
 * @var array<string, string>|null $inquiryErrors
 * @var string|null $publicDatabaseMessage
 */

$settings = defaultSettingsForm();
$cabins = [];
$cabinImages = [];
$databaseMessage = null;

$form = isset($inquiryForm) && is_array($inquiryForm)
    ? $inquiryForm
    : defaultPublicInquiryForm();

$errors = isset($inquiryErrors) && is_array($inquiryErrors)
    ? $inquiryErrors
    : [];

$inquiryMessage = isset($publicDatabaseMessage) && is_string($publicDatabaseMessage)
    ? $publicDatabaseMessage
    : null;

$successMessage = isset($_GET['inquiry_sent'])
    ? 'Dziękujemy. Zapytanie zostało wysłane. Odpowiemy najszybciej jak to możliwe.'
    : null;

if (!Database::canAttemptConnection()) {
    $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Strona pokazuje podstawowe dane domyślne.';
} else {
    try {
        $settings = SettingsRepository::all();

        $allCabins = CabinRepository::all();

        foreach ($allCabins as $cabin) {
            if ((int) $cabin['is_active'] !== 1) {
                continue;
            }

            $cabins[] = $cabin;
            $images = CabinImageRepository::allForCabin((int) $cabin['id']);
            $mainImage = null;

            foreach ($images as $image) {
                if ((int) $image['is_main'] === 1) {
                    $mainImage = $image;
                    break;
                }
            }

            if ($mainImage === null && $images !== []) {
                $mainImage = $images[0];
            }

            $cabinImages[(int) $cabin['id']] = $mainImage;
        }
    } catch (Throwable $exception) {
        $databaseMessage = 'Nie udało się pobrać danych publicznych z bazy: ' . $exception->getMessage();
    }
}

$formatPublicPrice = static function (int $amount, string $currency): string {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
};

$currency = $settings['currency'] !== '' ? $settings['currency'] : 'PLN';
?>
<section class="page-section">
    <div class="container">
        <div class="panel">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Wypoczynek nad jeziorem</p>

                    <h1><?= htmlspecialchars($settings['property_name'], ENT_QUOTES, 'UTF-8') ?></h1>

                    <p>
                        <?= nl2br(htmlspecialchars($settings['public_short_description'], ENT_QUOTES, 'UTF-8')) ?>
                    </p>
                </div>

                <div class="page-header__actions">
                    <a class="button button--primary" href="#domki">
                        Zobacz domki
                    </a>

                    <a class="button button--secondary" href="#zapytanie">
                        Zapytaj o termin
                    </a>
                </div>
            </div>

            <?php if (isset($databaseMessage) && is_string($databaseMessage) && $databaseMessage !== ''): ?>
                <div class="alert alert--warning">
                    <?= htmlspecialchars($databaseMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <span>Zameldowanie</span>
                    <strong>od <?= htmlspecialchars($settings['check_in_time'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="stat-card">
                    <span>Wymeldowanie</span>
                    <strong>do <?= htmlspecialchars($settings['check_out_time'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="stat-card">
                    <span>Minimum pobytu</span>
                    <strong>
                        <?= htmlspecialchars($settings['minimum_nights'], ENT_QUOTES, 'UTF-8') ?>
                        noce
                    </strong>
                </div>

                <div class="stat-card">
                    <span>Aktywne domki</span>
                    <strong><?= htmlspecialchars((string) count($cabins), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>
        </div>

        <div class="panel" id="domki">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Oferta</p>

                    <h2>Nasze domki</h2>

                    <p>
                        Poniżej widoczne są tylko domki aktywne w panelu administratora.
                    </p>
                </div>
            </div>

            <?php if ($cabins === []): ?>
                <div class="empty-state">
                    <strong>Brak aktywnych domków do pokazania</strong>

                    <p>
                        Dodaj domek w panelu administratora albo ustaw istniejący domek jako aktywny.
                    </p>

                    <a class="button button--secondary" href="/admin/domki">
                        Przejdź do panelu domków
                    </a>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 24px;">
                    <?php foreach ($cabins as $cabin): ?>
                        <?php
                        $image = $cabinImages[(int) $cabin['id']] ?? null;
                        ?>

                        <article class="panel" style="margin: 0; box-shadow: none; border: 1px solid #e5e7eb;">
                            <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, 420px); gap: 24px; align-items: start;">
                                <div>
                                    <p class="eyebrow">
                                        <?= htmlspecialchars($cabin['short_name'] ?? 'Domek', ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <h3><?= htmlspecialchars($cabin['name'], ENT_QUOTES, 'UTF-8') ?></h3>

                                    <p>
                                        <?= nl2br(htmlspecialchars($cabin['description'], ENT_QUOTES, 'UTF-8')) ?>
                                    </p>

                                    <div class="dashboard-grid">
                                        <div class="stat-card">
                                            <span>Maksymalnie</span>
                                            <strong>
                                                <?= htmlspecialchars((string) $cabin['max_guests'], ENT_QUOTES, 'UTF-8') ?>
                                                os.
                                            </strong>
                                        </div>

                                        <div class="stat-card">
                                            <span>Sypialnie</span>
                                            <strong><?= htmlspecialchars((string) $cabin['bedrooms'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>

                                        <div class="stat-card">
                                            <span>Łazienki</span>
                                            <strong><?= htmlspecialchars((string) $cabin['bathrooms'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                    </div>

                                    <div class="table-wrapper">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Długość pobytu</th>
                                                    <th>Cena za noc</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr>
                                                    <td>1 noc</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice((int) $cabin['price_one_night'], $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>2 noce</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice((int) $cabin['price_two_nights'], $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>3 noce</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice((int) $cabin['price_three_nights'], $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>4 noce</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice((int) $cabin['price_four_nights'], $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>5 nocy</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice((int) $cabin['price_five_nights'], $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>6 nocy</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice((int) $cabin['price_six_nights'], $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>7+ nocy</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice((int) $cabin['price_seven_plus_nights'], $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div>
                                    <?php if (is_array($image)): ?>
                                        <img
                                            src="<?= htmlspecialchars($image['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="<?= htmlspecialchars($image['alt_text'] ?? $cabin['name'], ENT_QUOTES, 'UTF-8') ?>"
                                            style="width: 100%; height: 320px; object-fit: cover; border-radius: 18px; border: 1px solid #e5e7eb;"
                                        >
                                    <?php else: ?>
                                        <div
                                            class="empty-state"
                                            style="min-height: 320px; display: flex; flex-direction: column; justify-content: center;"
                                        >
                                            <strong>Brak zdjęcia głównego</strong>

                                            <p>
                                                Dodaj zdjęcie w panelu administratora.
                                            </p>

                                            <a
                                                class="button button--secondary"
                                                href="/admin/domki/zdjecia?id=<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                Dodaj zdjęcie
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Dodatkowe informacje</p>

                    <h2>Ceny dodatkowe i zasady</h2>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <span>Łowienie w jeziorze</span>
                    <strong>
                        <?= htmlspecialchars($formatPublicPrice((int) $settings['fishing_price'], $currency), ENT_QUOTES, 'UTF-8') ?>
                        / dzień
                    </strong>
                </div>

                <div class="stat-card">
                    <span>Balia / kubil</span>
                    <strong>
                        <?= htmlspecialchars($formatPublicPrice((int) $settings['hot_tub_price'], $currency), ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                </div>
            </div>

            <div class="empty-state">
                <strong>Zasady pobytu</strong>

                <p>
                    <?= nl2br(htmlspecialchars($settings['booking_rules'], ENT_QUOTES, 'UTF-8')) ?>
                </p>
            </div>
        </div>

        <div class="panel" id="zapytanie">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Zapytanie</p>

                    <h2>Zapytaj o wolny termin</h2>

                    <p>
                        Wyślij zapytanie o pobyt. Po wysłaniu pojawi się ono w panelu administratora
                        w zakładce „Zapytania”.
                    </p>
                </div>
            </div>

            <?php if (isset($successMessage) && is_string($successMessage) && $successMessage !== ''): ?>
                <div class="alert alert--success">
                    <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (isset($inquiryMessage) && is_string($inquiryMessage) && $inquiryMessage !== ''): ?>
                <div class="alert alert--warning">
                    <?= htmlspecialchars($inquiryMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="alert alert--danger">
                    Popraw błędy w formularzu.
                </div>
            <?php endif; ?>

            <form class="form form--wide" method="post" action="/zapytanie">
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
                        <label for="phone">Telefon</label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            value="<?= htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['phone'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="email">E-mail</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="<?= htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8') ?>"
                        >

                        <?php if (isset($errors['email'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="cabin_id">Domek</label>
                        <select id="cabin_id" name="cabin_id">
                            <option value="">Dowolny / proszę zaproponować</option>

                            <?php foreach ($cabins as $cabin): ?>
                                <option
                                    value="<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $form['cabin_id'] === (string) $cabin['id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($cabin['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (isset($errors['cabin_id'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['cabin_id'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="date_from">Przyjazd</label>
                        <input
                            id="date_from"
                            name="date_from"
                            type="date"
                            value="<?= htmlspecialchars($form['date_from'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['date_from'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['date_from'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="date_to">Wyjazd</label>
                        <input
                            id="date_to"
                            name="date_to"
                            type="date"
                            value="<?= htmlspecialchars($form['date_to'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['date_to'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['date_to'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="adults">Dorośli</label>
                        <input
                            id="adults"
                            name="adults"
                            type="number"
                            min="1"
                            step="1"
                            value="<?= htmlspecialchars($form['adults'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['adults'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['adults'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="children">Dzieci</label>
                        <input
                            id="children"
                            name="children"
                            type="number"
                            min="0"
                            step="1"
                            value="<?= htmlspecialchars($form['children'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['children'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['children'], ENT_QUOTES, 'UTF-8') ?></span>
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
                        <label for="notes">Wiadomość / dodatkowe informacje</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="5"
                        ><?= htmlspecialchars($form['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button button--primary" type="submit">
                        Wyślij zapytanie
                    </button>
                </div>
            </form>
        </div>

        <div class="panel" id="kontakt">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Kontakt</p>

                    <h2>Dane kontaktowe</h2>
                </div>
            </div>

            <div class="status-list">
                <div class="status-row">
                    <span>Obiekt</span>
                    <strong><?= htmlspecialchars($settings['property_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="status-row">
                    <span>E-mail</span>
                    <strong><?= htmlspecialchars($settings['contact_email'] !== '' ? $settings['contact_email'] : '—', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="status-row">
                    <span>Telefon</span>
                    <strong><?= htmlspecialchars($settings['contact_phone'] !== '' ? $settings['contact_phone'] : '—', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="status-row">
                    <span>Adres</span>
                    <strong>
                        <?= htmlspecialchars($settings['address_line'], ENT_QUOTES, 'UTF-8') ?>,
                        <?= htmlspecialchars($settings['postal_code'], ENT_QUOTES, 'UTF-8') ?>
                        <?= htmlspecialchars($settings['city'], ENT_QUOTES, 'UTF-8') ?>,
                        <?= htmlspecialchars($settings['country'], ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                </div>
            </div>

            <div class="form-actions">
                <a class="button button--secondary" href="/logowanie">
                    Panel administratora
                </a>
            </div>
        </div>
    </div>
</section>