<?php

declare(strict_types=1);

final class SettingsRepository
{
    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'property_name' => 'Domki Sztabinki',
            'contact_email' => 'radekzdancewicz@gmail.com',
            'contact_phone' => '',
            'address_line' => 'Sztabinki',
            'postal_code' => '',
            'city' => 'Sejny',
            'country' => 'Polska',
            'check_in_time' => '15:00',
            'check_out_time' => '11:00',
            'minimum_nights' => '4',
            'currency' => 'PLN',
            'price_one_night' => '800',
            'price_two_nights' => '440',
            'price_three_nights' => '430',
            'price_four_nights' => '420',
            'price_five_nights' => '410',
            'price_six_nights' => '400',
            'price_seven_plus_nights' => '350',
            'fishing_price' => '30',
            'hot_tub_price' => '200',
            'deposit_amount' => '0',
            'bank_account_holder' => '',
            'bank_account_number' => '',
            'public_short_description' => 'Domki letniskowe nad jeziorem w spokojnej okolicy.',
            'booking_rules' => 'Obiekt przeznaczony jest do spokojnego wypoczynku. Nie organizujemy głośnych imprez.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        self::ensureTable();

        $settings = self::defaults();
        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT setting_key, setting_value
            FROM app_settings
            ORDER BY setting_key ASC'
        );

        if ($statement === false) {
            return $settings;
        }

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return $settings;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $key = isset($row['setting_key']) ? (string) $row['setting_key'] : '';
            $value = isset($row['setting_value']) ? (string) $row['setting_value'] : '';

            if ($key !== '' && array_key_exists($key, $settings)) {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }

    /**
     * @param array<string, string> $settings
     */
    public static function save(array $settings): void
    {
        self::ensureTable();

        $allowedKeys = array_keys(self::defaults());
        $connection = Database::connection();

        $connection->beginTransaction();

        try {
            $statement = $connection->prepare(
                'INSERT INTO app_settings (setting_key, setting_value)
                VALUES (:setting_key, :setting_value)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );

            foreach ($allowedKeys as $key) {
                $statement->execute([
                    'setting_key' => $key,
                    'setting_value' => $settings[$key] ?? '',
                ]);
            }

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    public static function ensureTable(): void
    {
        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(100) NOT NULL,
                setting_value TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}