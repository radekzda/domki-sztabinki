<?php

declare(strict_types=1);

/**
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var array<int, array{
 *     id: int,
 *     name: string,
 *     short_name: string|null,
 *     max_guests: int,
 *     bedrooms: int,
 *     bathrooms: int,
 *     price_per_night: int,
 *     price_one_night: int,
 *     price_two_nights: int,
 *     price_three_nights: int,
 *     price_four_nights: int,
 *     price_five_nights: int,
 *     price_six_nights: int,
 *     price_seven_plus_nights: int,
 *     is_active: int,
 *     sort_order: int,
 *     created_at: string
 * }> $cabins
 * @var array<int, array{
 *     id: int,
 *     first_name: string,
 *     last_name: string,
 *     email: string,
 *     phone: string|null,
 *     country: string|null,
 *     city: string|null,
 *     is_vip: int,
 *     source: string,
 *     created_at: string
 * }> $guests
 * @var bool $canSave
 * @var string $action
 * @var string $submitLabel
 * @var bool $lockIcalSourceFields
 * @var array<string, string> $pricingSettings
 * @var bool $enableLivePricing
 */

$actionValue = isset($action) && is_string($action) ? $action : '';
$submitLabelValue = isset($submitLabel) && is_string($submitLabel) ? $submitLabel : 'Zapisz rezerwację';
$lockIcalSourceFieldsValue = isset($lockIcalSourceFields)
    && $lockIcalSourceFields === true;
$enableLivePricingValue = isset($enableLivePricing)
    && $enableLivePricing === true;
$pricingSettingsValue = isset($pricingSettings)
    && is_array($pricingSettings)
        ? $pricingSettings
        : [];
$returnUrl = isset($_GET['return']) && is_string($_GET['return']) ? $_GET['return'] : '';

if ($returnUrl === '' && isset($_POST['return_url']) && is_string($_POST['return_url'])) {
    $returnUrl = $_POST['return_url'];
}

$canReturnToCalendar = str_starts_with($returnUrl, '/admin/kalendarz');
?>
<form class="form form--wide" method="post" action="<?= htmlspecialchars($actionValue, ENT_QUOTES, 'UTF-8') ?>">
    <?= csrfField() ?>
<?php if ($canReturnToCalendar): ?>
        <input
            type="hidden"
            name="return_url"
            value="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>"
        >
    <?php endif; ?>
    <div class="form-grid">
        <div class="form-field form-field--full">
            <label for="guest_id">Powiązany gość</label>
            <select id="guest_id" name="guest_id">
                <option value="">Utwórz / dopasuj automatycznie po e-mailu</option>

                <?php foreach ($guests as $guest): ?>
                    <option
                        value="<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-guest-name="<?= htmlspecialchars(trim($guest['first_name'] . ' ' . $guest['last_name']), ENT_QUOTES, 'UTF-8') ?>"
                        data-guest-first-name="<?= htmlspecialchars($guest['first_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-guest-last-name="<?= htmlspecialchars($guest['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-guest-email="<?= htmlspecialchars($guest['email'], ENT_QUOTES, 'UTF-8') ?>"
                        data-guest-phone="<?= htmlspecialchars($guest['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-guest-street="<?= htmlspecialchars($guest['street'] ?? $guest['full_address'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-guest-postal-code="<?= htmlspecialchars($guest['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-guest-city="<?= htmlspecialchars($guest['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-guest-country="<?= htmlspecialchars($guest['country'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        <?= $form['guest_id'] === (string) $guest['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name'] . ' — ' . $guest['email'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (isset($errors['guest_id'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['guest_id'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>

            <span class="form-hint">
                Gdy zostawisz puste, system znajdzie gościa po e-mailu albo utworzy nową kartę gościa.
            </span>
        </div>

        <div class="form-field form-field--full">
            <label for="cabin_id">Domek</label>
            <select
                id="cabin_id"
                name="cabin_id"
                required
                <?= $lockIcalSourceFieldsValue ? 'disabled' : '' ?>
            >
                <option value="">Wybierz domek</option>

                <?php foreach ($cabins as $cabin): ?>
                    <option
                        value="<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-max-guests="<?= htmlspecialchars((string) $cabin['max_guests'], ENT_QUOTES, 'UTF-8') ?>"
                        <?= $form['cabin_id'] === (string) $cabin['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars(
                            $cabin['name']
                            . ' — maks. '
                            . $cabin['max_guests']
                            . ' os.',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if ($lockIcalSourceFieldsValue): ?>
                <input
                    type="hidden"
                    name="cabin_id"
                    value="<?= htmlspecialchars($form['cabin_id'], ENT_QUOTES, 'UTF-8') ?>"
                >
            <?php endif; ?>

            <?php if (isset($errors['cabin_id'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['cabin_id'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>

            <span class="form-hint">
                <?php if ($lockIcalSourceFieldsValue): ?>
                    Domek pochodzi z blokady iCal i nie może być zmieniony podczas jej przekształcania w rezerwację.
                <?php else: ?>
                    Łączna liczba dorosłych i dzieci nie może przekroczyć maksymalnej liczby osób dla wybranego domku.
                <?php endif; ?>
            </span>
        </div>

        <div class="form-field">
            <label for="start_date">Od</label>
            <input
                id="start_date"
                name="start_date"
                type="date"
                value="<?= htmlspecialchars($form['start_date'], ENT_QUOTES, 'UTF-8') ?>"
                required
                <?= $lockIcalSourceFieldsValue ? 'readonly' : '' ?>
            >

            <?php if (isset($errors['start_date'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['start_date'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="end_date">Do</label>
            <input
                id="end_date"
                name="end_date"
                type="date"
                value="<?= htmlspecialchars($form['end_date'], ENT_QUOTES, 'UTF-8') ?>"
                required
                <?= $lockIcalSourceFieldsValue ? 'readonly' : '' ?>
            >

            <?php if (isset($errors['end_date'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['end_date'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="check_in_time">Godzina przyjazdu</label>
            <input
                id="check_in_time"
                name="check_in_time"
                type="time"
                value="<?= htmlspecialchars($form['check_in_time'] ?? '15:00', ENT_QUOTES, 'UTF-8') ?>"
            >

            <?php if (isset($errors['check_in_time'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['check_in_time'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="check_out_time">Godzina wyjazdu</label>
            <input
                id="check_out_time"
                name="check_out_time"
                type="time"
                value="<?= htmlspecialchars($form['check_out_time'] ?? '11:00', ENT_QUOTES, 'UTF-8') ?>"
            >

            <?php if (isset($errors['check_out_time'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['check_out_time'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="first_name">Imię gościa</label>
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
            <label for="last_name">Nazwisko gościa</label>
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
            <label for="email">E-mail (opcjonalnie)</label>
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
            <label for="phone">Telefon</label>
            <input
                id="phone"
                name="phone"
                type="text"
                value="<?= htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-field form-field--full">
            <label for="street">Ulica i numer</label>
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

        <div class="form-field form-field--full">
            <label for="country">Kraj</label>
            <input
                id="country"
                name="country"
                type="text"
                value="<?= htmlspecialchars($form['country'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-field">
            <label for="adults">Dorośli</label>
            <input
                id="adults"
                name="adults"
                type="number"
                min="1"
                max="30"
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
                max="30"
                step="1"
                value="<?= htmlspecialchars($form['children'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <?php if (isset($errors['children'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['children'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>


        <?php if ($enableLivePricingValue): ?>
            <div class="form-field">
                <label for="reservation_total_price">
                    Kwota
                    <span id="reservation_price_formula"></span>
                </label>
                <input
                    id="reservation_total_price"
                    name="total_price"
                    type="number"
                    min="0"
                    step="1"
                    value="<?= htmlspecialchars(
                        (string) ($form['total_price'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <?php if (isset($errors['total_price'])): ?>
                    <span class="form-error"><?= htmlspecialchars($errors['total_price'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="paid_amount">Wpłacono</label>
                <input
                    id="paid_amount"
                    name="paid_amount"
                    type="number"
                    min="0"
                    step="1"
                    value="<?= htmlspecialchars($form['paid_amount'], ENT_QUOTES, 'UTF-8') ?>"
                >

                <?php if (isset($errors['paid_amount'])): ?>
                    <span class="form-error"><?= htmlspecialchars($errors['paid_amount'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <div class="form-field form-field--full">
                <label for="reservation_remaining_amount">
                    Pozostało do zapłaty
                </label>
                <input
                    id="reservation_remaining_amount"
                    type="text"
                    value="—"
                    readonly
                >
            </div>
        <?php endif; ?>

        <div class="form-field">
            <label for="status">Status rezerwacji</label>
            <select id="status" name="status">
                <option value="PENDING" <?= $form['status'] === 'PENDING' ? 'selected' : '' ?>>
                    Oczekuje
                </option>
                <option value="CONFIRMED" <?= $form['status'] === 'CONFIRMED' ? 'selected' : '' ?>>
                    Potwierdzona
                </option>
                <option value="CHECKED_IN" <?= $form['status'] === 'CHECKED_IN' ? 'selected' : '' ?>>
                    Zameldowany
                </option>
                <option value="CHECKED_OUT" <?= $form['status'] === 'CHECKED_OUT' ? 'selected' : '' ?>>
                    Wymeldowany
                </option>
                <option value="CANCELLED" <?= $form['status'] === 'CANCELLED' ? 'selected' : '' ?>>
                    Anulowana
                </option>
            </select>

            <?php if (isset($errors['status'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="payment_status">Status płatności</label>
            <select id="payment_status" name="payment_status">
                <option value="PENDING" <?= $form['payment_status'] === 'PENDING' ? 'selected' : '' ?>>
                    Oczekuje
                </option>
                <option value="PARTIAL" <?= $form['payment_status'] === 'PARTIAL' ? 'selected' : '' ?>>
                    Częściowa
                </option>
                <option value="PAID" <?= $form['payment_status'] === 'PAID' ? 'selected' : '' ?>>
                    Opłacona
                </option>
                <option value="REFUNDED" <?= $form['payment_status'] === 'REFUNDED' ? 'selected' : '' ?>>
                    Zwrócona
                </option>
            </select>

            <?php if (isset($errors['payment_status'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['payment_status'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <?php if (!$enableLivePricingValue): ?>
        <div class="form-field">
            <label for="paid_amount">Wpłacono</label>
            <input
                id="paid_amount"
                name="paid_amount"
                type="number"
                min="0"
                step="1"
                value="<?= htmlspecialchars($form['paid_amount'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <?php if (isset($errors['paid_amount'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['paid_amount'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <?php endif; ?>

        <div class="form-field">
            <label for="source">Źródło</label>
            <select
                id="source"
                name="source"
                <?= $lockIcalSourceFieldsValue ? 'disabled' : '' ?>
            >
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
            </select>

            <?php if ($lockIcalSourceFieldsValue): ?>
                <input
                    type="hidden"
                    name="source"
                    value="<?= htmlspecialchars($form['source'], ENT_QUOTES, 'UTF-8') ?>"
                >
            <?php endif; ?>

            <?php if (isset($errors['source'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['source'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field form-field--full">
            <label for="notes">Notatki</label>
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
          <?php if ($canReturnToCalendar): ?>
              <a
                  class="button button--secondary"
                  href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>"
              >
                  Wróć do kalendarza
              </a>
          <?php endif; ?>


        <a class="button button--secondary" href="/admin/rezerwacje">
            Anuluj
        </a>
    </div>
</form>


<!-- M13.64 reservation guest autofill -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var guestSelect = document.getElementById('guest_id');

        if (!guestSelect) {
            return;
        }

        var firstNameInput = document.getElementById('first_name');
        var lastNameInput = document.getElementById('last_name');
        var emailInput = document.getElementById('email');
        var phoneInput = document.getElementById('phone');
        var streetInput = document.getElementById('street');
        var postalCodeInput = document.getElementById('postal_code');
        var cityInput = document.getElementById('city');
        var countryInput = document.getElementById('country');

        guestSelect.addEventListener('change', function () {
            var selectedOption = guestSelect.options[guestSelect.selectedIndex];

            if (!selectedOption || selectedOption.value === '') {
                return;
            }

            if (firstNameInput) {
                firstNameInput.value =
                    selectedOption.dataset.guestFirstName || '';
            }

            if (lastNameInput) {
                lastNameInput.value =
                    selectedOption.dataset.guestLastName || '';
            }

            if (emailInput) {
                emailInput.value =
                    selectedOption.dataset.guestEmail || '';
            }

            if (phoneInput) {
                phoneInput.value =
                    selectedOption.dataset.guestPhone || '';
            }

            if (streetInput) {
                streetInput.value =
                    selectedOption.dataset.guestStreet || '';
            }

            if (postalCodeInput) {
                postalCodeInput.value =
                    selectedOption.dataset.guestPostalCode || '';
            }

            if (cityInput) {
                cityInput.value =
                    selectedOption.dataset.guestCity || '';
            }

            if (countryInput) {
                countryInput.value =
                    selectedOption.dataset.guestCountry || '';
            }
        });
    });
</script>

<?php if ($enableLivePricingValue): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var pricing = <?= json_encode(
            [
                'price_one_night' => (int) (
                    $pricingSettingsValue['price_one_night']
                    ?? 800
                ),
                'price_two_nights' => (int) (
                    $pricingSettingsValue['price_two_nights']
                    ?? 440
                ),
                'price_three_nights' => (int) (
                    $pricingSettingsValue['price_three_nights']
                    ?? 430
                ),
                'price_four_nights' => (int) (
                    $pricingSettingsValue['price_four_nights']
                    ?? 420
                ),
                'price_five_nights' => (int) (
                    $pricingSettingsValue['price_five_nights']
                    ?? 410
                ),
                'price_six_nights' => (int) (
                    $pricingSettingsValue['price_six_nights']
                    ?? 400
                ),
                'price_seven_plus_nights' => (int) (
                    $pricingSettingsValue['price_seven_plus_nights']
                    ?? 350
                ),
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        ) ?>;

        var startInput =
            document.getElementById('start_date');
        var endInput =
            document.getElementById('end_date');
        var totalInput =
            document.getElementById(
                'reservation_total_price'
            );
        var formulaLabel =
            document.getElementById(
                'reservation_price_formula'
            );
        var paidInput =
            document.getElementById('paid_amount');
        var remainingInput =
            document.getElementById(
                'reservation_remaining_amount'
            );
        var paymentStatusSelect =
            document.getElementById('payment_status');

        if (
            !startInput
            || !endInput
            || !totalInput
            || !formulaLabel
            || !paidInput
            || !remainingInput
            || !paymentStatusSelect
        ) {
            return;
        }

        var totalPrice = 0;
        var suggestedTotalPrice = 0;

        function money(value) {
            return new Intl.NumberFormat(
                'pl-PL',
                {
                    maximumFractionDigits: 0
                }
            ).format(value) + ' zł';
        }

        function calculateNights() {
            if (
                startInput.value === ''
                || endInput.value === ''
            ) {
                return 0;
            }

            var start = new Date(
                startInput.value + 'T00:00:00Z'
            );
            var end = new Date(
                endInput.value + 'T00:00:00Z'
            );

            if (
                Number.isNaN(start.getTime())
                || Number.isNaN(end.getTime())
                || end <= start
            ) {
                return 0;
            }

            return Math.round(
                (
                    end.getTime()
                    - start.getTime()
                )
                / 86400000
            );
        }

        function nightlyPrice(nights) {
            if (nights <= 1) {
                return pricing.price_one_night;
            }

            if (nights === 2) {
                return pricing.price_two_nights;
            }

            if (nights === 3) {
                return pricing.price_three_nights;
            }

            if (nights === 4) {
                return pricing.price_four_nights;
            }

            if (nights === 5) {
                return pricing.price_five_nights;
            }

            if (nights === 6) {
                return pricing.price_six_nights;
            }

            return pricing.price_seven_plus_nights;
        }

        function paidAmount() {
            var value = Number(
                paidInput.value || 0
            );

            return Number.isFinite(value)
                ? Math.max(0, value)
                : 0;
        }

        function currentTotalPrice() {
            var value = Number(
                totalInput.value || 0
            );

            return Number.isFinite(value)
                ? Math.max(0, value)
                : 0;
        }

        function updateRemaining() {
            totalPrice = currentTotalPrice();

            var paid = paidAmount();

            remainingInput.value = money(
                Math.max(
                    totalPrice - paid,
                    0
                )
            );
        }

        function updateSummary() {
            var nights = calculateNights();

            if (nights < 1) {
                totalPrice = 0;
                totalInput.value = '—';
                formulaLabel.textContent = '';
                remainingInput.value = '—';

                return;
            }

            var pricePerNight =
                nightlyPrice(nights);

            suggestedTotalPrice =
                nights * pricePerNight;

            totalInput.value =
                String(suggestedTotalPrice);

            totalPrice =
                suggestedTotalPrice;

            formulaLabel.textContent =
                ' ('
                + nights
                + (
                    nights === 1
                        ? ' noc × '
                        : ' nocy × '
                )
                + money(pricePerNight)
                + ')';

            if (
                paymentStatusSelect.value
                === 'PAID'
            ) {
                paidInput.value =
                    String(totalPrice);
            } else if (
                Number(paidInput.value || 0)
                > totalPrice
            ) {
                paidInput.value =
                    String(totalPrice);
            }

            updateRemaining();
        }

        function syncPaidWithStatus() {
            if (
                paymentStatusSelect.value
                === 'PAID'
            ) {
                paidInput.value =
                    String(totalPrice);
            } else if (
                paymentStatusSelect.value
                === 'PENDING'
            ) {
                paidInput.value = '0';
            }

            updateRemaining();
        }

        startInput.addEventListener(
            'change',
            updateSummary
        );

        endInput.addEventListener(
            'change',
            updateSummary
        );

        totalInput.addEventListener(
            'input',
            function () {
                totalPrice =
                    currentTotalPrice();

                if (
                    paymentStatusSelect.value
                    === 'PAID'
                ) {
                    paidInput.value =
                        String(totalPrice);
                } else if (
                    Number(
                        paidInput.value || 0
                    ) > totalPrice
                ) {
                    paidInput.value =
                        String(totalPrice);
                }

                updateRemaining();
            }
        );

        paidInput.addEventListener(
            'input',
            updateRemaining
        );

        paymentStatusSelect.addEventListener(
            'change',
            syncPaidWithStatus
        );

        var initialTotalPrice =
            totalInput.value;

        updateSummary();

        if (
            initialTotalPrice !== ''
            && Number.isFinite(
                Number(initialTotalPrice)
            )
        ) {
            totalInput.value =
                initialTotalPrice;

            totalPrice =
                currentTotalPrice();
        }

        syncPaidWithStatus();
    });
</script>
<?php endif; ?>

