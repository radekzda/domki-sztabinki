<?php

declare(strict_types=1);

return [
    'app_name' => Env::get('APP_NAME', 'Domki Sztabinki PMS'),
    'environment' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'base_url' => Env::get('APP_URL', ''),
    'timezone' => Env::get('APP_TIMEZONE', 'Europe/Warsaw'),
];