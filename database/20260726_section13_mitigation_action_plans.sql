-- Gruber Procurement Intelligence
-- Section 13: Mitigation Action Plans & Supplier Contingency Management
-- Apply after Version 3, Section 11, and Section 12 migrations.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS procurement_mitigation_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_number VARCHAR(40) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL,
    scenario_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    trigger_type VARCHAR(40) NOT NULL DEFAULT 'risk_score',
    trigger_operator VARCHAR(4) NOT NULL DEFAULT '>=',
    trigger_value DECIMAL(18,4) NOT NULL DEFAULT 0,
    source_risk_score DECIMAL(8,2) NOT NULL DEFAULT 0,
    source_net_impact DECIMAL(18,2) NOT NULL DEFAULT 0,
    risk_level VARCHAR(20) NOT NULL DEFAULT 'medium',
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    owner_id BIGINT UNSIGNED NOT NULL,
    approval_id BIGINT UNSIGNED NULL,
    summary TEXT NOT NULL,
    activation_notes TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mitigation_plan_company_status (company_id, status),
    KEY idx_mitigation_plan_scenario (scenario_id),
    KEY idx_mitigation_plan_supplier (supplier_id),
    KEY idx_mitigation_plan_category (category_id),
    KEY idx_mitigation_plan_risk (risk_level),
    KEY idx_mitigation_plan_owner (owner_id),
    KEY idx_mitigation_plan_approval (approval_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_mitigation_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    sequence_no INT UNSIGNED NOT NULL DEFAULT 1,
    action_type VARCHAR(40) NOT NULL,
    title VARCHAR(190) NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    priority VARCHAR(20) NOT NULL DEFAULT 'medium',
    status VARCHAR(32) NOT NULL DEFAULT 'planned',
    due_date DATE NOT NULL,
    estimated_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
    recovery_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    service_risk_reduction DECIMAL(8,2) NOT NULL DEFAULT 0,
    readiness_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
    supplier_id BIGINT UNSIGNED NULL,
    notes TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mitigation_action_plan_sequence (plan_id, sequence_no),
    KEY idx_mitigation_action_owner_status (owner_id, status),
    KEY idx_mitigation_action_due (due_date, status),
    KEY idx_mitigation_action_supplier (supplier_id),
    KEY idx_mitigation_action_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version, description, checksum_sha256)
VALUES ('4.2-section13', 'Mitigation action plans and supplier contingency management', NULL)
ON DUPLICATE KEY UPDATE description = VALUES(description);
