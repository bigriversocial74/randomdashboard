-- Gruber Procurement Intelligence
-- Section 12: Scenario Planning & Procurement Risk Simulation
-- Apply after Version 3 and Section 11 migrations.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS procurement_scenarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scenario_number VARCHAR(40) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    scenario_type VARCHAR(40) NOT NULL,
    supplier_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    inputs_json LONGTEXT NOT NULL,
    result_json LONGTEXT NOT NULL,
    risk_level VARCHAR(20) NOT NULL DEFAULT 'medium',
    decision_status VARCHAR(32) NOT NULL DEFAULT 'draft',
    owner_id BIGINT UNSIGNED NOT NULL,
    approval_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_scenarios_company_status (company_id, decision_status),
    KEY idx_scenarios_supplier (supplier_id),
    KEY idx_scenarios_category (category_id),
    KEY idx_scenarios_risk (risk_level),
    KEY idx_scenarios_approval (approval_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version, description, checksum_sha256)
VALUES ('4.1-section12', 'Procurement scenario planning and risk simulation records', NULL)
ON DUPLICATE KEY UPDATE description = VALUES(description);
