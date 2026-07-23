# Import CSV — Domki Sztabinki PMS

Wszystkie importy używają:

- kodowania UTF-8,
- separatora `;`,
- pierwszego wiersza z nazwami kolumn,
- neutralnych danych bez identyfikatorów Base44.

## 1. Import domków

Wymagane:

```text
short_name;name
```

Opcjonalne:

```text
description
max_guests
area_sqm
bedrooms
bathrooms
price_per_night
price_one_night
price_two_nights
price_three_nights
price_four_nights
price_five_nights
price_six_nights
price_seven_plus_nights
amenities
location
cabin_type
status
pets_allowed
has_parking
has_kitchen
sort_order
```

Dopasowanie istniejącego domku:

1. `short_name`,
2. `name`.

Przykład:

```csv
short_name;name;description;max_guests;area_sqm;bedrooms;bathrooms;price_per_night;price_one_night;price_two_nights;price_three_nights;price_four_nights;price_five_nights;price_six_nights;price_seven_plus_nights;amenities;location;cabin_type;status;pets_allowed;has_parking;has_kitchen;sort_order
D5;Domek 5;Domek nad jeziorem;6;48;2;1;450;800;450;440;430;420;410;350;Wi-Fi, grill, kajak;Sztabinki;Domek;aktywny;0;1;1;5
```

## 2. Import gości

Wymagane:

```text
first_name;last_name;email
```

Opcjonalne:

```text
phone
street
postal_code
city
country
full_address
pesel
document_number
birth_date
notes
source
preferred_contact
preferences
important_notes
```

Dopasowanie istniejącego gościa:

1. e-mail,
2. numer telefonu po usunięciu spacji i znaków formatowania.

Źródło istniejącego gościa nie jest nadpisywane.

Dozwolone źródła:

```text
MANUAL
DIRECT
WWW
BOOKING
PHONE
AIRBNB
ICAL_OTHER
```

Przykład:

```csv
first_name;last_name;email;phone;street;postal_code;city;country;full_address;pesel;document_number;birth_date;notes;source;preferred_contact;preferences;important_notes
Jan;Kowalski;jan@example.com;+48123123123;Leśna 10;16-500;Sejny;Polska;;80010112345;ABC123456;1980-01-01;Stały gość;DIRECT;PHONE;;
```

## 3. Import rezerwacji

Wymagane:

```text
cabin;first_name;last_name;email;check_in;check_out
```

Opcjonalne:

```text
phone
adults
children
total_price
paid_amount
status
payment_status
source
check_in_time
check_out_time
street
postal_code
city
country
notes
```

`cabin` może zawierać:

- skrót domku, np. `D5`,
- pełną nazwę domku.

Gość jest dopasowywany:

1. po e-mailu,
2. po telefonie.

Jeżeli gościa nie ma, importer tworzy go z prawdziwym adresem e-mail z CSV.

Ponowny import rozpoznaje tę samą rezerwację po:

- domku,
- powiązanym gościu lub jego e-mailu,
- dacie przyjazdu,
- dacie wyjazdu.

Jeżeli `total_price` jest puste, cena jest wyliczana z aktualnego cennika domku.

Status płatności jest ustalany na podstawie `paid_amount` i `total_price`.

Przykład:

```csv
cabin;first_name;last_name;email;phone;check_in;check_out;adults;children;total_price;paid_amount;status;payment_status;source;check_in_time;check_out_time;street;postal_code;city;country;notes
D5;Jan;Kowalski;jan@example.com;+48123123123;2026-08-01;2026-08-06;2;1;2400;500;CONFIRMED;PARTIAL;BOOKING;15:00;11:00;Leśna 10;16-500;Sejny;Polska;Import z Booking
```

