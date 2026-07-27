-- Gruber Procurement Intelligence
-- Section 16: Contract Lifecycle, SLA Compliance & Renewal Governance
-- Apply after Version 3 and Sections 11 through 15 migrations.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS supplier_contract_governance_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id BIGINT UNSIGNED NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    renewal_notice_days INT UNSIGNED NOT NULL DEFAULT 90,
    review_status VARCHAR(32) NOT NULL DEFAULT 'draft',
    risk_tier VARCHAR(20) NOT NULL DEFAULT 'medium',
    performance_review_id BIGINT UNSIGNED NULL,
    approval_id BIGINT UNSIGNED NULL,
    committed_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    actual_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    off_contract_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    evidence_note TEXT NOT NULL,
    next_review_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_contract_governance_profile (contract_id),
    KEY idx_contract_governance_owner (owner_id),
    KEY idx_contract_governance_reviewer (reviewer_id),
    KEY idx_contract_governance_status (review_status, risk_tier),
    KEY idx_contract_governance_performance (performance_review_id),
    KEY idx_contract_governance_approval (approval_id),
    KEY idx_contract_governance_next_review (next_review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_contract_obligations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obligation_number VARCHAR(40) NOT NULL UNIQUE,
    contract_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    obligation_type VARCHAR(50) NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    due_date DATE NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'not_started',
    target_value DECIMAL(18,4) NOT NULL DEFAULT 0,
    actual_value DECIMAL(18,4) NOT NULL DEFAULT 0,
    unit VARCHAR(30) NOT NULL DEFAULT 'percent',
    evidence_note TEXT NOT NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contract_obligation_contract (contract_id, due_date),
    KEY idx_contract_obligation_company_status (company_id, status),
    KEY idx_contract_obligation_owner (owner_id),
    KEY idx_contract_obligation_type (obligation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_contract_amendments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    amendment_number VARCHAR(40) NOT NULL UNIQUE,
    contract_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    effective_date DATE NOT NULL,
    value_change DECIMAL(18,2) NOT NULL DEFAULT 0,
    term_change_days INT NOT NULL DEFAULT 0,
    before_terms TEXT NOT NULL,
    after_terms TEXT NOT NULL,
    rationale TEXT NOT NULL,
    approval_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contract_amendment_contract (contract_id, effective_date),
    KEY idx_contract_amendment_company_status (company_id, status),
    KEY idx_contract_amendment_approval (approval_id),
    KEY idx_contract_amendment_creator (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_contract_renewal_decisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    decision_number VARCHAR(40) NOT NULL UNIQUE,
    contract_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    performance_review_id BIGINT UNSIGNED NULL,
    decision VARCHAR(50) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    owner_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    approval_id BIGINT UNSIGNED NULL,
    current_annual_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    proposed_annual_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    value_change DECIMAL(18,2) NOT NULL DEFAULT 0,
    effective_date DATE NOT NULL,
    alternative_supplier_id BIGINT UNSIGNED NULL,
    rationale TEXT NOT NULL,
    negotiation_objectives TEXT NOT NULL,
    evidence_note TEXT NOT NULL,
    implemented_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contract_renewal_contract (contract_id, updated_at),
    KEY idx_contract_renewal_company_status (company_id, status),
    KEY idx_contract_renewal_supplier (supplier_id),
    KEY idx_contract_renewal_performance (performance_review_id),
    KEY idx_contract_renewal_approval (approval_id),
    KEY idx_contract_renewal_alternate (alternative_supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_contract_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(32) NULL,
    to_status VARCHAR(32) NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    evidence_note TEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contract_event_contract (contract_id, created_at),
    KEY idx_contract_event_entity (entity_type, entity_id),
    KEY idx_contract_event_type (event_type),
    KEY idx_contract_event_severity (severity),
    KEY idx_contract_event_actor (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version, description, checksum_sha256)
VALUES ('4.5-section16', 'Contract lifecycle, SLA compliance, amendments, renewal decisions, and commercial governance', NULL)
ON DUPLICATE KEY UPDATE description = VALUES(description);
