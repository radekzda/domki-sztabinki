INSERT INTO cabins (
    name,
    short_name,
    description,
    max_guests,
    bedrooms,
    bathrooms,
    price_per_night,
    price_one_night,
    price_two_nights,
    price_three_nights,
    price_four_nights,
    price_five_nights,
    price_six_nights,
    price_seven_plus_nights,
    is_active,
    sort_order
)
SELECT
    'Domek nr 1',
    'D1',
    'Komfortowy domek całoroczny nad jeziorem. Salon z aneksem kuchennym, dwie sypialnie, łazienka, taras oraz dostęp do wyposażenia rekreacyjnego.',
    6,
    2,
    1,
    440,
    800,
    440,
    430,
    420,
    410,
    400,
    350,
    1,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM cabins WHERE name = 'Domek nr 1'
);

INSERT INTO cabins (
    name,
    short_name,
    description,
    max_guests,
    bedrooms,
    bathrooms,
    price_per_night,
    price_one_night,
    price_two_nights,
    price_three_nights,
    price_four_nights,
    price_five_nights,
    price_six_nights,
    price_seven_plus_nights,
    is_active,
    sort_order
)
SELECT
    'Domek nr 2',
    'D2',
    'Komfortowy domek całoroczny nad jeziorem. Salon z aneksem kuchennym, dwie sypialnie, łazienka, taras oraz dostęp do wyposażenia rekreacyjnego.',
    6,
    2,
    1,
    440,
    800,
    440,
    430,
    420,
    410,
    400,
    350,
    1,
    2
WHERE NOT EXISTS (
    SELECT 1 FROM cabins WHERE name = 'Domek nr 2'
);