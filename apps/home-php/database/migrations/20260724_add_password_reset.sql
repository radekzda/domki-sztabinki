SET @has_session_version = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'users'
    AND column_name = 'session_version'
);

SET @add_session_version_sql = IF(
    @has_session_version = 0,
    'ALTER TABLE users ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER is_active',
    'SELECT 1'
);

PREPARE add_session_version_statement
FROM @add_session_version_sql;

EXECUTE add_session_version_statement;

DEALLOCATE PREPARE add_session_version_statement;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    request_ip_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY password_reset_tokens_hash_unique (token_hash),
    INDEX password_reset_tokens_user_index (user_id),
    INDEX password_reset_tokens_expires_index (expires_at),
    CONSTRAINT password_reset_tokens_user_foreign
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
