-- Gruber Procurement Intelligence
-- Section 30: Enterprise Policy, SOP, Knowledge & Change Adoption
-- Apply after Sections 11 through 29. Idempotent for MySQL 8.0 and MariaDB 10.11.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS enterprise_policy_documents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 document_number VARCHAR(64) NOT NULL UNIQUE,
 title VARCHAR(190) NOT NULL,
 document_type VARCHAR(40) NOT NULL DEFAULT 'policy',
 entity_id BIGINT UNSIGNED NULL,
 company_id BIGINT UNSIGNED NULL,
 owner_user_id BIGINT UNSIGNED NOT NULL,
 reviewer_user_id BIGINT UNSIGNED NOT NULL,
 approver_user_id BIGINT UNSIGNED NOT NULL,
 purpose TEXT NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'draft',
 current_version_id BIGINT UNSIGNED NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_policy_document_entity(entity_id,status),
 KEY idx_policy_document_company(company_id,status),
 KEY idx_policy_document_current(current_version_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_policy_versions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 document_id BIGINT UNSIGNED NOT NULL,
 version_number VARCHAR(40) NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'draft',
 effective_from DATE NOT NULL,
 effective_to DATE NULL,
 content LONGTEXT NOT NULL,
 change_summary TEXT NOT NULL,
 content_hash CHAR(64) NOT NULL,
 source_initiative_id BIGINT UNSIGNED NULL,
 process_id BIGINT UNSIGNED NULL,
 process_version_id BIGINT UNSIGNED NULL,
 process_step_id BIGINT UNSIGNED NULL,
 prepared_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL,
 review_note TEXT NOT NULL,
 approval_note TEXT NOT NULL,
 submitted_at DATETIME NULL,
 reviewed_at DATETIME NULL,
 published_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_policy_version(document_id,version_number),
 KEY idx_policy_version_status(document_id,status,effective_from),
 KEY idx_policy_version_process(process_id,process_version_id,process_step_id),
 KEY idx_policy_version_initiative(source_initiative_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_policy_links (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 version_id BIGINT UNSIGNED NOT NULL,
 link_type VARCHAR(50) NOT NULL,
 source_id BIGINT UNSIGNED NOT NULL,
 relationship_label VARCHAR(190) NOT NULL,
 link_key CHAR(64) NOT NULL UNIQUE,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_policy_link_version(version_id,link_type),
 KEY idx_policy_link_source(link_type,source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_change_impacts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 version_id BIGINT UNSIGNED NOT NULL,
 initiative_id BIGINT UNSIGNED NOT NULL,
 process_id BIGINT UNSIGNED NULL,
 process_step_id BIGINT UNSIGNED NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 company_id BIGINT UNSIGNED NOT NULL,
 role_code VARCHAR(80) NOT NULL,
 system_code VARCHAR(80) NOT NULL,
 impact_level VARCHAR(20) NOT NULL DEFAULT 'medium',
 impact_description TEXT NOT NULL,
 required_action TEXT NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'prepared',
 prepared_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL,
 review_note TEXT NOT NULL,
 approval_note TEXT NOT NULL,
 reviewed_at DATETIME NULL,
 approved_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_change_impact_version(version_id,status),
 KEY idx_change_impact_company(company_id,entity_id,status),
 KEY idx_change_impact_process(process_id,process_step_id),
 KEY idx_change_impact_initiative(initiative_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_training_campaigns (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 campaign_number VARCHAR(64) NOT NULL UNIQUE,
 version_id BIGINT UNSIGNED NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 company_id BIGINT UNSIGNED NOT NULL,
 campaign_name VARCHAR(190) NOT NULL,
 delivery_mode VARCHAR(40) NOT NULL DEFAULT 'self_paced',
 required_score TINYINT UNSIGNED NOT NULL DEFAULT 80,
 due_at DATETIME NOT NULL,
 renewal_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 status VARCHAR(32) NOT NULL DEFAULT 'scheduled',
 owner_user_id BIGINT UNSIGNED NOT NULL,
 reviewer_user_id BIGINT UNSIGNED NOT NULL,
 approver_user_id BIGINT UNSIGNED NOT NULL,
 evidence_note TEXT NOT NULL,
 source_key CHAR(64) NOT NULL UNIQUE,
 published_at DATETIME NULL,
 closed_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_training_campaign_version(version_id,status,due_at),
 KEY idx_training_campaign_company(company_id,entity_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_training_assignments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 campaign_id BIGINT UNSIGNED NOT NULL,
 assignment_number VARCHAR(64) NOT NULL UNIQUE,
 assigned_user_id BIGINT UNSIGNED NOT NULL,
 assigned_role_code VARCHAR(80) NOT NULL,
 validator_user_id BIGINT UNSIGNED NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'assigned',
 due_at DATETIME NOT NULL,
 work_item_id BIGINT UNSIGNED NULL,
 calendar_event_id BIGINT UNSIGNED NULL,
 process_instance_id BIGINT UNSIGNED NULL,
 step_instance_id BIGINT UNSIGNED NULL,
 source_key CHAR(64) NOT NULL UNIQUE,
 assigned_by BIGINT UNSIGNED NOT NULL,
 completed_at DATETIME NULL,
 renewal_due_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_training_assignment_campaign(campaign_id,status,due_at),
 KEY idx_training_assignment_user(assigned_user_id,status,due_at),
 KEY idx_training_assignment_work(work_item_id),
 KEY idx_training_assignment_calendar(calendar_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_training_attestations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 assignment_id BIGINT UNSIGNED NOT NULL,
 version_id BIGINT UNSIGNED NOT NULL,
 attestation_number VARCHAR(64) NOT NULL UNIQUE,
 policy_hash CHAR(64) NOT NULL,
 acknowledgement_text TEXT NOT NULL,
 quiz_score DECIMAL(6,2) NOT NULL DEFAULT 0,
 passed TINYINT(1) NOT NULL DEFAULT 0,
 competency_status VARCHAR(32) NOT NULL DEFAULT 'pending_validation',
 status VARCHAR(32) NOT NULL DEFAULT 'submitted',
 submitted_by BIGINT UNSIGNED NOT NULL,
 validator_user_id BIGINT UNSIGNED NOT NULL,
 evidence_note TEXT NOT NULL,
 validation_note TEXT NOT NULL,
 idempotency_key CHAR(64) NOT NULL UNIQUE,
 submitted_at DATETIME NOT NULL,
 validated_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_attestation_assignment(assignment_id,status),
 KEY idx_attestation_version(version_id,status),
 KEY idx_attestation_validator(validator_user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_policy_waivers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 assignment_id BIGINT UNSIGNED NOT NULL,
 version_id BIGINT UNSIGNED NOT NULL,
 waiver_number VARCHAR(64) NOT NULL UNIQUE,
 reason TEXT NOT NULL,
 compensating_control TEXT NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'submitted',
 requested_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL,
 review_note TEXT NOT NULL,
 approval_note TEXT NOT NULL,
 expires_at DATETIME NOT NULL,
 requested_at DATETIME NOT NULL,
 reviewed_at DATETIME NULL,
 approved_at DATETIME NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_policy_waiver_assignment(assignment_id,status,expires_at),
 KEY idx_policy_waiver_version(version_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_adoption_snapshots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 campaign_id BIGINT UNSIGNED NOT NULL,
 snapshot_date DATE NOT NULL,
 assigned_count INT UNSIGNED NOT NULL DEFAULT 0,
 completed_count INT UNSIGNED NOT NULL DEFAULT 0,
 validated_count INT UNSIGNED NOT NULL DEFAULT 0,
 passed_count INT UNSIGNED NOT NULL DEFAULT 0,
 waived_count INT UNSIGNED NOT NULL DEFAULT 0,
 overdue_count INT UNSIGNED NOT NULL DEFAULT 0,
 adoption_rate DECIMAL(6,2) NOT NULL DEFAULT 0,
 pass_rate DECIMAL(6,2) NOT NULL DEFAULT 0,
 evidence_note TEXT NOT NULL,
 snapshot_key CHAR(64) NOT NULL UNIQUE,
 created_by BIGINT UNSIGNED NOT NULL,
 locked_at DATETIME NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_adoption_snapshot_campaign(campaign_id,snapshot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_policy_evidence_manifests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 version_id BIGINT UNSIGNED NOT NULL,
 manifest_number VARCHAR(64) NOT NULL UNIQUE,
 manifest_json LONGTEXT NOT NULL,
 manifest_hash CHAR(64) NOT NULL UNIQUE,
 evidence_note TEXT NOT NULL,
 generated_by BIGINT UNSIGNED NOT NULL,
 generated_at DATETIME NOT NULL,
 locked_at DATETIME NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_policy_manifest_version(version_id,generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions(permission_key,area,action,description) VALUES
('knowledge_adoption.view','knowledge_adoption','view','View scoped policies, versions, training, attestations, waivers, and adoption evidence'),
('knowledge_adoption.create','knowledge_adoption','create','Create governed policy documents, versions, impacts, campaigns, and evidence'),
('knowledge_adoption.edit','knowledge_adoption','edit','Edit permitted draft policy and adoption records'),
('knowledge_adoption.submit','knowledge_adoption','submit','Submit policy versions, impacts, attestations, and waivers for review'),
('knowledge_adoption.review','knowledge_adoption','review','Independently review policy versions, impacts, competency evidence, and waivers'),
('knowledge_adoption.approve','knowledge_adoption','approve','Approve change impacts and policy waivers'),
('knowledge_adoption.publish','knowledge_adoption','publish','Publish immutable policy and SOP versions'),
('knowledge_adoption.assign_training','knowledge_adoption','assign_training','Create role-targeted training assignments with work and calendar handoffs'),
('knowledge_adoption.attest','knowledge_adoption','attest','Submit learner acknowledgements, quizzes, and competency evidence'),
('knowledge_adoption.manage_waivers','knowledge_adoption','manage_waivers','Request and administer time-limited waivers and compensating controls'),
('knowledge_adoption.snapshot','knowledge_adoption','snapshot','Create immutable adoption and compliance snapshots'),
('knowledge_adoption.export','knowledge_adoption','export','Export scoped adoption records and evidence manifests'),
('knowledge_adoption.administer','knowledge_adoption','administer','Administer enterprise policy, knowledge, training, and adoption governance');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.area='knowledge_adoption' WHERE r.code='system_administrator';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('knowledge_adoption.view','knowledge_adoption.create','knowledge_adoption.edit','knowledge_adoption.submit','knowledge_adoption.review','knowledge_adoption.approve','knowledge_adoption.publish','knowledge_adoption.assign_training','knowledge_adoption.manage_waivers','knowledge_adoption.snapshot','knowledge_adoption.export') WHERE r.code='executive';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.area='knowledge_adoption' WHERE r.code='company_administrator';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('knowledge_adoption.view','knowledge_adoption.create','knowledge_adoption.edit','knowledge_adoption.submit','knowledge_adoption.review','knowledge_adoption.approve','knowledge_adoption.publish','knowledge_adoption.assign_training','knowledge_adoption.manage_waivers','knowledge_adoption.snapshot','knowledge_adoption.export') WHERE r.code='procurement_manager';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('knowledge_adoption.view','knowledge_adoption.create','knowledge_adoption.edit','knowledge_adoption.submit','knowledge_adoption.attest','knowledge_adoption.manage_waivers') WHERE r.code='data_contributor';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('knowledge_adoption.view','knowledge_adoption.review','knowledge_adoption.approve','knowledge_adoption.publish','knowledge_adoption.manage_waivers','knowledge_adoption.snapshot','knowledge_adoption.export') WHERE r.code='reviewer';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('knowledge_adoption.view','knowledge_adoption.export') WHERE r.code='read_only';

INSERT IGNORE INTO schema_migrations(version,description,applied_at)
VALUES('5.9-section30','Enterprise policy, SOP, knowledge, training, attestation, waiver, adoption, and evidence governance',NOW());
