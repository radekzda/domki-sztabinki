CREATE TABLE IF NOT EXISTS cabins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    external_id VARCHAR(80) NULL,
    name VARCHAR(160) NOT NULL,
    short_name VARCHAR(80) NULL,
    description TEXT NOT NULL,
    amenities TEXT NULL,
    location VARCHAR(120) NULL,
    cabin_type VARCHAR(80) NULL,
    pets_allowed TINYINT(1) NOT NULL DEFAULT 0,
    has_parking TINYINT(1) NOT NULL DEFAULT 0,
    has_kitchen TINYINT(1) NOT NULL DEFAULT 0,
    max_guests INT UNSIGNED NOT NULL DEFAULT 6,
    area_sqm INT NULL,
    bedrooms INT UNSIGNED NOT NULL DEFAULT 2,
    bathrooms INT UNSIGNED NOT NULL DEFAULT 1,
    price_per_night INT UNSIGNED NOT NULL DEFAULT 440,
    price_one_night INT UNSIGNED NOT NULL DEFAULT 800,
    price_two_nights INT UNSIGNED NOT NULL DEFAULT 440,
    price_three_nights INT UNSIGNED NOT NULL DEFAULT 430,
    price_four_nights INT UNSIGNED NOT NULL DEFAULT 420,
    price_five_nights INT UNSIGNED NOT NULL DEFAULT 410,
    price_six_nights INT UNSIGNED NOT NULL DEFAULT 400,
    price_seven_plus_nights INT UNSIGNED NOT NULL DEFAULT 350,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    main_image_url VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_cabins_external_id (external_id),
    INDEX cabins_is_active_index (is_active),
    INDEX cabins_sort_order_index (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cabin_images (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cabin_id INT UNSIGNED NOT NULL,
    url VARCHAR(255) NOT NULL,
    alt VARCHAR(255) NULL,
    is_main TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX cabin_images_cabin_id_index (cabin_id),
    INDEX cabin_images_sort_order_index (sort_order),
    CONSTRAINT cabin_images_cabin_id_foreign
        FOREIGN KEY (cabin_id) REFERENCES cabins(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    external_id VARCHAR(80) NULL,
    first_name VARCHAR(120) NOT NULL,
    last_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(60) NULL,
    country VARCHAR(120) NULL,
    street VARCHAR(190) NULL,
    postal_code VARCHAR(40) NULL,
    city VARCHAR(120) NULL,
    full_address VARCHAR(255) NULL,
    pesel VARCHAR(30) NULL,
    document_number VARCHAR(80) NULL,
    nationality VARCHAR(120) NULL,
    birth_date DATE NULL,
    is_vip TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    source VARCHAR(60) NOT NULL DEFAULT 'MANUAL',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_guests_external_id (external_id),
    INDEX guests_email_index (email),
    INDEX guests_phone_index (phone),
    INDEX guests_source_index (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    external_id VARCHAR(80) NULL,
    cabin_id INT UNSIGNED NOT NULL,
    guest_id INT UNSIGNED NULL,
    guest_name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(60) NULL,
    first_name VARCHAR(120) NULL,
    last_name VARCHAR(120) NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    check_in_at DATETIME NULL,
    check_out_at DATETIME NULL,
    nights INT UNSIGNED NOT NULL DEFAULT 1,
    price_per_night DECIMAL(10, 2) NULL,
    guests INT UNSIGNED NOT NULL DEFAULT 1,
    adults INT UNSIGNED NOT NULL DEFAULT 1,
    children INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'PENDING',
    source VARCHAR(40) NOT NULL DEFAULT 'MANUAL',
    payment_status VARCHAR(40) NULL,
    total_price DECIMAL(10, 2) NULL,
    paid_amount DECIMAL(10, 2) NULL,
    street VARCHAR(190) NULL,
    postal_code VARCHAR(40) NULL,
    city VARCHAR(120) NULL,
    country VARCHAR(120) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_reservations_external_id (external_id),
    INDEX reservations_cabin_id_index (cabin_id),
    INDEX reservations_guest_id_index (guest_id),
    INDEX reservations_status_index (status),
    INDEX reservations_source_index (source),
    INDEX reservations_payment_status_index (payment_status),
    INDEX reservations_start_date_index (start_date),
    INDEX reservations_end_date_index (end_date),
    CONSTRAINT reservations_cabin_id_foreign
        FOREIGN KEY (cabin_id) REFERENCES cabins(id)
        ON DELETE RESTRICT,
    CONSTRAINT reservations_guest_id_foreign
        FOREIGN KEY (guest_id) REFERENCES guests(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inquiries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(190) NOT NULL,
    first_name VARCHAR(120) NULL,
    last_name VARCHAR(120) NULL,
    phone VARCHAR(60) NOT NULL,
    email VARCHAR(190) NULL,
    cabin_id INT UNSIGNED NULL,
    cabin_name VARCHAR(160) NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    guests INT UNSIGNED NOT NULL DEFAULT 1,
    adults INT UNSIGNED NOT NULL DEFAULT 1,
    children INT UNSIGNED NOT NULL DEFAULT 0,
    street VARCHAR(190) NULL,
    postal_code VARCHAR(40) NULL,
    city VARCHAR(120) NULL,
    country VARCHAR(120) NULL,
    notes TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'NEW',
    source VARCHAR(40) NOT NULL DEFAULT 'WWW',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX inquiries_cabin_id_index (cabin_id),
    INDEX inquiries_status_index (status),
    INDEX inquiries_created_at_index (created_at),
    CONSTRAINT inquiries_cabin_id_foreign
        FOREIGN KEY (cabin_id) REFERENCES cabins(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS response_templates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX response_templates_is_active_index (is_active),
    INDEX response_templates_sort_order_index (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'ADMIN',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY admin_users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS site_images (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    image_type VARCHAR(50) NOT NULL DEFAULT 'GALLERY',
    sort_order INT NOT NULL DEFAULT 0,
    is_main TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX site_images_image_type_idx (image_type),
    INDEX site_images_is_main_idx (is_main),
    INDEX site_images_sort_order_idx (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_name VARCHAR(190) NOT NULL DEFAULT 'Domki Sztabinki',
    owner_name VARCHAR(190) NULL,
    owner_email VARCHAR(190) NULL,
    owner_phone VARCHAR(60) NULL,
    contact_email VARCHAR(190) NULL,
    contact_phone VARCHAR(60) NULL,
    property_street VARCHAR(190) NULL,
    property_postal_code VARCHAR(40) NULL,
    property_city VARCHAR(120) NULL,
    property_country VARCHAR(120) NOT NULL DEFAULT 'Polska',
    check_in_time VARCHAR(20) NOT NULL DEFAULT '15:00',
    check_out_time VARCHAR(20) NOT NULL DEFAULT '11:00',
    minimum_nights INT UNSIGNED NOT NULL DEFAULT 4,
    season_start_month INT UNSIGNED NOT NULL DEFAULT 5,
    season_end_month INT UNSIGNED NOT NULL DEFAULT 9,
    website_url VARCHAR(190) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;