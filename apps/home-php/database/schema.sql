CREATE TABLE IF NOT EXISTS invoice_sellers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    name VARCHAR(190) NOT NULL,

    tax_id_type VARCHAR(20) NOT NULL DEFAULT 'NIP',
    tax_id VARCHAR(40) NULL,

    street VARCHAR(190) NULL,
    postal_code VARCHAR(40) NULL,
    city VARCHAR(120) NULL,
    country VARCHAR(120) NOT NULL DEFAULT 'Polska',

    email VARCHAR(190) NULL,
    phone VARCHAR(60) NULL,

    bank_account_holder VARCHAR(190) NULL,
    bank_account_number VARCHAR(80) NULL,

    invoice_series VARCHAR(40) NOT NULL DEFAULT 'FV',

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX invoice_sellers_active_index (
        is_active
    )
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cabins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_seller_id INT UNSIGNED NULL,
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
    cleaning_status VARCHAR(30) NOT NULL DEFAULT 'READY',
    cleaning_updated_at DATETIME NULL,
    main_image_url VARCHAR(255) NULL,
    ical_url TEXT NULL,
    ical_enabled TINYINT(1) NOT NULL DEFAULT 0,
    ical_source VARCHAR(40) NOT NULL DEFAULT 'BOOKING',
    ical_last_sync_at DATETIME NULL,
    ical_last_sync_status VARCHAR(40) NULL,
    ical_export_token VARCHAR(64) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX cabins_is_active_index (is_active),
    INDEX cabins_sort_order_index (sort_order),
    INDEX cabins_invoice_seller_index (
        invoice_seller_id
    ),
    CONSTRAINT cabins_invoice_seller_foreign
        FOREIGN KEY (invoice_seller_id)
        REFERENCES invoice_sellers(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cabin_images (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cabin_id INT UNSIGNED NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
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
    preferred_contact VARCHAR(30) NULL,
    preferences TEXT NULL,
    important_notes TEXT NULL,
    source VARCHAR(60) NOT NULL DEFAULT 'MANUAL',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX guests_email_index (email),
    INDEX guests_phone_index (phone),
    INDEX guests_source_index (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
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

CREATE TABLE IF NOT EXISTS reservation_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    details TEXT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    amount DECIMAL(10, 2) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reservation_history_reservation_id (
        reservation_id
    ),
    KEY idx_reservation_history_created_at (
        created_at
    ),
    KEY idx_reservation_history_event_type (
        event_type
    )
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_sequences (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    seller_id INT UNSIGNED NOT NULL,

    series VARCHAR(40) NOT NULL DEFAULT 'FV',

    sequence_year SMALLINT UNSIGNED NOT NULL,
    sequence_month TINYINT UNSIGNED NOT NULL,

    last_number INT UNSIGNED NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY invoice_sequences_seller_period_unique (
        seller_id,
        series,
        sequence_year,
        sequence_month
    ),

    INDEX invoice_sequences_seller_index (
        seller_id
    ),

    CONSTRAINT invoice_sequences_seller_foreign
        FOREIGN KEY (seller_id)
        REFERENCES invoice_sellers(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    reservation_id INT UNSIGNED NULL,
    seller_id INT UNSIGNED NOT NULL,

    invoice_number VARCHAR(100) NOT NULL,

    series VARCHAR(40) NOT NULL DEFAULT 'FV',

    sequence_year SMALLINT UNSIGNED NOT NULL,
    sequence_month TINYINT UNSIGNED NOT NULL,
    sequence_number INT UNSIGNED NOT NULL,

    issue_date DATE NOT NULL,
    sale_date DATE NOT NULL,
    due_date DATE NULL,

    status VARCHAR(30) NOT NULL DEFAULT 'DRAFT',

    currency CHAR(3) NOT NULL DEFAULT 'PLN',

    payment_method VARCHAR(30) NULL,
    payment_status VARCHAR(30)
        NOT NULL DEFAULT 'UNPAID',
    paid_amount DECIMAL(12, 2)
        NOT NULL DEFAULT 0.00,

    seller_name VARCHAR(190) NOT NULL,
    seller_tax_id_type VARCHAR(20)
        NOT NULL DEFAULT 'NIP',
    seller_tax_id VARCHAR(40) NULL,

    seller_street VARCHAR(190) NULL,
    seller_postal_code VARCHAR(40) NULL,
    seller_city VARCHAR(120) NULL,
    seller_country VARCHAR(120)
        NOT NULL DEFAULT 'Polska',

    seller_email VARCHAR(190) NULL,
    seller_phone VARCHAR(60) NULL,

    seller_bank_account_holder VARCHAR(190) NULL,
    seller_bank_account_number VARCHAR(80) NULL,

    buyer_type VARCHAR(20)
        NOT NULL DEFAULT 'PERSON',

    buyer_name VARCHAR(190) NOT NULL,

    buyer_tax_id_type VARCHAR(20)
        NOT NULL DEFAULT 'NONE',
    buyer_tax_id VARCHAR(40) NULL,

    buyer_street VARCHAR(190) NULL,
    buyer_postal_code VARCHAR(40) NULL,
    buyer_city VARCHAR(120) NULL,
    buyer_country VARCHAR(120) NULL,
    buyer_email VARCHAR(190) NULL,

    net_total DECIMAL(12, 2)
        NOT NULL DEFAULT 0.00,
    vat_total DECIMAL(12, 2)
        NOT NULL DEFAULT 0.00,
    gross_total DECIMAL(12, 2)
        NOT NULL DEFAULT 0.00,

    tax_exemption_basis VARCHAR(255) NULL,

    notes TEXT NULL,

    ksef_status VARCHAR(30) NULL,
    ksef_number VARCHAR(120) NULL,
    ksef_sent_at DATETIME NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY invoices_seller_number_unique (
        seller_id,
        invoice_number
    ),

    UNIQUE KEY invoices_sequence_unique (
        seller_id,
        series,
        sequence_year,
        sequence_month,
        sequence_number
    ),

    INDEX invoices_reservation_index (
        reservation_id
    ),

    INDEX invoices_seller_index (
        seller_id
    ),

    INDEX invoices_issue_date_index (
        issue_date
    ),

    INDEX invoices_status_index (
        status
    ),

    INDEX invoices_buyer_tax_id_index (
        buyer_tax_id
    ),

    INDEX invoices_ksef_status_index (
        ksef_status
    ),

    CONSTRAINT invoices_reservation_foreign
        FOREIGN KEY (reservation_id)
        REFERENCES reservations(id)
        ON DELETE SET NULL,

    CONSTRAINT invoices_seller_foreign
        FOREIGN KEY (seller_id)
        REFERENCES invoice_sellers(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id INT UNSIGNED NOT NULL,

    name VARCHAR(255) NOT NULL,

    quantity DECIMAL(12, 3) NOT NULL DEFAULT 1.000,
    unit VARCHAR(30) NOT NULL DEFAULT 'usł.',

    unit_net DECIMAL(12, 2) NOT NULL DEFAULT 0.00,

    vat_rate_code VARCHAR(20) NOT NULL DEFAULT 'NP',

    net_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    vat_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    gross_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,

    sort_order INT NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX invoice_items_invoice_index (
        invoice_id
    ),

    CONSTRAINT invoice_items_invoice_foreign
        FOREIGN KEY (invoice_id)
        REFERENCES invoices(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_reminder_notifications (
    reservation_id INT UNSIGNED NOT NULL,
    reminder_date DATE NOT NULL,
    recipient VARCHAR(190) NOT NULL,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (
        reservation_id,
        reminder_date
    ),
    INDEX invoice_reminder_date_index (
        reminder_date
    ),
    CONSTRAINT invoice_reminder_reservation_foreign
        FOREIGN KEY (reservation_id)
        REFERENCES reservations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ical_sync_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cabin_id INT UNSIGNED NOT NULL,
    source VARCHAR(40) NOT NULL DEFAULT 'BOOKING',
    sync_status VARCHAR(40) NOT NULL,
    total_events INT UNSIGNED NOT NULL DEFAULT 0,
    matched_reservations INT UNSIGNED NOT NULL DEFAULT 0,
    conflicts INT UNSIGNED NOT NULL DEFAULT 0,
    new_blocks INT UNSIGNED NOT NULL DEFAULT 0,
    existing_ical INT UNSIGNED NOT NULL DEFAULT 0,
    deactivated INT UNSIGNED NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX ical_sync_logs_cabin_index (cabin_id),
    INDEX ical_sync_logs_status_index (sync_status),
    INDEX ical_sync_logs_created_at_index (created_at),
    CONSTRAINT ical_sync_logs_cabin_foreign
        FOREIGN KEY (cabin_id)
        REFERENCES cabins(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ical_events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cabin_id INT UNSIGNED NOT NULL,
    matched_reservation_id INT UNSIGNED NULL,
    ical_uid VARCHAR(191) NOT NULL,
    source VARCHAR(40) NOT NULL DEFAULT 'BOOKING',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    summary VARCHAR(255) NULL,
    description TEXT NULL,
    event_status VARCHAR(40) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_seen_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ical_events_cabin_uid_unique (
        cabin_id,
        ical_uid
    ),
    INDEX ical_events_cabin_index (
        cabin_id
    ),
    INDEX ical_events_reservation_index (
        matched_reservation_id
    ),
    INDEX ical_events_source_index (
        source
    ),
    INDEX ical_events_start_date_index (
        start_date
    ),
    INDEX ical_events_end_date_index (
        end_date
    ),
    CONSTRAINT ical_events_cabin_foreign
        FOREIGN KEY (cabin_id)
        REFERENCES cabins(id)
        ON DELETE CASCADE,
    CONSTRAINT ical_events_reservation_foreign
        FOREIGN KEY (matched_reservation_id)
        REFERENCES reservations(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inquiries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(190) NOT NULL,
    first_name VARCHAR(120) NULL,
    last_name VARCHAR(120) NULL,
    phone VARCHAR(60) NOT NULL,
    email VARCHAR(190) NULL,
    cabin_id INT UNSIGNED NULL,
    cabin_name VARCHAR(160) NULL,
    reservation_id INT UNSIGNED NULL,
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
    UNIQUE KEY inquiries_reservation_id_unique (reservation_id),
    INDEX inquiries_status_index (status),
    INDEX inquiries_created_at_index (created_at),
    CONSTRAINT inquiries_cabin_id_foreign
        FOREIGN KEY (cabin_id) REFERENCES cabins(id)
        ON DELETE SET NULL,
    CONSTRAINT inquiries_reservation_id_foreign
        FOREIGN KEY (reservation_id) REFERENCES reservations(id)
        ON DELETE SET NULL
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

CREATE TABLE IF NOT EXISTS message_templates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    template_key VARCHAR(100) NULL,
    template_context VARCHAR(50) NOT NULL DEFAULT 'GENERAL',
    content TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_message_templates_template_key (template_key),
    KEY idx_message_templates_context (template_context),
    KEY idx_message_templates_active (is_active)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
