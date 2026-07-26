-- Gruber Procurement Intelligence
-- Section 14: Mitigation Execution, Recovery Verification & Procurement Change Control
-- Apply after Version 3 and Sections 11, 12, and 13 migrations.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS procurement_mitigation_executions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    execution_number VARCHAR(40) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    action_id BIGINT UNSIGNED NOT NULL,
    execution_type VARCHAR(50) NOT NULL,
    title VARCHAR(190) NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'proposed',
    change_risk VARCHAR(20) NOT NULL DEFAULT 'medium',
    approval_id BIGINT UNSIGNED NULL,
    before_json LONGTEXT NOT NULL,
    target_json LONGTEXT NOT NULL,
    actual_json LONGTEXT NOT NULL,
    rollback_plan TEXT NOT NULL,
    evidence_note TEXT NOT NULL,
    scheduled_date DATE NOT NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mitigation_execution_company_status (company_id, status),
    KEY idx_mitigation_execution_plan (plan_id),
    KEY idx_mitigation_execution_action (action_id),
    KEY idx_mitigation_execution_owner (owner_id),
    KEY idx_mitigation_execution_approval (approval_id),
    KEY idx_mitigation_execution_risk (change_risk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_mitigation_execution_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    execution_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    from_status VARCHAR(32) NULL,
    to_status VARCHAR(32) NULL,
    evidence_note TEXT NOT NULL,
    event_json LONGTEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_execution_events_execution (execution_id, created_at),
    KEY idx_execution_events_type (event_type),
    KEY idx_execution_events_actor (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_recovery_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    verification_number VARCHAR(40) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL,
    execution_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    planned_recovery_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    actual_recovery_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    planned_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
    actual_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
    before_risk_score DECIMAL(8,2) NOT NULL DEFAULT 0,
    after_risk_score DECIMAL(8,2) NOT NULL DEFAULT 0,
    before_lead_time_days INT NOT NULL DEFAULT 0,
    after_lead_time_days INT NOT NULL DEFAULT 0,
    inventory_exposure_reduced DECIMAL(18,2) NOT NULL DEFAULT 0,
    po_value_redirected DECIMAL(18,2) NOT NULL DEFAULT 0,
    service_level_before DECIMAL(8,2) NOT NULL DEFAULT 0,
    service_level_after DECIMAL(8,2) NOT NULL DEFAULT 0,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    evidence_note TEXT NOT NULL,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_recovery_verification_company_status (company_id, status),
    KEY idx_recovery_verification_execution (execution_id),
    KEY idx_recovery_verification_reviewer (reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version, description, checksum_sha256)
VALUES ('4.3-section14', 'Mitigation execution, recovery verification, and procurement change control', NULL)
ON DUPLICATE KEY UPDATE description = VALUES(description);
