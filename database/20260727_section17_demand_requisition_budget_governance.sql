-- Gruber Procurement Intelligence
-- Section 17: Demand Intake, Purchase Requisitions & Budget Governance
-- Apply after Version 3 and Sections 11 through 16 migrations.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS procurement_request_governance_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id BIGINT UNSIGNED NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    approval_id BIGINT UNSIGNED NULL,
    budget_envelope_id BIGINT UNSIGNED NULL,
    sourcing_assessment_id BIGINT UNSIGNED NULL,
    converted_po_id BIGINT UNSIGNED NULL,
    capex_opex VARCHAR(32) NOT NULL DEFAULT 'operating_expense',
    unplanned_demand BOOLEAN NOT NULL DEFAULT FALSE,
    source_status VARCHAR(32) NOT NULL DEFAULT 'not_assessed',
    evidence_note TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_request_governance_profile (purchase_request_id),
    KEY idx_request_governance_owner (owner_id),
    KEY idx_request_governance_reviewer (reviewer_id),
    KEY idx_request_governance_approval (approval_id),
    KEY idx_request_governance_budget (budget_envelope_id),
    KEY idx_request_governance_assessment (sourcing_assessment_id),
    KEY idx_request_governance_po (converted_po_id),
    KEY idx_request_governance_source (source_status, unplanned_demand)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_budget_envelopes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_number VARCHAR(40) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    project_workorder_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    budget_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    requested_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    approved_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    committed_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    actual_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    owner_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'active',
    evidence_note TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_budget_company_period (company_id, period_start, period_end),
    KEY idx_budget_department (department_id),
    KEY idx_budget_project (project_workorder_id),
    KEY idx_budget_category (category_id),
    KEY idx_budget_owner (owner_id),
    KEY idx_budget_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_demand_forecasts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    forecast_number VARCHAR(40) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    forecast_quantity DECIMAL(18,4) NOT NULL DEFAULT 0,
    forecast_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    confidence_pct DECIMAL(6,2) NOT NULL DEFAULT 50,
    source_note TEXT NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_forecast_company_period (company_id, period_start, period_end),
    KEY idx_forecast_category (category_id),
    KEY idx_forecast_owner (owner_id),
    KEY idx_forecast_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_request_sourcing_assessments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_number VARCHAR(40) NOT NULL UNIQUE,
    purchase_request_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    recommended_supplier_id BIGINT UNSIGNED NULL,
    recommended_action VARCHAR(40) NOT NULL,
    inventory_avoidance_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    open_po_coverage_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    consolidation_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    contract_covered_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    off_contract_exposure DECIMAL(18,2) NOT NULL DEFAULT 0,
    duplicate_request_count INT UNSIGNED NOT NULL DEFAULT 0,
    required_date_risk VARCHAR(20) NOT NULL DEFAULT 'medium',
    budget_status VARCHAR(24) NOT NULL DEFAULT 'no_budget',
    supplier_risk VARCHAR(20) NOT NULL DEFAULT 'medium',
    performance_score DECIMAL(6,2) NOT NULL DEFAULT 0,
    price_variance_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
    assessment_score DECIMAL(6,2) NOT NULL DEFAULT 0,
    evidence_note TEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_request_assessment_request (purchase_request_id, created_at),
    KEY idx_request_assessment_company (company_id),
    KEY idx_request_assessment_supplier (recommended_supplier_id),
    KEY idx_request_assessment_action (recommended_action),
    KEY idx_request_assessment_risk (required_date_risk, supplier_risk),
    KEY idx_request_assessment_creator (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_request_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    from_status VARCHAR(32) NULL,
    to_status VARCHAR(32) NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    evidence_note TEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_request_event_request (purchase_request_id, created_at),
    KEY idx_request_event_type (event_type),
    KEY idx_request_event_severity (severity),
    KEY idx_request_event_actor (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version, description, checksum_sha256)
VALUES ('4.6-section17', 'Demand intake, purchase requisitions, budget governance, sourcing assessment, forecasts, and request-to-PO traceability', NULL)
ON DUPLICATE KEY UPDATE description = VALUES(description);
