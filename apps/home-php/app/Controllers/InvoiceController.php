<?php

declare(strict_types=1);

final class InvoiceController
{
    public static function createFromReservation(): void
    {
        Auth::requireAdmin();

        $reservationId = self::reservationIdFromQuery();

        if ($reservationId === null) {
            self::renderError(
                'Nieprawidłowy adres',
                'Brakuje prawidłowego identyfikatora rezerwacji.',
                400
            );

            return;
        }

        try {
            $context = self::loadContext(
                $reservationId
            );

            $form = self::formFromContext(
                $context
            );

            self::renderForm(
                $context,
                $form,
                [],
                null,
                true
            );
        } catch (Throwable $exception) {
            self::renderError(
                'Nie można przygotować faktury',
                AppErrorHandler::safeMessage(
                    $exception
                ),
                422
            );
        }
    }

    public static function storeFromReservation(): void
    {
        Auth::requireAdmin();
        requireValidCsrf();

        $reservationId = filter_var(
            $_POST['reservation_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            !is_int($reservationId)
            || $reservationId < 1
        ) {
            self::renderError(
                'Nieprawidłowe dane',
                'Nie można ustalić rezerwacji dla faktury.',
                400
            );

            return;
        }

        try {
            $context = self::loadContext(
                $reservationId
            );
        } catch (Throwable $exception) {
            self::renderError(
                'Nie można przygotować faktury',
                AppErrorHandler::safeMessage(
                    $exception
                ),
                422
            );

            return;
        }

        $form = self::formFromPost();

        $errors = self::validateForm(
            $form
        );

        if ($errors !== []) {
            self::renderForm(
                $context,
                $form,
                $errors,
                null,
                true,
                422
            );

            return;
        }

        try {
            $quantity = max(
                1,
                (int) (
                    $context['reservation']['nights']
                    ?? 1
                )
            );

            $amounts = self::calculateAmounts(
                $form['gross_amount'],
                $form['vat_rate_code']
            );

            $invoiceId = InvoiceRepository::create(
                [
                    'reservation_id' =>
                        $reservationId,

                    'seller_id' =>
                        (int) $context[
                            'seller'
                        ]['id'],

                    'series' =>
                        $form['series'],

                    'issue_date' =>
                        $form['issue_date'],

                    'sale_date' =>
                        $form['sale_date'],

                    'due_date' =>
                        $form['due_date'],

                    'status' =>
                        'ISSUED',

                    'currency' =>
                        'PLN',

                    'payment_method' =>
                        $form[
                            'payment_method'
                        ],

                    'payment_status' =>
                        $form[
                            'payment_status'
                        ],

                    'buyer_type' =>
                        $form['buyer_type'],

                    'buyer_name' =>
                        $form['buyer_name'],

                    'buyer_tax_id_type' =>
                        $form[
                            'buyer_tax_id_type'
                        ],

                    'buyer_tax_id' =>
                        $form[
                            'buyer_tax_id'
                        ],

                    'buyer_street' =>
                        $form[
                            'buyer_street'
                        ],

                    'buyer_postal_code' =>
                        $form[
                            'buyer_postal_code'
                        ],

                    'buyer_city' =>
                        $form[
                            'buyer_city'
                        ],

                    'buyer_country' =>
                        $form[
                            'buyer_country'
                        ],

                    'buyer_email' =>
                        $form[
                            'buyer_email'
                        ],

                    'tax_exemption_basis' =>
                        $form[
                            'tax_exemption_basis'
                        ],

                    'notes' =>
                        $form['notes'],
                ],
                [
                    [
                        'name' =>
                            'wynajem domku wczasowego – '
                            . $quantity
                            . ' nocy',

                        'quantity' =>
                            1,

                        'unit' =>
                            'usł.',

                        'unit_net' =>
                            $amounts['net'],

                        'vat_rate_code' =>
                            $form[
                                'vat_rate_code'
                            ],

                        'net_amount' =>
                            $amounts['net'],

                        'vat_amount' =>
                            $amounts['vat'],

                        'gross_amount' =>
                            $amounts['gross'],

                        'sort_order' =>
                            0,
                    ],
                ]
            );

            Response::redirect(
                '/admin/rezerwacje/pokaz?id='
                . $reservationId
                . '&invoice_created=1'
                . '&invoice_id='
                . $invoiceId
            );
        } catch (Throwable $exception) {
            self::renderForm(
                $context,
                $form,
                [],
                'Nie udało się wystawić faktury: '
                    . AppErrorHandler::safeMessage(
                        $exception
                    ),
                true,
                500
            );
        }
    }

    /**
     * @return array{
     *     reservation: array<string, mixed>,
     *     cabin: array<string, mixed>,
     *     seller: array<string, mixed>,
     *     guest: array<string, mixed>|null
     * }
     */
    private static function loadContext(
        int $reservationId
    ): array {
        if (!Database::canAttemptConnection()) {
            throw new RuntimeException(
                'Brak połączenia z bazą danych.'
            );
        }

        $reservation = ReservationRepository::find(
            $reservationId
        );

        if ($reservation === null) {
            throw new RuntimeException(
                'Nie znaleziono rezerwacji.'
            );
        }

        $cabin = CabinRepository::find(
            (int) $reservation['cabin_id']
        );

        if ($cabin === null) {
            throw new RuntimeException(
                'Nie znaleziono domku przypisanego '
                . 'do rezerwacji.'
            );
        }

        $sellerId = (int) (
            $cabin['invoice_seller_id']
            ?? 0
        );

        if ($sellerId < 1) {
            throw new RuntimeException(
                'Ten domek nie ma przypisanego '
                . 'sprzedawcy faktur. '
                . 'Przypisz sprzedawcę w edycji domku.'
            );
        }

        $seller = InvoiceSellerRepository::find(
            $sellerId
        );

        if ($seller === null) {
            throw new RuntimeException(
                'Nie znaleziono przypisanego '
                . 'sprzedawcy faktur.'
            );
        }

        $guest = null;

        $guestId = isset(
            $reservation['guest_id']
        )
            ? (int) $reservation['guest_id']
            : 0;

        if ($guestId > 0) {
            $guest = GuestRepository::find(
                $guestId
            );
        }

        return [
            'reservation' => $reservation,
            'cabin' => $cabin,
            'seller' => $seller,
            'guest' => $guest,
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, string>
     */
    private static function formFromContext(
        array $context
    ): array {
        $reservation =
            $context['reservation'];

        $cabin =
            $context['cabin'];

        $seller =
            $context['seller'];

        $guest = is_array(
            $context['guest'] ?? null
        )
            ? $context['guest']
            : null;

        $address = self::parseGuestAddress(
            $guest
        );

        $buyerName = trim(
            (string) (
                $reservation['guest_name']
                ?? ''
            )
        );

        $buyerEmail = trim(
            (string) (
                $reservation['email']
                ?? ''
            )
        );

        if ($guest !== null) {
            $guestName = trim(
                (string) (
                    $guest['first_name']
                    ?? ''
                )
                . ' '
                . (string) (
                    $guest['last_name']
                    ?? ''
                )
            );

            if ($guestName !== '') {
                $buyerName = $guestName;
            }

            $guestEmail = trim(
                (string) (
                    $guest['email']
                    ?? ''
                )
            );

            if ($guestEmail !== '') {
                $buyerEmail = $guestEmail;
            }
        }

        $today = date('Y-m-d');

        $paymentStatus = strtoupper(
            (string) (
                $reservation[
                    'payment_status'
                ]
                ?? 'UNPAID'
            )
        );

        if (
            !in_array(
                $paymentStatus,
                [
                    'UNPAID',
                    'PARTIALLY_PAID',
                    'PAID',
                ],
                true
            )
        ) {
            $paymentStatus = 'UNPAID';
        }

        return [
            'series' =>
                (string) (
                    $seller['invoice_series']
                    ?? 'FV'
                ),

            'issue_date' =>
                $today,

            'sale_date' =>
                (string) (
                    $reservation['end_date']
                    ?? $today
                ),

            'due_date' =>
                $today,

            'buyer_type' =>
                'PERSON',

            'buyer_name' =>
                $buyerName,

            'buyer_tax_id_type' =>
                'NONE',

            'buyer_tax_id' =>
                '',

            'buyer_street' =>
                $address['street'],

            'buyer_postal_code' =>
                $address['postal_code'],

            'buyer_city' =>
                $address['city'],

            'buyer_country' =>
                $address['country'],

            'buyer_email' =>
                $buyerEmail,

            'item_name' =>
                'wynajem domku wczasowego',


            'gross_amount' =>
                self::normalizeMoneyForForm(
                    $reservation[
                        'total_price'
                    ]
                    ?? '0'
                ),

            'vat_rate_code' =>
                '8',

            'tax_exemption_basis' =>
                '',

            'payment_method' =>
                '',

            'payment_status' =>
                $paymentStatus,

            'notes' =>
                (string) (
                    $reservation['notes']
                    ?? ''
                ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function formFromPost(): array
    {
        $keys = [
            'series',
            'issue_date',
            'sale_date',
            'due_date',
            'buyer_type',
            'buyer_name',
            'buyer_tax_id_type',
            'buyer_tax_id',
            'buyer_street',
            'buyer_postal_code',
            'buyer_city',
            'buyer_country',
            'buyer_email',
            'item_name',
            'gross_amount',
            'vat_rate_code',
            'tax_exemption_basis',
            'payment_method',
            'payment_status',
            'notes',
        ];

        $form = [];

        foreach ($keys as $key) {
            $value = $_POST[$key] ?? '';

            $form[$key] =
                is_string($value)
                    ? trim($value)
                    : '';
        }

        $form['series'] = strtoupper(
            $form['series']
        );

        $form['buyer_type'] = strtoupper(
            $form['buyer_type']
        );

        $form['buyer_tax_id_type'] =
            strtoupper(
                $form['buyer_tax_id_type']
            );

        $form['vat_rate_code'] =
            strtoupper(
                $form['vat_rate_code']
            );

        $form['payment_status'] =
            strtoupper(
                $form['payment_status']
            );

        $form['item_name'] =
            'wynajem domku wczasowego';

        return $form;
    }

    /**
     * @param array<string, string> $form
     *
     * @return array<string, string>
     */
    private static function validateForm(
        array $form
    ): array {
        $errors = [];

        if (
            preg_match(
                '/^[A-Z0-9_-]{1,40}$/',
                $form['series']
            ) !== 1
        ) {
            $errors['series'] =
                'Podaj prawidłową serię faktury.';
        }

        foreach (
            [
                'issue_date' =>
                    'Data wystawienia',
                'sale_date' =>
                    'Data sprzedaży',
            ]
            as $field => $label
        ) {
            if (
                !self::isValidDate(
                    $form[$field]
                )
            ) {
                $errors[$field] =
                    $label
                    . ' jest nieprawidłowa.';
            }
        }

        if (
            $form['due_date'] !== ''
            && !self::isValidDate(
                $form['due_date']
            )
        ) {
            $errors['due_date'] =
                'Termin płatności jest nieprawidłowy.';
        }

        if (
            !in_array(
                $form['buyer_type'],
                [
                    'PERSON',
                    'COMPANY',
                ],
                true
            )
        ) {
            $errors['buyer_type'] =
                'Wybierz typ nabywcy.';
        }

        if ($form['buyer_name'] === '') {
            $errors['buyer_name'] =
                'Podaj nazwę nabywcy.';
        }

        if (
            $form['buyer_type'] === 'COMPANY'
            && $form['buyer_tax_id'] === ''
        ) {
            $errors['buyer_tax_id'] =
                'Podaj identyfikator podatkowy firmy.';
        }

        if (
            $form['buyer_email'] !== ''
            && filter_var(
                $form['buyer_email'],
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            $errors['buyer_email'] =
                'Podaj prawidłowy adres e-mail.';
        }

        if ($form['item_name'] === '') {
            $errors['item_name'] =
                'Podaj nazwę usługi.';
        }

        $gross = self::money(
            $form['gross_amount']
        );

        if ($gross <= 0) {
            $errors['gross_amount'] =
                'Kwota faktury musi być większa od zera.';
        }

        if (
            !in_array(
                $form['vat_rate_code'],
                [
                    '23',
                    '8',
                    '5',
                    '0',
                    'ZW',
                    'NP',
                ],
                true
            )
        ) {
            $errors['vat_rate_code'] =
                'Wybierz sposób rozliczenia VAT.';
        }

        if (
            in_array(
                $form['vat_rate_code'],
                [
                    'ZW',
                    'NP',
                ],
                true
            )
            && $form[
                'tax_exemption_basis'
            ] === ''
        ) {
            $errors[
                'tax_exemption_basis'
            ] =
                'Podaj podstawę lub informację '
                . 'dotyczącą zastosowanego oznaczenia.';
        }

        if (
            !in_array(
                $form['payment_status'],
                [
                    'UNPAID',
                    'PARTIALLY_PAID',
                    'PAID',
                ],
                true
            )
        ) {
            $errors['payment_status'] =
                'Wybierz status płatności.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed>|null $guest
     *
     * @return array{
     *     street: string,
     *     postal_code: string,
     *     city: string,
     *     country: string
     * }
     */
    private static function parseGuestAddress(
        ?array $guest
    ): array {
        if ($guest === null) {
            return [
                'street' => '',
                'postal_code' => '',
                'city' => '',
                'country' => 'Polska',
            ];
        }

        $fullAddress = trim(
            (string) (
                $guest['full_address']
                ?? ''
            )
        );

        $city = trim(
            (string) (
                $guest['city']
                ?? ''
            )
        );

        $country = trim(
            (string) (
                $guest['country']
                ?? ''
            )
        );

        if ($country === '') {
            $country = 'Polska';
        }

        $street = $fullAddress;
        $postalCode = '';

        if (
            $fullAddress !== ''
            && preg_match(
                '/^(.*?)\s*,?\s*(\d{2}-\d{3})\s+(.+)$/u',
                $fullAddress,
                $matches
            ) === 1
        ) {
            $street = trim(
                (string) (
                    $matches[1]
                    ?? ''
                ),
                " \t\n\r\0\x0B,"
            );

            $postalCode = trim(
                (string) (
                    $matches[2]
                    ?? ''
                )
            );

            $parsedCity = trim(
                (string) (
                    $matches[3]
                    ?? ''
                ),
                " \t\n\r\0\x0B,"
            );

            if ($parsedCity !== '') {
                $city = $parsedCity;
            }
        }

        return [
            'street' =>
                $street,

            'postal_code' =>
                $postalCode,

            'city' =>
                $city,

            'country' =>
                $country,
        ];
    }

    /**
     * @return array{
     *     net: string,
     *     vat: string,
     *     gross: string
     * }
     */
    private static function calculateAmounts(
        string $grossValue,
        string $vatRateCode
    ): array {
        $gross = self::money(
            $grossValue
        );

        if (
            in_array(
                $vatRateCode,
                [
                    '23',
                    '8',
                    '5',
                ],
                true
            )
        ) {
            $rate =
                (float) $vatRateCode;

            $net =
                $gross
                / (
                    1
                    + $rate / 100
                );

            $vat =
                $gross - $net;
        } else {
            $net = $gross;
            $vat = 0.0;
        }

        return [
            'net' =>
                number_format(
                    $net,
                    2,
                    '.',
                    ''
                ),

            'vat' =>
                number_format(
                    $vat,
                    2,
                    '.',
                    ''
                ),

            'gross' =>
                number_format(
                    $gross,
                    2,
                    '.',
                    ''
                ),
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, string> $form
     * @param array<string, string> $errors
     */
    private static function renderForm(
        array $context,
        array $form,
        array $errors,
        ?string $databaseMessage,
        bool $canSave,
        int $statusCode = 200
    ): void {
        Response::html(
            View::render(
                'pages/admin_invoice_new',
                [
                    'title' =>
                        'Wystaw fakturę',

                    'reservation' =>
                        $context[
                            'reservation'
                        ],

                    'cabin' =>
                        $context['cabin'],

                    'seller' =>
                        $context['seller'],

                    'form' =>
                        $form,

                    'errors' =>
                        $errors,

                    'databaseMessage' =>
                        $databaseMessage,

                    'canSave' =>
                        $canSave,
                ]
            ),
            $statusCode
        );
    }

    private static function renderError(
        string $title,
        string $message,
        int $statusCode
    ): void {
        Response::html(
            View::render(
                'pages/error',
                [
                    'title' =>
                        $title,

                    'message' =>
                        $message,
                ]
            ),
            $statusCode
        );
    }

    private static function reservationIdFromQuery(): ?int
    {
        $id = filter_var(
            $_GET['reservation_id']
            ?? null,
            FILTER_VALIDATE_INT
        );

        return is_int($id)
            && $id > 0
                ? $id
                : null;
    }

    private static function isValidDate(
        string $value
    ): bool {
        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );

        return $date !== false
            && $date->format('Y-m-d')
                === $value;
    }

    private static function money(
        mixed $value
    ): float {
        return round(
            (float) str_replace(
                ',',
                '.',
                (string) $value
            ),
            2
        );
    }

    private static function normalizeMoneyForForm(
        mixed $value
    ): string {
        return number_format(
            self::money($value),
            2,
            '.',
            ''
        );
    }
}