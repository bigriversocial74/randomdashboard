-- Gruber Procurement Intelligence
-- Section 28: Executive Command Center, Goals, KPI Governance & Performance Intelligence
-- Apply after Sections 11 through 27. Idempotent for MySQL 8.0 and MariaDB 10.11.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS enterprise_goals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 goal_number VARCHAR(64) NOT NULL UNIQUE,
 parent_goal_id BIGINT UNSIGNED NULL,
 entity_id BIGINT UNSIGNED NULL,
 company_id BIGINT UNSIGNED NULL,
 goal_name VARCHAR(190) NOT NULL,
 description TEXT NOT NULL,
 goal_type VARCHAR(40) NOT NULL DEFAULT 'operational',
 unit VARCHAR(40) NOT NULL DEFAULT 'count',
 direction VARCHAR(24) NOT NULL DEFAULT 'higher_better',
 target_value DECIMAL(20,4) NOT NULL DEFAULT 0,
 period_start DATE NOT NULL,
 period_end DATE NOT NULL,
 owner_user_id BIGINT UNSIGNED NOT NULL,
 reviewer_user_id BIGINT UNSIGNED NOT NULL,
 executive_sponsor_user_id BIGINT UNSIGNED NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'draft',
 confidence_pct TINYINT UNSIGNED NOT NULL DEFAULT 50,
 evidence_note TEXT NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_goal_entity_status(entity_id,status,period_end),
 KEY idx_goal_company_status(company_id,status,period_end),
 KEY idx_goal_owner(owner_user_id,status,period_end),
 KEY idx_goal_parent(parent_goal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_goal_links (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 goal_id BIGINT UNSIGNED NOT NULL,
 link_type VARCHAR(50) NOT NULL,
 source_id BIGINT UNSIGNED NOT NULL,
 source_key CHAR(64) NOT NULL UNIQUE,
 relationship_label VARCHAR(190) NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_goal_link_goal(goal_id,link_type),
 KEY idx_goal_link_source(link_type,source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_kpi_definitions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kpi_code VARCHAR(80) NOT NULL UNIQUE,
 kpi_name VARCHAR(190) NOT NULL,
 description TEXT NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 company_id BIGINT UNSIGNED NULL,
 source_module VARCHAR(80) NOT NULL,
 metric_key VARCHAR(120) NOT NULL,
 process_id BIGINT UNSIGNED NULL,
 process_step_id BIGINT UNSIGNED NULL,
 owner_user_id BIGINT UNSIGNED NOT NULL,
 data_owner_user_id BIGINT UNSIGNED NOT NULL,
 refresh_frequency VARCHAR(32) NOT NULL DEFAULT 'daily',
 status VARCHAR(32) NOT NULL DEFAULT 'draft',
 active_version_id BIGINT UNSIGNED NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_kpi_entity_status(entity_id,status),
 KEY idx_kpi_company_status(company_id,status),
 KEY idx_kpi_process(process_id,process_step_id),
 KEY idx_kpi_metric(metric_key,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_kpi_definition_versions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 definition_id BIGINT UNSIGNED NOT NULL,
 version_number VARCHAR(32) NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'draft',
 calculation_method TEXT NOT NULL,
 calculation_json LONGTEXT NOT NULL,
 unit VARCHAR(40) NOT NULL,
 direction VARCHAR(24) NOT NULL,
 target_value DECIMAL(20,4) NOT NULL,
 warning_value DECIMAL(20,4) NOT NULL,
 critical_value DECIMAL(20,4) NOT NULL,
 prepared_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL,
 review_note TEXT NOT NULL,
 approval_note TEXT NOT NULL,
 evidence_note TEXT NOT NULL,
 effective_from DATE NOT NULL,
 published_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_kpi_version(definition_id,version_number),
 KEY idx_kpi_version_status(status,locked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_kpi_snapshots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 definition_id BIGINT UNSIGNED NOT NULL,
 version_id BIGINT UNSIGNED NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 company_id BIGINT UNSIGNED NULL,
 period_start DATE NOT NULL,
 period_end DATE NOT NULL,
 actual_value DECIMAL(20,4) NOT NULL,
 target_value DECIMAL(20,4) NOT NULL,
 status VARCHAR(32) NOT NULL,
 trend_direction VARCHAR(20) NOT NULL DEFAULT 'flat',
 forecast_value DECIMAL(20,4) NOT NULL,
 confidence_pct TINYINT UNSIGNED NOT NULL DEFAULT 50,
 evidence_json LONGTEXT NOT NULL,
 snapshot_key CHAR(64) NOT NULL UNIQUE,
 generated_by BIGINT UNSIGNED NOT NULL,
 generated_at DATETIME NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_kpi_snapshot_definition(definition_id,period_end),
 KEY idx_kpi_snapshot_entity(entity_id,period_end),
 KEY idx_kpi_snapshot_status(status,period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_scorecards (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 scorecard_number VARCHAR(64) NOT NULL UNIQUE,
 scorecard_name VARCHAR(190) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 company_id BIGINT UNSIGNED NULL,
 period_start DATE NOT NULL,
 period_end DATE NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'prepared',
 on_target_count INT UNSIGNED NOT NULL DEFAULT 0,
 warning_count INT UNSIGNED NOT NULL DEFAULT 0,
 critical_count INT UNSIGNED NOT NULL DEFAULT 0,
 results_json LONGTEXT NOT NULL,
 executive_summary TEXT NOT NULL,
 prepared_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL,
 review_note TEXT NOT NULL,
 approval_note TEXT NOT NULL,
 published_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_scorecard_entity_period(entity_id,period_end),
 KEY idx_scorecard_company_period(company_id,period_end),
 KEY idx_scorecard_status(status,period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_executive_reviews (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 review_number VARCHAR(64) NOT NULL UNIQUE,
 scorecard_id BIGINT UNSIGNED NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 company_id BIGINT UNSIGNED NOT NULL,
 review_type VARCHAR(50) NOT NULL,
 meeting_start DATETIME NOT NULL,
 meeting_end DATETIME NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'scheduled',
 agenda_json LONGTEXT NOT NULL,
 summary_text TEXT NOT NULL,
 prepared_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL,
 review_note TEXT NOT NULL,
 approval_note TEXT NOT NULL,
 calendar_event_id BIGINT UNSIGNED NULL,
 published_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_executive_review_entity(entity_id,status,meeting_start),
 KEY idx_executive_review_scorecard(scorecard_id,status),
 KEY idx_executive_review_calendar(calendar_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_executive_decisions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 decision_number VARCHAR(64) NOT NULL UNIQUE,
 review_id BIGINT UNSIGNED NOT NULL,
 goal_id BIGINT UNSIGNED NULL,
 kpi_definition_id BIGINT UNSIGNED NULL,
 decision_type VARCHAR(50) NOT NULL,
 title VARCHAR(190) NOT NULL,
 description TEXT NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'open',
 owner_user_id BIGINT UNSIGNED NOT NULL,
 due_at DATETIME NOT NULL,
 work_item_id BIGINT UNSIGNED NULL,
 calendar_event_id BIGINT UNSIGNED NULL,
 idempotency_key CHAR(64) NOT NULL UNIQUE,
 created_by BIGINT UNSIGNED NOT NULL,
 closed_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_executive_decision_review(review_id,status),
 KEY idx_executive_decision_owner(owner_user_id,status,due_at),
 KEY idx_executive_decision_work(work_item_id),
 KEY idx_executive_decision_calendar(calendar_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions(permission_key,area,action,description) VALUES
('executive_intelligence.view','executive_intelligence','view','View scoped goals, KPI definitions, scorecards, forecasts, reviews, and executive decisions'),
('executive_intelligence.create','executive_intelligence','create','Create governed goals, KPI definitions, scorecards, and executive reviews'),
('executive_intelligence.edit','executive_intelligence','edit','Edit permitted goals and record governed executive decisions'),
('executive_intelligence.snapshot','executive_intelligence','snapshot','Generate replay-safe KPI snapshots from approved visible evidence'),
('executive_intelligence.review','executive_intelligence','review','Independently review KPI definitions, scorecards, and executive reviews'),
('executive_intelligence.approve','executive_intelligence','approve','Approve and permanently publish KPI versions, scorecards, and executive reviews'),
('executive_intelligence.export','executive_intelligence','export','Export scoped executive performance evidence'),
('executive_intelligence.administer','executive_intelligence','administer','Administer enterprise goal, KPI, scorecard, forecast, and review governance');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.area='executive_intelligence' WHERE r.code='system_administrator';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('executive_intelligence.view','executive_intelligence.create','executive_intelligence.edit','executive_intelligence.snapshot','executive_intelligence.review','executive_intelligence.approve','executive_intelligence.export') WHERE r.code='executive';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.area='executive_intelligence' WHERE r.code='company_administrator';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('executive_intelligence.view','executive_intelligence.create','executive_intelligence.edit','executive_intelligence.snapshot','executive_intelligence.review','executive_intelligence.approve','executive_intelligence.export') WHERE r.code='procurement_manager';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('executive_intelligence.view','executive_intelligence.create','executive_intelligence.edit','executive_intelligence.snapshot') WHERE r.code='data_contributor';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('executive_intelligence.view','executive_intelligence.review','executive_intelligence.approve','executive_intelligence.export') WHERE r.code='reviewer';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('executive_intelligence.view','executive_intelligence.export') WHERE r.code='read_only';

SET @ei_preparer=(SELECT id FROM users WHERE status='active' ORDER BY id LIMIT 1);
SET @ei_reviewer=(SELECT id FROM users WHERE status='active' AND id<>@ei_preparer ORDER BY id LIMIT 1);
SET @ei_approver=(SELECT id FROM users WHERE status='active' AND id<>@ei_preparer AND id<>@ei_reviewer ORDER BY id LIMIT 1);
SET @ei_entity=(SELECT id FROM business_entities WHERE company_id IS NOT NULL AND status='active' ORDER BY id LIMIT 1);
SET @ei_company=(SELECT company_id FROM business_entities WHERE id=@ei_entity);
SET @ei_process=(SELECT id FROM business_processes WHERE status='published' ORDER BY id LIMIT 1);
SET @ei_process_version=(SELECT active_version_id FROM business_processes WHERE id=@ei_process);
SET @ei_step=(SELECT id FROM business_process_steps WHERE version_id=@ei_process_version AND step_type NOT IN('start','end') AND status='active' ORDER BY sort_order,id LIMIT 1);

INSERT IGNORE INTO enterprise_kpi_definitions(kpi_code,kpi_name,description,entity_id,company_id,source_module,metric_key,process_id,process_step_id,owner_user_id,data_owner_user_id,refresh_frequency,status,created_by)
SELECT 'KPI-PROC-SLA','Process SLA compliance','Percent of governed Section 25 process steps completed or active within their published SLA.',NULL,NULL,'process_mapping','process_sla_compliance_pct',@ei_process,@ei_step,@ei_preparer,@ei_reviewer,'daily','active',@ei_preparer
WHERE @ei_preparer IS NOT NULL AND @ei_reviewer IS NOT NULL;
INSERT IGNORE INTO enterprise_kpi_definitions(kpi_code,kpi_name,description,entity_id,company_id,source_module,metric_key,process_id,process_step_id,owner_user_id,data_owner_user_id,refresh_frequency,status,created_by)
SELECT 'KPI-CRITICAL-WORK','Critical open work','Open Section 26 critical work originating from the selected operating entity.',@ei_entity,@ei_company,'work_management','critical_open_work',@ei_process,@ei_step,@ei_preparer,@ei_reviewer,'hourly','active',@ei_preparer
WHERE @ei_entity IS NOT NULL AND @ei_preparer IS NOT NULL AND @ei_reviewer IS NOT NULL;
INSERT IGNORE INTO enterprise_kpi_definitions(kpi_code,kpi_name,description,entity_id,company_id,source_module,metric_key,process_id,process_step_id,owner_user_id,data_owner_user_id,refresh_frequency,status,created_by)
SELECT 'KPI-PROC-EXCEPTIONS','Open process exceptions','Open Section 25 exceptions across visible governed process instances.',NULL,NULL,'process_mapping','process_open_exceptions',@ei_process,NULL,@ei_preparer,@ei_reviewer,'hourly','active',@ei_preparer
WHERE @ei_preparer IS NOT NULL AND @ei_reviewer IS NOT NULL;

SET @ei_kpi_sla=(SELECT id FROM enterprise_kpi_definitions WHERE kpi_code='KPI-PROC-SLA');
SET @ei_kpi_work=(SELECT id FROM enterprise_kpi_definitions WHERE kpi_code='KPI-CRITICAL-WORK');
SET @ei_kpi_exceptions=(SELECT id FROM enterprise_kpi_definitions WHERE kpi_code='KPI-PROC-EXCEPTIONS');
INSERT IGNORE INTO enterprise_kpi_definition_versions(definition_id,version_number,status,calculation_method,calculation_json,unit,direction,target_value,warning_value,critical_value,prepared_by,reviewed_by,approved_by,review_note,approval_note,evidence_note,effective_from,published_at,locked_at)
SELECT @ei_kpi_sla,'1.0','published','Deterministic Section 25 step-instance SLA calculation.','{"metric_key":"process_sla_compliance_pct","scope":"approved_visible_records"}','%','higher_better',95,90,80,@ei_preparer,@ei_reviewer,@ei_approver,'Method and process binding independently reviewed.','Approved for governed executive scorecards.','Published KPI version bound to the active Section 25 process and step.',CURRENT_DATE,NOW(),NOW()
WHERE @ei_kpi_sla IS NOT NULL AND @ei_approver IS NOT NULL;
INSERT IGNORE INTO enterprise_kpi_definition_versions(definition_id,version_number,status,calculation_method,calculation_json,unit,direction,target_value,warning_value,critical_value,prepared_by,reviewed_by,approved_by,review_note,approval_note,evidence_note,effective_from,published_at,locked_at)
SELECT @ei_kpi_work,'1.0','published','Deterministic Section 26 critical-work count scoped by originating entity.','{"metric_key":"critical_open_work","scope":"originating_entity"}','count','lower_better',1,3,6,@ei_preparer,@ei_reviewer,@ei_approver,'Method and process linkage independently reviewed.','Approved for governed executive scorecards.','Published KPI version preserves Section 25 runtime references when decisions create work.',CURRENT_DATE,NOW(),NOW()
WHERE @ei_kpi_work IS NOT NULL AND @ei_approver IS NOT NULL;
INSERT IGNORE INTO enterprise_kpi_definition_versions(definition_id,version_number,status,calculation_method,calculation_json,unit,direction,target_value,warning_value,critical_value,prepared_by,reviewed_by,approved_by,review_note,approval_note,evidence_note,effective_from,published_at,locked_at)
SELECT @ei_kpi_exceptions,'1.0','published','Deterministic count of unresolved Section 25 process exceptions.','{"metric_key":"process_open_exceptions","scope":"approved_visible_records"}','count','lower_better',0,2,5,@ei_preparer,@ei_reviewer,@ei_approver,'Exception scope independently reviewed.','Approved for governed executive scorecards.','Published KPI version reads immutable process events and current exception state.',CURRENT_DATE,NOW(),NOW()
WHERE @ei_kpi_exceptions IS NOT NULL AND @ei_approver IS NOT NULL;

UPDATE enterprise_kpi_definitions d SET active_version_id=(SELECT v.id FROM enterprise_kpi_definition_versions v WHERE v.definition_id=d.id AND v.status='published' ORDER BY v.id DESC LIMIT 1) WHERE d.active_version_id IS NULL;

INSERT IGNORE INTO enterprise_goals(goal_number,parent_goal_id,entity_id,company_id,goal_name,description,goal_type,unit,direction,target_value,period_start,period_end,owner_user_id,reviewer_user_id,executive_sponsor_user_id,status,confidence_pct,evidence_note,created_by)
SELECT 'GOAL-2026-PROC-SLA',NULL,@ei_entity,@ei_company,'Maintain process SLA compliance','Maintain governed process execution within published Section 25 step SLAs.','operational','%','higher_better',95,CURRENT_DATE,DATE_ADD(CURRENT_DATE,INTERVAL 90 DAY),@ei_preparer,@ei_reviewer,@ei_approver,'active',65,'Measured from Section 25 process-step runtime evidence and connected Section 26 work.',@ei_preparer
WHERE @ei_entity IS NOT NULL AND @ei_approver IS NOT NULL;
SET @ei_goal=(SELECT id FROM enterprise_goals WHERE goal_number='GOAL-2026-PROC-SLA');
INSERT IGNORE INTO enterprise_goal_links(goal_id,link_type,source_id,source_key,relationship_label,created_by)
SELECT @ei_goal,'kpi_definition',@ei_kpi_sla,SHA2(CONCAT(@ei_goal,'|kpi_definition|',@ei_kpi_sla),256),'Primary KPI',@ei_preparer WHERE @ei_goal IS NOT NULL AND @ei_kpi_sla IS NOT NULL;
INSERT IGNORE INTO enterprise_goal_links(goal_id,link_type,source_id,source_key,relationship_label,created_by)
SELECT @ei_goal,'process',@ei_process,SHA2(CONCAT(@ei_goal,'|process|',@ei_process),256),'Governing process',@ei_preparer WHERE @ei_goal IS NOT NULL AND @ei_process IS NOT NULL;
INSERT IGNORE INTO enterprise_goal_links(goal_id,link_type,source_id,source_key,relationship_label,created_by)
SELECT @ei_goal,'process_step',@ei_step,SHA2(CONCAT(@ei_goal,'|process_step|',@ei_step),256),'Measured process step',@ei_preparer WHERE @ei_goal IS NOT NULL AND @ei_step IS NOT NULL;

INSERT INTO schema_migrations(version,description,checksum_sha256)
VALUES('5.7-section28','Executive command center, goals, process-aware KPI governance, immutable snapshots, scorecards, forecasts, reviews, and governed decisions',NULL)
ON DUPLICATE KEY UPDATE description=VALUES(description);
