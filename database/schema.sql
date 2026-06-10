-- Mobile Library API — database schema (MySQL 8, 3rd Normal Form)
--
-- Charset/collation: utf8mb4 for full Unicode (book text, logins).
-- All foreign keys are indexed; cascade rules keep the graph consistent.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- users: authentication data
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    login         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_login (login)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- auth_tokens: opaque bearer tokens issued at login/registration
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_tokens (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    BIGINT UNSIGNED NOT NULL,
    token      CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_auth_tokens_token (token),
    KEY idx_auth_tokens_user (user_id),
    CONSTRAINT fk_auth_tokens_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- books: personal library entries (soft-deletable via deleted_at)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS books (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_id   BIGINT UNSIGNED NOT NULL,
    title      VARCHAR(255) NOT NULL,
    content    LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_books_owner (owner_id),
    KEY idx_books_deleted_at (deleted_at),
    CONSTRAINT fk_books_owner
        FOREIGN KEY (owner_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- library_access: owner_id grants grantee_id read access to their library
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS library_access (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_id   BIGINT UNSIGNED NOT NULL,
    grantee_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_library_access_pair (owner_id, grantee_id),
    KEY idx_library_access_grantee (grantee_id),
    CONSTRAINT fk_library_access_owner
        FOREIGN KEY (owner_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_library_access_grantee
        FOREIGN KEY (grantee_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
