-- Gruber Procurement Intelligence
-- Section 21: Enterprise Spend Analytics, Category Strategy & Executive Procurement Planning
-- Apply after Version 3 and Sections 11 through 20 migrations.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS procurement_spend_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_number VARCHAR(80) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    fiscal_year INT UNSIGNED NOT NULL,
    fiscal_period VARCHAR(32) NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NULL,
    contract_id BIGINT UNSIGNED NULL,
    item_id BIGINT UNSIGNED NULL,
    project_workorder_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    buyer_id BIGINT UNSIGNED NULL,
    business_purpose VARCHAR(64) NOT NULL,
    ordered_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    received_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    invoiced_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    payment_ready_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    open_commitments DECIMAL(18,2) NOT NULL DEFAULT 0,
    contracted_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    off_contract_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    emergency_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    freight_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
    quality_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
    inventory_carrying_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
    addressable_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    spend_under_management DECIMAL(18,2) NOT NULL DEFAULT 0,
    spend_under_contract DECIMAL(18,2) NOT NULL DEFAULT 0,
    high_risk_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    source_hash CHAR(64) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    owner_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    approval_id BIGINT UNSIGNED NULL,
    evidence_note TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_spend_snapshot_period (period_start,period_end,company_id),
    KEY idx_spend_snapshot_category (category_id,period_end),
    KEY idx_spend_snapshot_supplier (supplier_id,period_end),
    KEY idx_spend_snapshot_status (status,period_end),
    CONSTRAINT fk_spend_snapshot_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_spend_snapshot_category FOREIGN KEY (category_id) REFERENCES purchasing_categories(id),
    CONSTRAINT fk_spend_snapshot_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_spend_snapshot_contract FOREIGN KEY (contract_id) REFERENCES supplier_contracts(id),
    CONSTRAINT fk_spend_snapshot_item FOREIGN KEY (item_id) REFERENCES items(id),
    CONSTRAINT fk_spend_snapshot_project FOREIGN KEY (project_workorder_id) REFERENCES projects_workorders(id),
    CONSTRAINT fk_spend_snapshot_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_spend_snapshot_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_spend_snapshot_buyer FOREIGN KEY (buyer_id) REFERENCES users(id),
    CONSTRAINT fk_spend_snapshot_owner FOREIGN KEY (owner_id) REFERENCES users(id),
    CONSTRAINT fk_spend_snapshot_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id),
    CONSTRAINT fk_spend_snapshot_approval FOREIGN KEY (approval_id) REFERENCES workflow_approvals(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_spend_classifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NULL,
    classification_type VARCHAR(64) NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    rationale TEXT NOT NULL,
    root_cause TEXT NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'open',
    due_date DATE NOT NULL,
    resolution_note TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_spend_classification_snapshot (snapshot_id,status),
    KEY idx_spend_classification_company (company_id,severity,status),
    KEY idx_spend_classification_owner (owner_id,due_date),
    CONSTRAINT fk_spend_classification_snapshot FOREIGN KEY (snapshot_id) REFERENCES procurement_spend_snapshots(id) ON DELETE CASCADE,
    CONSTRAINT fk_spend_classification_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_spend_classification_owner FOREIGN KEY (owner_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_category_strategies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    strategy_number VARCHAR(80) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    fiscal_year INT UNSIGNED NOT NULL,
    title VARCHAR(240) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    owner_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    approval_id BIGINT UNSIGNED NULL,
    current_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    addressable_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    contracted_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    supplier_count INT UNSIGNED NOT NULL DEFAULT 0,
    concentration_pct DECIMAL(8,3) NOT NULL DEFAULT 0,
    high_risk_spend DECIMAL(18,2) NOT NULL DEFAULT 0,
    validated_savings DECIMAL(18,2) NOT NULL DEFAULT 0,
    demand_summary TEXT NOT NULL,
    market_structure TEXT NOT NULL,
    supplier_panel TEXT NOT NULL,
    risk_summary TEXT NOT NULL,
    inventory_alternatives TEXT NOT NULL,
    performance_summary TEXT NOT NULL,
    negotiation_strategy TEXT NOT NULL,
    sourcing_strategy TEXT NOT NULL,
    target_terms TEXT NOT NULL,
    strategy_decision VARCHAR(64) NOT NULL,
    review_date DATE NOT NULL,
    renewal_date DATE NOT NULL,
    evidence_note TEXT NOT NULL,
    submitted_at DATETIME NULL,
    approved_at DATETIME NULL,
    locked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_category_strategy_scope (company_id,category_id,fiscal_year),
    KEY idx_category_strategy_status (status,review_date),
    KEY idx_category_strategy_owner (owner_id,status),
    CONSTRAINT fk_category_strategy_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_category_strategy_category FOREIGN KEY (category_id) REFERENCES purchasing_categories(id),
    CONSTRAINT fk_category_strategy_owner FOREIGN KEY (owner_id) REFERENCES users(id),
    CONSTRAINT fk_category_strategy_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id),
    CONSTRAINT fk_category_strategy_approval FOREIGN KEY (approval_id) REFERENCES workflow_approvals(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_category_strategy_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    strategy_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NULL,
    action_number VARCHAR(80) NOT NULL UNIQUE,
    action_type VARCHAR(64) NOT NULL,
    title VARCHAR(240) NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    due_date DATE NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'planned',
    planned_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    actual_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    milestone TEXT NOT NULL,
    evidence_note TEXT NOT NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_category_action_strategy (strategy_id,status,due_date),
    KEY idx_category_action_owner (owner_id,status,due_date),
    CONSTRAINT fk_category_action_strategy FOREIGN KEY (strategy_id) REFERENCES procurement_category_strategies(id) ON DELETE CASCADE,
    CONSTRAINT fk_category_action_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_category_action_owner FOREIGN KEY (owner_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_planning_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_number VARCHAR(80) NOT NULL UNIQUE,
    company_id BIGINT UNSIGNED NULL,
    fiscal_year INT UNSIGNED NOT NULL,
    period_type VARCHAR(32) NOT NULL,
    period_label VARCHAR(120) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    version_number INT UNSIGNED NOT NULL DEFAULT 1,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    owner_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    approval_id BIGINT UNSIGNED NULL,
    evidence_note TEXT NOT NULL,
    submitted_at DATETIME NULL,
    approved_at DATETIME NULL,
    locked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_procurement_plan_scope (company_id,fiscal_year,period_type,period_label,version_number),
    KEY idx_procurement_plan_status (status,period_end),
    CONSTRAINT fk_procurement_plan_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_procurement_plan_owner FOREIGN KEY (owner_id) REFERENCES users(id),
    CONSTRAINT fk_procurement_plan_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id),
    CONSTRAINT fk_procurement_plan_approval FOREIGN KEY (approval_id) REFERENCES workflow_approvals(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_plan_targets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    planning_period_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NULL,
    metric_code VARCHAR(80) NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NULL,
    target_value DECIMAL(18,4) NOT NULL DEFAULT 0,
    actual_value DECIMAL(18,4) NOT NULL DEFAULT 0,
    variance_value DECIMAL(18,4) NOT NULL DEFAULT 0,
    variance_pct DECIMAL(12,4) NOT NULL DEFAULT 0,
    tolerance_pct DECIMAL(12,4) NOT NULL DEFAULT 5,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    owner_id BIGINT UNSIGNED NOT NULL,
    root_cause TEXT NOT NULL,
    corrective_action TEXT NOT NULL,
    revised_forecast DECIMAL(18,4) NOT NULL DEFAULT 0,
    executive_decision TEXT NOT NULL,
    evidence_note TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_plan_target_dimension (planning_period_id,metric_code,category_id,supplier_id),
    KEY idx_plan_target_status (status,metric_code),
    KEY idx_plan_target_owner (owner_id,status),
    CONSTRAINT fk_plan_target_period FOREIGN KEY (planning_period_id) REFERENCES procurement_planning_periods(id) ON DELETE CASCADE,
    CONSTRAINT fk_plan_target_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_plan_target_category FOREIGN KEY (category_id) REFERENCES purchasing_categories(id),
    CONSTRAINT fk_plan_target_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_plan_target_owner FOREIGN KEY (owner_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procurement_strategy_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    from_status VARCHAR(32) NULL,
    to_status VARCHAR(32) NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    evidence_note TEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_strategy_event_company (company_id,created_at),
    KEY idx_strategy_event_entity (entity_type,entity_id,created_at),
    KEY idx_strategy_event_type (event_type,created_at),
    CONSTRAINT fk_strategy_event_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_strategy_event_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (permission_key,area,action,description) VALUES
('strategy.view','strategy','view','View enterprise spend analytics and procurement strategy'),
('strategy.create','strategy','create','Create spend snapshots, category strategies, plans, and targets'),
('strategy.edit','strategy','edit','Edit procurement strategies, plans, classifications, and targets'),
('strategy.assign','strategy','assign','Assign strategy and planning ownership'),
('strategy.submit','strategy','submit','Submit strategies and plans for independent review'),
('strategy.review','strategy','review','Review strategy and planning evidence'),
('strategy.approve','strategy','approve','Approve and lock category strategies and procurement plans'),
('strategy.export','strategy','export','Export governed spend and strategy data');

INSERT IGNORE INTO role_permissions (role_id,permission_id,granted_by)
SELECT r.id,p.id,NULL FROM roles r JOIN permissions p ON p.area='strategy'
WHERE r.code IN ('system_administrator','company_administrator','procurement_manager');
INSERT IGNORE INTO role_permissions (role_id,permission_id,granted_by)
SELECT r.id,p.id,NULL FROM roles r JOIN permissions p ON p.permission_key IN ('strategy.view','strategy.export','strategy.approve') WHERE r.code='executive';
INSERT IGNORE INTO role_permissions (role_id,permission_id,granted_by)
SELECT r.id,p.id,NULL FROM roles r JOIN permissions p ON p.permission_key IN ('strategy.view','strategy.create','strategy.edit','strategy.submit') WHERE r.code='data_contributor';
INSERT IGNORE INTO role_permissions (role_id,permission_id,granted_by)
SELECT r.id,p.id,NULL FROM roles r JOIN permissions p ON p.permission_key IN ('strategy.view','strategy.review','strategy.approve','strategy.export') WHERE r.code='reviewer';
INSERT IGNORE INTO role_permissions (role_id,permission_id,granted_by)
SELECT r.id,p.id,NULL FROM roles r JOIN permissions p ON p.permission_key='strategy.view' WHERE r.code='read_only';

INSERT INTO schema_migrations (version,description,checksum_sha256)
VALUES ('5.0-section21','Enterprise spend snapshots, classifications, category strategies, strategy actions, procurement plans, plan targets, immutable strategy events, and strategy permissions',NULL)
ON DUPLICATE KEY UPDATE description=VALUES(description);