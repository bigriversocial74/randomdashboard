-- Gruber Procurement Intelligence
-- Section 15: Supplier Performance Monitoring, Corrective Action & Continuous Improvement
-- Apply after Version 3 and Sections 11, 12, 13, and 14 migrations.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS supplier_performance_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_number VARCHAR(40) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    source_execution_id BIGINT UNSIGNED NULL,
    source_verification_id BIGINT UNSIGNED NULL,
    review_window_days INT NOT NULL DEFAULT 30,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    recommendation VARCHAR(32) NOT NULL DEFAULT 'approved',
    risk_tier VARCHAR(20) NOT NULL DEFAULT 'medium',
    approval_id BIGINT UNSIGNED NULL,
    baseline_json LONGTEXT NOT NULL,
    current_json LONGTEXT NOT NULL,
    targets_json LONGTEXT NOT NULL,
    overall_score DECIMAL(8,2) NOT NULL DEFAULT 0,
    improvement_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
    sustainability_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
    repeated_failure_count INT NOT NULL DEFAULT 0,
    spend_exposure DECIMAL(18,2) NOT NULL DEFAULT 0,
    savings_retained DECIMAL(18,2) NOT NULL DEFAULT 0,
    evidence_note TEXT NOT NULL,
    next_review_date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_performance_company_status (company_id, status),
    KEY idx_supplier_performance_supplier (supplier_id, updated_at),
    KEY idx_supplier_performance_execution (source_execution_id),
    KEY idx_supplier_performance_verification (source_verification_id),
    KEY idx_supplier_performance_owner (owner_id),
    KEY idx_supplier_performance_reviewer (reviewer_id),
    KEY idx_supplier_performance_approval (approval_id),
    KEY idx_supplier_performance_risk (risk_tier),
    KEY idx_supplier_performance_next_review (next_review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_corrective_action_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action_number VARCHAR(40) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL,
    review_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    root_cause TEXT NOT NULL,
    corrective_action TEXT NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'planned',
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    due_date DATE NOT NULL,
    target_metric VARCHAR(64) NOT NULL,
    target_value DECIMAL(18,4) NOT NULL DEFAULT 0,
    actual_value DECIMAL(18,4) NOT NULL DEFAULT 0,
    evidence_note TEXT NOT NULL,
    completed_at DATETIME NULL,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_corrective_action_company_status (company_id, status),
    KEY idx_corrective_action_review (review_id, updated_at),
    KEY idx_corrective_action_supplier (supplier_id),
    KEY idx_corrective_action_owner (owner_id),
    KEY idx_corrective_action_due (due_date),
    KEY idx_corrective_action_metric (target_metric),
    KEY idx_corrective_action_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_performance_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    metric VARCHAR(64) NULL,
    previous_value DECIMAL(18,4) NULL,
    current_value DECIMAL(18,4) NULL,
    threshold_value DECIMAL(18,4) NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    evidence_note TEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_performance_event_review (review_id, created_at),
    KEY idx_supplier_performance_event_type (event_type),
    KEY idx_supplier_performance_event_metric (metric),
    KEY idx_supplier_performance_event_severity (severity),
    KEY idx_supplier_performance_event_actor (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version, description, checksum_sha256)
VALUES ('4.4-section15', 'Supplier performance monitoring, corrective action, and continuous improvement', NULL)
ON DUPLICATE KEY UPDATE description = VALUES(description);
