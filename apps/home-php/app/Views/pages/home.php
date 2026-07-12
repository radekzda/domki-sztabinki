<?php

declare(strict_types=1);

/**
 * @var string $title
 */

$settings = defaultSettingsForm();
$cabins = [];
$cabinImages = [];
$databaseMessage = null;

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

                    <a class="button button--secondary" href="#kontakt">
                        Kontakt
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

        <div class="panel" id="kontakt">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Kontakt</p>

                    <h2>Zapytaj o wolny termin</h2>

                    <p>
                        Publiczny formularz zapytania dodamy w następnym kroku. Na razie możesz użyć danych kontaktowych.
                    </p>
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
                <a class="button button--primary" href="/logowanie">
                    Panel administratora
                </a>
            </div>
        </div>
    </div>
</section>