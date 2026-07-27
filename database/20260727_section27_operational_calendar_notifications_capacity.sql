-- Gruber Procurement Intelligence
-- Section 27: Enterprise Operational Calendar, Notifications, Workload Capacity & Delegation
-- Apply after Sections 11 through 26. Idempotent for MySQL 8.0 and MariaDB 10.11.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS enterprise_calendar_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_number VARCHAR(64) NOT NULL UNIQUE,
 source_key CHAR(64) NOT NULL UNIQUE,
 source_module VARCHAR(80) NOT NULL,
 source_type VARCHAR(100) NOT NULL,
 source_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
 entity_id BIGINT UNSIGNED NOT NULL,
 company_id BIGINT UNSIGNED NOT NULL,
 owner_user_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(190) NOT NULL,
 description TEXT NOT NULL,
 event_type VARCHAR(50) NOT NULL,
 visibility VARCHAR(20) NOT NULL DEFAULT 'company',
 participant_user_ids_json LONGTEXT NOT NULL,
 starts_at DATETIME NOT NULL,
 ends_at DATETIME NOT NULL,
 all_day BOOLEAN NOT NULL DEFAULT FALSE,
 status VARCHAR(32) NOT NULL DEFAULT 'scheduled',
 location VARCHAR(240) NOT NULL DEFAULT '',
 required_permission VARCHAR(160) NOT NULL DEFAULT 'operational_calendar.view',
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_calendar_company_time(company_id,starts_at,ends_at),
 KEY idx_calendar_entity_time(entity_id,starts_at),
 KEY idx_calendar_owner_time(owner_user_id,starts_at),
 KEY idx_calendar_source(source_module,source_type,source_id),
 KEY idx_calendar_status(status,starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_notification_preferences (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL UNIQUE,
 email_enabled BOOLEAN NOT NULL DEFAULT TRUE,
 in_app_enabled BOOLEAN NOT NULL DEFAULT TRUE,
 daily_digest_enabled BOOLEAN NOT NULL DEFAULT TRUE,
 weekly_digest_enabled BOOLEAN NOT NULL DEFAULT TRUE,
 morning_summary_enabled BOOLEAN NOT NULL DEFAULT TRUE,
 end_of_day_summary_enabled BOOLEAN NOT NULL DEFAULT TRUE,
 quiet_hours_start TIME NULL,
 quiet_hours_end TIME NULL,
 timezone VARCHAR(100) NOT NULL DEFAULT 'America/Phoenix',
 minimum_severity VARCHAR(20) NOT NULL DEFAULT 'info',
 channel_settings_json LONGTEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_notification_preferences_timezone(timezone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_notification_queue (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 work_item_id BIGINT UNSIGNED NULL,
 calendar_event_id BIGINT UNSIGNED NULL,
 notification_type VARCHAR(80) NOT NULL,
 severity VARCHAR(20) NOT NULL DEFAULT 'info',
 title VARCHAR(190) NOT NULL,
 message TEXT NOT NULL,
 channel VARCHAR(30) NOT NULL DEFAULT 'in_app',
 status VARCHAR(32) NOT NULL DEFAULT 'queued',
 dedupe_key CHAR(64) NOT NULL UNIQUE,
 scheduled_at DATETIME NOT NULL,
 sent_at DATETIME NULL,
 read_at DATETIME NULL,
 delivery_receipt_json LONGTEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_notification_user_status(user_id,status,scheduled_at),
 KEY idx_notification_entity(entity_id,scheduled_at),
 KEY idx_notification_work(work_item_id),
 KEY idx_notification_event(calendar_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_capacity_profiles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 company_id BIGINT UNSIGNED NOT NULL,
 weekly_capacity_hours DECIMAL(8,2) NOT NULL DEFAULT 40,
 daily_capacity_hours DECIMAL(8,2) NOT NULL DEFAULT 8,
 max_active_items INT UNSIGNED NOT NULL DEFAULT 15,
 utilization_warning_pct DECIMAL(6,2) NOT NULL DEFAULT 75,
 utilization_critical_pct DECIMAL(6,2) NOT NULL DEFAULT 95,
 timezone VARCHAR(100) NOT NULL DEFAULT 'America/Phoenix',
 status VARCHAR(32) NOT NULL DEFAULT 'active',
 effective_from DATE NOT NULL,
 effective_to DATE NULL,
 evidence_note TEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_capacity_user_company(user_id,company_id),
 KEY idx_capacity_company_status(company_id,status),
 KEY idx_capacity_effective(effective_from,effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_delegations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 delegation_number VARCHAR(64) NOT NULL UNIQUE,
 source_user_id BIGINT UNSIGNED NOT NULL,
 delegate_user_id BIGINT UNSIGNED NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 permission_keys_json LONGTEXT NOT NULL,
 starts_at DATETIME NOT NULL,
 ends_at DATETIME NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'planned',
 reason TEXT NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NULL,
 approved_by BIGINT UNSIGNED NULL,
 activated_at DATETIME NULL,
 revoked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_delegation_source(source_user_id,status,starts_at,ends_at),
 KEY idx_delegation_delegate(delegate_user_id,status,starts_at,ends_at),
 KEY idx_delegation_entity(entity_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_digest_runs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 digest_type VARCHAR(32) NOT NULL,
 period_start DATETIME NOT NULL,
 period_end DATETIME NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'generated',
 item_count INT UNSIGNED NOT NULL DEFAULT 0,
 event_count INT UNSIGNED NOT NULL DEFAULT 0,
 notification_count INT UNSIGNED NOT NULL DEFAULT 0,
 summary_text TEXT NOT NULL,
 idempotency_key CHAR(64) NOT NULL UNIQUE,
 generated_at DATETIME NOT NULL,
 delivered_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_digest_user_period(user_id,digest_type,period_start),
 KEY idx_digest_status(status,generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_calendar_subscriptions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 subscription_name VARCHAR(190) NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE,
 token_prefix VARCHAR(12) NOT NULL,
 scope_json LONGTEXT NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'active',
 expires_at DATETIME NOT NULL,
 last_accessed_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 revoked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_calendar_subscription_user(user_id,status,expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_operating_cadences (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 cadence_code VARCHAR(80) NOT NULL UNIQUE,
 cadence_name VARCHAR(190) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 role_code VARCHAR(80) NOT NULL,
 cadence_type VARCHAR(32) NOT NULL,
 schedule_json LONGTEXT NOT NULL,
 timezone VARCHAR(100) NOT NULL DEFAULT 'America/Phoenix',
 agenda_json LONGTEXT NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'active',
 sort_order INT NOT NULL DEFAULT 10,
 evidence_note TEXT NOT NULL,
 prepared_by BIGINT UNSIGNED NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL,
 locked_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_cadence_entity_status(entity_id,status,sort_order),
 KEY idx_cadence_role(role_code,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions(permission_key,area,action,description) VALUES
('operational_calendar.view','operational_calendar','view','View scoped operational calendars, notifications, capacity, delegation, and digests'),
('operational_calendar.create','operational_calendar','create','Create governed operational calendar events'),
('operational_calendar.edit','operational_calendar','edit','Edit permitted operational calendar events'),
('operational_calendar.delegate','operational_calendar','delegate','Create governed temporary delegation coverage'),
('operational_calendar.manage_capacity','operational_calendar','manage_capacity','Manage workload capacity profiles'),
('operational_calendar.manage_notifications','operational_calendar','manage_notifications','Manage personal notification and digest preferences'),
('operational_calendar.export','operational_calendar','export','Export scoped operational calendar data'),
('operational_calendar.administer','operational_calendar','administer','Administer enterprise calendar, cadence, capacity, delegation, and delivery governance');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.area='operational_calendar'
WHERE r.code='system_administrator';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('operational_calendar.view','operational_calendar.export')
WHERE r.code IN('executive','read_only');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('operational_calendar.view','operational_calendar.create','operational_calendar.edit','operational_calendar.delegate','operational_calendar.manage_capacity','operational_calendar.manage_notifications','operational_calendar.export','operational_calendar.administer')
WHERE r.code='company_administrator';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('operational_calendar.view','operational_calendar.create','operational_calendar.edit','operational_calendar.delegate','operational_calendar.manage_capacity','operational_calendar.manage_notifications','operational_calendar.export')
WHERE r.code='procurement_manager';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('operational_calendar.view','operational_calendar.create','operational_calendar.manage_notifications')
WHERE r.code='data_contributor';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN('operational_calendar.view','operational_calendar.create','operational_calendar.edit','operational_calendar.delegate','operational_calendar.manage_notifications','operational_calendar.export')
WHERE r.code='reviewer';

INSERT IGNORE INTO enterprise_notification_preferences(user_id,channel_settings_json)
SELECT id,'{"email":"adapter_gated","in_app":"active"}' FROM users WHERE status='active';

INSERT IGNORE INTO enterprise_capacity_profiles(user_id,company_id,weekly_capacity_hours,daily_capacity_hours,max_active_items,utilization_warning_pct,utilization_critical_pct,timezone,status,effective_from,evidence_note)
SELECT cm.user_id,cm.company_id,40,8,15,75,95,'America/Phoenix','active',CURRENT_DATE,'Section 27 seeded a governed starting capacity profile from active company membership.'
FROM company_memberships cm JOIN users u ON u.id=cm.user_id
WHERE cm.membership_status='active' AND u.status='active';

INSERT IGNORE INTO enterprise_operating_cadences(cadence_code,cadence_name,entity_id,role_code,cadence_type,schedule_json,timezone,agenda_json,status,sort_order,evidence_note,prepared_by,reviewed_by,approved_by,locked_at)
SELECT 'CADENCE-DAILY-OPS','Daily procurement operations review',NULL,'procurement_manager','daily','{"time":"08:30","weekdays":[1,2,3,4,5]}','America/Phoenix','["Critical work","Supplier commitments","Exceptions","Approvals","Capacity"]','active',10,'Governed daily operating cadence.',a.id,r.id,e.id,NOW()
FROM users a JOIN users r JOIN users e
WHERE a.status='active' AND r.status='active' AND e.status='active' AND a.id<>r.id AND a.id<>e.id AND r.id<>e.id ORDER BY a.id,r.id,e.id LIMIT 1;
INSERT IGNORE INTO enterprise_operating_cadences(cadence_code,cadence_name,entity_id,role_code,cadence_type,schedule_json,timezone,agenda_json,status,sort_order,evidence_note,prepared_by,reviewed_by,approved_by,locked_at)
SELECT 'CADENCE-WEEKLY-LEADERSHIP','Weekly procurement leadership review',NULL,'executive','weekly','{"weekday":5,"time":"14:00"}','America/Phoenix','["SLA compliance","Capacity","Supplier risk","Savings","Escalations"]','active',20,'Governed weekly leadership cadence.',a.id,r.id,e.id,NOW()
FROM users a JOIN users r JOIN users e
WHERE a.status='active' AND r.status='active' AND e.status='active' AND a.id<>r.id AND a.id<>e.id AND r.id<>e.id ORDER BY a.id,r.id,e.id LIMIT 1;
INSERT IGNORE INTO enterprise_operating_cadences(cadence_code,cadence_name,entity_id,role_code,cadence_type,schedule_json,timezone,agenda_json,status,sort_order,evidence_note,prepared_by,reviewed_by,approved_by,locked_at)
SELECT 'CADENCE-MONTHLY-CAPACITY','Monthly workload and capacity review',NULL,'company_administrator','monthly','{"day":1,"time":"10:00"}','America/Phoenix','["Capacity profiles","Delegation coverage","Backlog aging","Automation load"]','active',30,'Governed monthly capacity review.',a.id,r.id,e.id,NOW()
FROM users a JOIN users r JOIN users e
WHERE a.status='active' AND r.status='active' AND e.status='active' AND a.id<>r.id AND a.id<>e.id AND r.id<>e.id ORDER BY a.id,r.id,e.id LIMIT 1;

INSERT IGNORE INTO enterprise_calendar_events(event_number,source_key,source_module,source_type,source_id,entity_id,company_id,owner_user_id,title,description,event_type,visibility,participant_user_ids_json,starts_at,ends_at,all_day,status,location,required_permission,created_by)
SELECT CONCAT('CAL-WORK-',w.id),SHA2(CONCAT('work_item|',w.id),256),'work_management','work_item',w.id,w.entity_id,w.company_id,COALESCE(w.assigned_user_id,w.created_by),CONCAT(w.work_number,' · ',w.title),w.description,'work_due','team',CONCAT('[',COALESCE(w.assigned_user_id,w.created_by),']'),DATE_SUB(w.due_at,INTERVAL 30 MINUTE),w.due_at,FALSE,'scheduled','',w.required_permission,w.created_by
FROM enterprise_work_items w
WHERE w.due_at IS NOT NULL AND w.status NOT IN('completed','cancelled');

INSERT IGNORE INTO schema_migrations(version,description)
VALUES('5.6-section27','Section 27 enterprise operational calendar, notifications, workload capacity, delegation, digests, calendar subscriptions, and operating cadence');