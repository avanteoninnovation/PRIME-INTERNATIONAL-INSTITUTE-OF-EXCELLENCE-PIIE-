-- Website Management schema for manual import via phpMyAdmin

CREATE TABLE IF NOT EXISTS website_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_key VARCHAR(191) NOT NULL UNIQUE,
    title VARCHAR(191) NOT NULL,
    slug VARCHAR(191) NULL,
    status TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS website_sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_key VARCHAR(191) NOT NULL,
    section_key VARCHAR(191) NOT NULL,
    title VARCHAR(191) NULL,
    subtitle VARCHAR(191) NULL,
    content LONGTEXT NULL,
    extra_json LONGTEXT NULL,
    image VARCHAR(191) NULL,
    status TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX website_sections_page_key_idx (page_key),
    INDEX website_sections_section_key_idx (section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS website_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(191) NOT NULL,
    item_type VARCHAR(191) NOT NULL DEFAULT 'general',
    title VARCHAR(191) NULL,
    subtitle VARCHAR(191) NULL,
    description TEXT NULL,
    content LONGTEXT NULL,
    image VARCHAR(191) NULL,
    link VARCHAR(191) NULL,
    button_text VARCHAR(191) NULL,
    status TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    meta_json LONGTEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX website_items_section_key_idx (section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS website_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(191) NOT NULL UNIQUE,
    `value` LONGTEXT NULL,
    is_json TINYINT NOT NULL DEFAULT 0,
    status TINYINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS website_seo_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_key VARCHAR(191) NOT NULL UNIQUE,
    meta_title VARCHAR(191) NULL,
    meta_description TEXT NULL,
    meta_keywords TEXT NULL,
    canonical_url VARCHAR(191) NULL,
    status TINYINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
