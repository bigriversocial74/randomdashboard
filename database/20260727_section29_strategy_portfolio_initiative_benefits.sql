-- Gruber Procurement Intelligence
-- Section 29: Enterprise Strategy Portfolio, Initiative Governance & Benefits Realization
-- Apply after Sections 11 through 28. Idempotent for MySQL 8.0 and MariaDB 10.11.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS enterprise_strategy_portfolios (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 portfolio_number VARCHAR(64) NOT NULL UNIQUE,
 portfolio_name VARCHAR(190) NOT NULL,
 portfolio_type VARCHAR(50) NOT NULL DEFAULT 'enterprise_transformation',
 entity_id BIGINT UNSIGNED NULL,
 company_id BIGINT UNSIGNED NULL,
 fiscal_year SMALLINT UNSIGNED NOT NULL,
 funding_limit DECIMAL(20,2) NOT NULL DEFAULT 0,
 capacity_limit_hours DECIMAL(12,2) NOT NULL DEFAULT 0,
 strategic_theme TEXT NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'draft',
 owner_user_id BIGINT UNSIGNED NOT NULL,
 reviewer_user_id BIGINT UNSIGNED NOT NULL,
 approver_user_id BIGINT UNSIGNED NOT NULL,
 evidence_note TEXT NOT NULL,
 published_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_portfolio_entity(entity_id,status,fiscal_year),
 KEY idx_portfolio_company(company_id,status,fiscal_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_strategic_initiatives (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 initiative_number VARCHAR(64) NOT NULL UNIQUE,
 portfolio_id BIGINT UNSIGNED NOT NULL,
 parent_initiative_id BIGINT UNSIGNED NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 company_id BIGINT UNSIGNED NOT NULL,
 initiative_name VARCHAR(190) NOT NULL,
 description TEXT NOT NULL,
 initiative_type VARCHAR(50) NOT NULL DEFAULT 'process_improvement',
 stage VARCHAR(40) NOT NULL DEFAULT 'idea',
 health VARCHAR(24) NOT NULL DEFAULT 'not_started',
 strategic_alignment_score DECIMAL(6,2) NOT NULL DEFAULT 0,
 value_score DECIMAL(6,2) NOT NULL DEFAULT 0,
 urgency_score DECIMAL(6,2) NOT NULL DEFAULT 0,
 risk_score DECIMAL(6,2) NOT NULL DEFAULT 0,
 confidence_pct TINYINT UNSIGNED NOT NULL DEFAULT 50,
 proposed_cost DECIMAL(20,2) NOT NULL DEFAULT 0,
 forecast_cost DECIMAL(20,2) NOT NULL DEFAULT 0,
 actual_cost DECIMAL(20,2) NOT NULL DEFAULT 0,
 expected_annual_benefit DECIMAL(20,2) NOT NULL DEFAULT 0,
 realized_benefit DECIMAL(20,2) NOT NULL DEFAULT 0,
 capacity_demand_hours DECIMAL(12,2) NOT NULL DEFAULT 0,
 start_date DATE NOT NULL,
 target_date DATE NOT NULL,
 owner_user_id BIGINT UNSIGNED NOT NULL,
 sponsor_user_id BIGINT UNSIGNED NOT NULL,
 reviewer_user_id BIGINT UNSIGNED NOT NULL,
 approver_user_id BIGINT UNSIGNED NOT NULL,
 benefit_validator_user_id BIGINT UNSIGNED NOT NULL,
 primary_goal_id BIGINT UNSIGNED NULL,
 primary_kpi_definition_id BIGINT UNSIGNED NULL,
 process_id BIGINT UNSIGNED NULL,
 process_step_id BIGINT UNSIGNED NULL,
 budget_envelope_id BIGINT UNSIGNED NULL,
 savings_opportunity_id BIGINT UNSIGNED NULL,
 evidence_note TEXT NOT NULL,
 submitted_at DATETIME NULL,
 reviewed_at DATETIME NULL,
 approved_at DATETIME NULL,
 funded_at DATETIME NULL,
 started_at DATETIME NULL,
 completed_at DATETIME NULL,
 cancelled_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_initiative_portfolio(portfolio_id,stage,target_date),
 KEY idx_initiative_entity(entity_id,stage,target_date),
 KEY idx_initiative_company(company_id,stage,target_date),
 KEY idx_initiative_process(process_id,process_step_id),
 KEY idx_initiative_goal(primary_goal_id,primary_kpi_definition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_initiative_links (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 initiative_id BIGINT UNSIGNED NOT NULL,
 link_type VARCHAR(60) NOT NULL,
 source_id BIGINT UNSIGNED NOT NULL,
 relationship_type VARCHAR(60) NOT NULL,
 relationship_label VARCHAR(190) NOT NULL,
 link_key CHAR(64) NOT NULL UNIQUE,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_initiative_link(initiative_id,link_type),
 KEY idx_initiative_source(link_type,source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_initiative_stage_gates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 initiative_id BIGINT UNSIGNED NOT NULL,
 gate_code VARCHAR(50) NOT NULL,
 from_stage VARCHAR(40) NOT NULL,
 to_stage VARCHAR(40) NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'prepared',
 decision VARCHAR(32) NULL,
 prepared_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL,
 evidence_note TEXT NOT NULL,
 review_note TEXT NOT NULL,
 approval_note TEXT NOT NULL,
 idempotency_key CHAR(64) NOT NULL UNIQUE,
 prepared_at DATETIME NOT NULL,
 reviewed_at DATETIME NULL,
 approved_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_gate_initiative(initiative_id,status,prepared_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_initiative_funding (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 initiative_id BIGINT UNSIGNED NOT NULL,
 budget_envelope_id BIGINT UNSIGNED NOT NULL,
 funding_number VARCHAR(64) NOT NULL UNIQUE,
 approved_amount DECIMAL(20,2) NOT NULL,
 forecast_cost DECIMAL(20,2) NOT NULL,
 currency_code CHAR(3) NOT NULL DEFAULT 'USD',
 status VARCHAR(32) NOT NULL DEFAULT 'reserved',
 prepared_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL,
 evidence_note TEXT NOT NULL,
 idempotency_key CHAR(64) NOT NULL UNIQUE,
 reserved_at DATETIME NOT NULL,
 approved_at DATETIME NULL,
 released_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_funding_initiative(initiative_id,status),
 KEY idx_funding_budget(budget_envelope_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_initiative_milestones (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 initiative_id BIGINT UNSIGNED NOT NULL,
 milestone_number VARCHAR(64) NOT NULL UNIQUE,
 milestone_name VARCHAR(190) NOT NULL,
 milestone_type VARCHAR(50) NOT NULL DEFAULT 'delivery',
 status VARCHAR(32) NOT NULL DEFAULT 'scheduled',
 owner_user_id BIGINT UNSIGNED NOT NULL,
 due_at DATETIME NOT NULL,
 work_item_id BIGINT UNSIGNED NULL,
 calendar_event_id BIGINT UNSIGNED NULL,
 process_instance_id BIGINT UNSIGNED NULL,
 step_instance_id BIGINT UNSIGNED NULL,
 evidence_note TEXT NOT NULL,
 source_key CHAR(64) NOT NULL UNIQUE,
 completed_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_milestone_initiative(initiative_id,status,due_at),
 KEY idx_milestone_work(work_item_id),
 KEY idx_milestone_calendar(calendar_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_initiative_benefits (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 initiative_id BIGINT UNSIGNED NOT NULL,
 benefit_number VARCHAR(64) NOT NULL UNIQUE,
 benefit_type VARCHAR(50) NOT NULL,
 baseline_value DECIMAL(20,4) NOT NULL DEFAULT 0,
 expected_value DECIMAL(20,4) NOT NULL DEFAULT 0,
 actual_value DECIMAL(20,4) NOT NULL DEFAULT 0,
 financial_value DECIMAL(20,2) NOT NULL DEFAULT 0,
 unit VARCHAR(40) NOT NULL DEFAULT 'USD',
 confidence_pct TINYINT UNSIGNED NOT NULL DEFAULT 50,
 kpi_definition_id BIGINT UNSIGNED NULL,
 baseline_snapshot_id BIGINT UNSIGNED NULL,
 actual_snapshot_id BIGINT UNSIGNED NULL,
 savings_realization_period_id BIGINT UNSIGNED NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'prepared',
 owner_user_id BIGINT UNSIGNED NOT NULL,
 validator_user_id BIGINT UNSIGNED NOT NULL,
 evidence_note TEXT NOT NULL,
 validation_note TEXT NOT NULL,
 idempotency_key CHAR(64) NOT NULL UNIQUE,
 submitted_at DATETIME NULL,
 validated_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_benefit_initiative(initiative_id,status),
 KEY idx_benefit_kpi(kpi_definition_id,status),
 KEY idx_benefit_savings(savings_realization_period_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_portfolio_scenarios (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 portfolio_id BIGINT UNSIGNED NOT NULL,
 scenario_number VARCHAR(64) NOT NULL UNIQUE,
 scenario_type VARCHAR(60) NOT NULL,
 scenario_name VARCHAR(190) NOT NULL,
 budget_limit DECIMAL(20,2) NOT NULL DEFAULT 0,
 capacity_limit_hours DECIMAL(12,2) NOT NULL DEFAULT 0,
 priority_weights_json LONGTEXT NOT NULL,
 results_json LONGTEXT NOT NULL,
 selected_cost DECIMAL(20,2) NOT NULL DEFAULT 0,
 selected_benefit DECIMAL(20,2) NOT NULL DEFAULT 0,
 selected_capacity_hours DECIMAL(12,2) NOT NULL DEFAULT 0,
 status VARCHAR(32) NOT NULL DEFAULT 'simulated',
 prepared_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 evidence_note TEXT NOT NULL,
 idempotency_key CHAR(64) NOT NULL UNIQUE,
 generated_at DATETIME NOT NULL,
 reviewed_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_scenario_portfolio(portfolio_id,status,generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_process_change_proposals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 initiative_id BIGINT UNSIGNED NOT NULL,
 proposal_number VARCHAR(64) NOT NULL UNIQUE,
 process_id BIGINT UNSIGNED NOT NULL,
 process_version_id BIGINT UNSIGNED NOT NULL,
 process_step_id BIGINT UNSIGNED NULL,
 proposed_change TEXT NOT NULL,
 reason TEXT NOT NULL,
 impact_json LONGTEXT NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'draft',
 prepared_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL,
 evidence_note TEXT NOT NULL,
 review_note TEXT NOT NULL,
 approval_note TEXT NOT NULL,
 submitted_at DATETIME NULL,
 reviewed_at DATETIME NULL,
 approved_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_change_initiative(initiative_id,status),
 KEY idx_change_process(process_id,process_version_id,process_step_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions(permission_key,area,action,description) VALUES
('portfolio_intelligence.view','portfolio_intelligence','view','View scoped portfolios, initiatives, scenarios, milestones, funding, and benefits'),
('portfolio_intelligence.create','portfolio_intelligence','create','Create governed portfolios, initiatives, milestones, scenarios, and process-change proposals'),
('portfolio_intelligence.edit','portfolio_intelligence','edit','Edit permitted draft portfolio and initiative records'),
('portfolio_intelligence.submit','portfolio_intelligence','submit','Submit initiatives, funding, benefits, and process-change proposals for review'),
('portfolio_intelligence.review','portfolio_intelligence','review','Independently review stage gates, funding, scenarios, benefits, and process changes'),
('portfolio_intelligence.approve','portfolio_intelligence','approve','Approve stage gates, funding, and process-change proposals'),
('portfolio_intelligence.fund','portfolio_intelligence','fund','Reserve and approve initiative funding against Section 17 budget envelopes'),
('portfolio_intelligence.validate_benefits','portfolio_intelligence','validate_benefits','Independently validate benefits against KPI and Section 20 evidence'),
('portfolio_intelligence.export','portfolio_intelligence','export','Export scoped portfolio and benefit evidence'),
('portfolio_intelligence.administer','portfolio_intelligence','administer','Administer enterprise strategy portfolio governance');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.area='portfolio_intelligence' WHERE r.code='system_administrator';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('portfolio_intelligence.view','portfolio_intelligence.create','portfolio_intelligence.edit','portfolio_intelligence.submit','portfolio_intelligence.review','portfolio_intelligence.approve','portfolio_intelligence.fund','portfolio_intelligence.validate_benefits','portfolio_intelligence.export') WHERE r.code='executive';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.area='portfolio_intelligence' WHERE r.code='company_administrator';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('portfolio_intelligence.view','portfolio_intelligence.create','portfolio_intelligence.edit','portfolio_intelligence.submit','portfolio_intelligence.review','portfolio_intelligence.approve','portfolio_intelligence.fund','portfolio_intelligence.validate_benefits','portfolio_intelligence.export') WHERE r.code='procurement_manager';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('portfolio_intelligence.view','portfolio_intelligence.create','portfolio_intelligence.edit','portfolio_intelligence.submit') WHERE r.code='data_contributor';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('portfolio_intelligence.view','portfolio_intelligence.review','portfolio_intelligence.approve','portfolio_intelligence.validate_benefits','portfolio_intelligence.export') WHERE r.code='reviewer';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('portfolio_intelligence.view','portfolio_intelligence.export') WHERE r.code='read_only';

INSERT IGNORE INTO schema_migrations(version,description,applied_at)
VALUES('5.8-section29','Enterprise strategy portfolio, initiative governance, process impact, execution integration, scenarios, and benefits realization',NOW());