-- =============================================================================
-- Solar Directory Pipeline Tables
-- =============================================================================
-- Run AFTER solar_setup.sql. These tables track automated pipeline state.
-- Usage: sed 's/{db_prefix}/fl_/g' pipeline_tables.sql | mysql -u USER -p DB_NAME
-- =============================================================================

-- ---------------------------------------------------------------------------
-- Pipeline run tracking — one row per orchestrator execution
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{db_prefix}solar_pipeline_runs` (
    `ID`                      INT AUTO_INCREMENT PRIMARY KEY,
    `run_type`                ENUM('weekly_regional','monthly_full','manual','backfill') NOT NULL,
    `region`                  VARCHAR(100) DEFAULT NULL COMMENT 'Comma-separated state codes, or ALL',
    `status`                  ENUM('running','completed','failed','partial') NOT NULL DEFAULT 'running',
    `started_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at`            DATETIME DEFAULT NULL,
    `records_scraped`         INT NOT NULL DEFAULT 0,
    `records_new`             INT NOT NULL DEFAULT 0,
    `records_updated`         INT NOT NULL DEFAULT 0,
    `records_deactivated`     INT NOT NULL DEFAULT 0,
    `records_errors`          INT NOT NULL DEFAULT 0,
    `outscraper_credits_used` INT NOT NULL DEFAULT 0,
    `error_message`           TEXT DEFAULT NULL,
    `log_path`                VARCHAR(255) DEFAULT NULL,
    INDEX `idx_run_type`      (`run_type`),
    INDEX `idx_status`        (`status`),
    INDEX `idx_started_at`    (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Region rotation schedule — one row per US state + DC (51 total)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{db_prefix}solar_region_schedule` (
    `ID`               INT AUTO_INCREMENT PRIMARY KEY,
    `region_code`      VARCHAR(2) NOT NULL COMMENT 'US state abbreviation',
    `region_name`      VARCHAR(50) NOT NULL COMMENT 'Full state name',
    `last_scraped_at`  DATETIME DEFAULT NULL,
    `last_verified_at` DATETIME DEFAULT NULL,
    `total_listings`   INT NOT NULL DEFAULT 0,
    `priority`         INT NOT NULL DEFAULT 5 COMMENT '1=low, 10=high. High-value states get scraped more often',
    `enabled`          TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `uk_region_code` (`region_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed all 50 states + DC with priorities (CA, TX, FL, NY, AZ = high priority solar markets)
INSERT INTO `{db_prefix}solar_region_schedule` (`region_code`, `region_name`, `priority`) VALUES
('AL', 'Alabama', 4),
('AK', 'Alaska', 2),
('AZ', 'Arizona', 9),
('AR', 'Arkansas', 3),
('CA', 'California', 10),
('CO', 'Colorado', 7),
('CT', 'Connecticut', 5),
('DE', 'Delaware', 3),
('FL', 'Florida', 9),
('GA', 'Georgia', 6),
('HI', 'Hawaii', 7),
('ID', 'Idaho', 4),
('IL', 'Illinois', 6),
('IN', 'Indiana', 4),
('IA', 'Iowa', 3),
('KS', 'Kansas', 3),
('KY', 'Kentucky', 3),
('LA', 'Louisiana', 4),
('ME', 'Maine', 4),
('MD', 'Maryland', 6),
('MA', 'Massachusetts', 7),
('MI', 'Michigan', 5),
('MN', 'Minnesota', 5),
('MS', 'Mississippi', 3),
('MO', 'Missouri', 4),
('MT', 'Montana', 3),
('NE', 'Nebraska', 3),
('NV', 'Nevada', 7),
('NH', 'New Hampshire', 4),
('NJ', 'New Jersey', 7),
('NM', 'New Mexico', 6),
('NY', 'New York', 8),
('NC', 'North Carolina', 6),
('ND', 'North Dakota', 2),
('OH', 'Ohio', 5),
('OK', 'Oklahoma', 3),
('OR', 'Oregon', 6),
('PA', 'Pennsylvania', 6),
('RI', 'Rhode Island', 4),
('SC', 'South Carolina', 5),
('SD', 'South Dakota', 2),
('TN', 'Tennessee', 4),
('TX', 'Texas', 9),
('UT', 'Utah', 7),
('VT', 'Vermont', 4),
('VA', 'Virginia', 6),
('WA', 'Washington', 7),
('WV', 'West Virginia', 2),
('WI', 'Wisconsin', 5),
('WY', 'Wyoming', 2),
('DC', 'District of Columbia', 5);

-- ---------------------------------------------------------------------------
-- Listing source tracking — maps Google Place ID to Flynax listing ID
-- This is the KEY table for incremental updates (no duplicates)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{db_prefix}solar_listing_sources` (
    `ID`                  INT AUTO_INCREMENT PRIMARY KEY,
    `listing_id`          INT NOT NULL COMMENT 'FK to {db_prefix}listings.ID',
    `account_id`          INT NOT NULL DEFAULT 0 COMMENT 'FK to {db_prefix}accounts.ID',
    `google_place_id`     VARCHAR(255) NOT NULL COMMENT 'Stable Google Maps identifier',
    `outscraper_hash`     VARCHAR(64) DEFAULT NULL COMMENT 'Hash of last scraped data for change detection',
    `last_google_rating`  DECIMAL(2,1) DEFAULT NULL,
    `last_review_count`   INT NOT NULL DEFAULT 0,
    `last_scraped_at`     DATETIME DEFAULT NULL,
    `last_verified_at`    DATETIME DEFAULT NULL,
    `business_status`     VARCHAR(30) NOT NULL DEFAULT 'OPERATIONAL',
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_place_id` (`google_place_id`),
    INDEX `idx_listing_id`   (`listing_id`),
    INDEX `idx_account_id`   (`account_id`),
    INDEX `idx_biz_status`   (`business_status`),
    INDEX `idx_last_scraped`  (`last_scraped_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
