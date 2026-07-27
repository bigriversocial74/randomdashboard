-- Gruber Procurement Intelligence
-- Section 26: Enterprise Work Management, Exception Command Center & Automation Rules
-- Apply after Version 3 and Sections 11 through 25 migrations.
-- Idempotent for MySQL 8.0 and MariaDB 10.11.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS enterprise_work_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_number VARCHAR(64) NOT NULL UNIQUE,
    source_key CHAR(64) NOT NULL UNIQUE,
    source_module VARCHAR(80) NOT NULL,
    source_type VARCHAR(80) NOT NULL,
    source_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    entity_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    process_instance_id BIGINT UNSIGNED NULL,
    step_instance_id BIGINT UNSIGNED NULL,
    work_type VARCHAR(80) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority VARCHAR(20) NOT NULL DEFAULT 'medium',
    status VARCHAR(32) NOT NULL DEFAULT 'open',
    assigned_entity_id BIGINT UNSIGNED NULL,
    assigned_user_id BIGINT UNSIGNED NULL,
    assigned_role_code VARCHAR(64) NULL,
    reviewer_id BIGINT UNSIGNED NULL,
    approver_id BIGINT UNSIGNED NULL,
    required_permission VARCHAR(120) NOT NULL DEFAULT 'platform.view',
    evidence_required BOOLEAN NOT NULL DEFAULT TRUE,
    due_at DATETIME NOT NULL,
    warning_at DATETIME NULL,
    escalation_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
    automation_rule_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    completed_by BIGINT UNSIGNED NULL,
    claimed_at DATETIME NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lock_version BIGINT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_work_entity_status (entity_id,status,due_at),
    KEY idx_work_company_status (company_id,status,due_at),
    KEY idx_work_assignee (assigned_user_id,status,due_at),
    KEY idx_work_role (assigned_role_code,status,due_at),
    KEY idx_work_reviewer (reviewer_id,status,due_at),
    KEY idx_work_approver (approver_id,status,due_at),
    KEY idx_work_priority_due (priority,status,due_at),
    KEY idx_work_source (source_module,source_type,source_id),
    KEY idx_work_process (process_instance_id,step_instance_id),
    CONSTRAINT fk_work_entity FOREIGN KEY(entity_id) REFERENCES business_entities(id),
    CONSTRAINT fk_work_company FOREIGN KEY(company_id) REFERENCES companies(id),
    CONSTRAINT fk_work_assigned_entity FOREIGN KEY(assigned_entity_id) REFERENCES business_entities(id),
    CONSTRAINT fk_work_assigned_user FOREIGN KEY(assigned_user_id) REFERENCES users(id),
    CONSTRAINT fk_work_reviewer FOREIGN KEY(reviewer_id) REFERENCES users(id),
    CONSTRAINT fk_work_approver FOREIGN KEY(approver_id) REFERENCES users(id),
    CONSTRAINT fk_work_creator FOREIGN KEY(created_by) REFERENCES users(id),
    CONSTRAINT fk_work_completer FOREIGN KEY(completed_by) REFERENCES users(id),
    CONSTRAINT fk_work_process_instance FOREIGN KEY(process_instance_id) REFERENCES business_process_instances(id),
    CONSTRAINT fk_work_step_instance FOREIGN KEY(step_instance_id) REFERENCES business_process_step_instances(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_work_item_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_item_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    from_status VARCHAR(32) NULL,
    to_status VARCHAR(32) NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    assigned_entity_id BIGINT UNSIGNED NULL,
    assigned_user_id BIGINT UNSIGNED NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    evidence_note TEXT NOT NULL,
    metadata_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_work_event_item (work_item_id,created_at),
    KEY idx_work_event_type (event_type,created_at),
    KEY idx_work_event_actor (actor_user_id,created_at),
    CONSTRAINT fk_work_event_item FOREIGN KEY(work_item_id) REFERENCES enterprise_work_items(id),
    CONSTRAINT fk_work_event_actor FOREIGN KEY(actor_user_id) REFERENCES users(id),
    CONSTRAINT fk_work_event_entity FOREIGN KEY(assigned_entity_id) REFERENCES business_entities(id),
    CONSTRAINT fk_work_event_assignee FOREIGN KEY(assigned_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_work_routing_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_code VARCHAR(80) NOT NULL UNIQUE,
    rule_name VARCHAR(180) NOT NULL,
    source_module VARCHAR(80) NOT NULL,
    work_type VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    assigned_role_code VARCHAR(64) NOT NULL,
    shared_service_entity_code VARCHAR(80) NULL,
    priority_override VARCHAR(20) NULL,
    sla_minutes INT UNSIGNED NOT NULL DEFAULT 480,
    reviewer_role_code VARCHAR(64) NULL,
    approver_role_code VARCHAR(64) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    sort_order INT UNSIGNED NOT NULL DEFAULT 100,
    evidence_note TEXT NOT NULL,
    prepared_by BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    locked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_routing_match (source_module,work_type,status,sort_order),
    KEY idx_routing_entity (entity_id,status),
    CONSTRAINT fk_routing_entity FOREIGN KEY(entity_id) REFERENCES business_entities(id),
    CONSTRAINT fk_routing_preparer FOREIGN KEY(prepared_by) REFERENCES users(id),
    CONSTRAINT fk_routing_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id),
    CONSTRAINT fk_routing_approver FOREIGN KEY(approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_automation_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_code VARCHAR(80) NOT NULL UNIQUE,
    rule_name VARCHAR(180) NOT NULL,
    trigger_event VARCHAR(120) NOT NULL,
    source_module VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    active_version_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_automation_rule_trigger (trigger_event,status),
    KEY idx_automation_rule_entity (entity_id,status),
    CONSTRAINT fk_automation_rule_entity FOREIGN KEY(entity_id) REFERENCES business_entities(id),
    CONSTRAINT fk_automation_rule_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_automation_rule_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_id BIGINT UNSIGNED NOT NULL,
    version_number VARCHAR(32) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    condition_json LONGTEXT NOT NULL,
    action_json LONGTEXT NOT NULL,
    idempotency_window_seconds INT UNSIGNED NOT NULL DEFAULT 86400,
    prepared_by BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    review_note TEXT NOT NULL,
    approval_note TEXT NOT NULL,
    evidence_note TEXT NOT NULL,
    published_at DATETIME NULL,
    locked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_automation_version (rule_id,version_number),
    KEY idx_automation_version_status (status,locked_at),
    CONSTRAINT fk_automation_version_rule FOREIGN KEY(rule_id) REFERENCES enterprise_automation_rules(id),
    CONSTRAINT fk_automation_version_preparer FOREIGN KEY(prepared_by) REFERENCES users(id),
    CONSTRAINT fk_automation_version_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id),
    CONSTRAINT fk_automation_version_approver FOREIGN KEY(approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_automation_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_id BIGINT UNSIGNED NOT NULL,
    version_id BIGINT UNSIGNED NOT NULL,
    source_key VARCHAR(255) NOT NULL,
    idempotency_key CHAR(64) NOT NULL UNIQUE,
    status VARCHAR(32) NOT NULL DEFAULT 'started',
    matched_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_work_item_id BIGINT UNSIGNED NULL,
    result_json LONGTEXT NOT NULL,
    error_message TEXT NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_automation_run_rule (rule_id,created_at),
    KEY idx_automation_run_status (status,created_at),
    CONSTRAINT fk_automation_run_rule FOREIGN KEY(rule_id) REFERENCES enterprise_automation_rules(id),
    CONSTRAINT fk_automation_run_version FOREIGN KEY(version_id) REFERENCES enterprise_automation_rule_versions(id),
    CONSTRAINT fk_automation_run_work FOREIGN KEY(created_work_item_id) REFERENCES enterprise_work_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_escalation_policies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    policy_code VARCHAR(80) NOT NULL UNIQUE,
    policy_name VARCHAR(180) NOT NULL,
    priority VARCHAR(20) NOT NULL,
    warning_minutes INT UNSIGNED NOT NULL,
    level1_minutes INT UNSIGNED NOT NULL,
    level2_minutes INT UNSIGNED NOT NULL,
    level3_minutes INT UNSIGNED NOT NULL,
    backup_role_code VARCHAR(64) NOT NULL,
    executive_role_code VARCHAR(64) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    evidence_note TEXT NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_escalation_policy_priority (priority,status),
    CONSTRAINT fk_escalation_policy_creator FOREIGN KEY(created_by) REFERENCES users(id),
    CONSTRAINT fk_escalation_policy_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id),
    CONSTRAINT fk_escalation_policy_approver FOREIGN KEY(approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enterprise_escalation_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_item_id BIGINT UNSIGNED NOT NULL,
    policy_id BIGINT UNSIGNED NOT NULL,
    from_level TINYINT UNSIGNED NOT NULL,
    to_level TINYINT UNSIGNED NOT NULL,
    triggered_at DATETIME NOT NULL,
    action_json LONGTEXT NOT NULL,
    idempotency_key CHAR(64) NOT NULL UNIQUE,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_escalation_event_work (work_item_id,triggered_at),
    KEY idx_escalation_event_policy (policy_id,triggered_at),
    CONSTRAINT fk_escalation_event_work FOREIGN KEY(work_item_id) REFERENCES enterprise_work_items(id),
    CONSTRAINT fk_escalation_event_policy FOREIGN KEY(policy_id) REFERENCES enterprise_escalation_policies(id),
    CONSTRAINT fk_escalation_event_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions(permission_key,area,action,description) VALUES
('work_management.view','work_management','view','View company-scoped enterprise work, exceptions, SLAs, and evidence'),
('work_management.create','work_management','create','Create supplemental enterprise work items from governed source records'),
('work_management.execute','work_management','execute','Claim, start, block, resume, and complete assigned work'),
('work_management.assign','work_management','assign','Route and reassign work within authorized company scope'),
('work_management.review','work_management','review','Independently review work evidence and exception resolution'),
('work_management.approve','work_management','approve','Provide final separated approval for governed work'),
('work_management.analyze','work_management','analyze','View workload, SLA, escalation, and automation intelligence'),
('work_management.automate','work_management','automate','Create and operate supervised automation rules'),
('work_management.export','work_management','export','Export protected work-management evidence'),
('work_management.administer','work_management','administer','Administer routing, escalation, and automation governance');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p WHERE r.code='system_administrator' AND p.permission_key LIKE 'work_management.%';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p WHERE r.code='executive' AND p.permission_key IN('work_management.view','work_management.review','work_management.approve','work_management.analyze','work_management.export');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p WHERE r.code='company_administrator' AND p.permission_key IN('work_management.view','work_management.create','work_management.execute','work_management.assign','work_management.review','work_management.approve','work_management.analyze','work_management.automate','work_management.export');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p WHERE r.code='procurement_manager' AND p.permission_key IN('work_management.view','work_management.create','work_management.execute','work_management.assign','work_management.review','work_management.approve','work_management.analyze','work_management.automate','work_management.export');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p WHERE r.code='data_contributor' AND p.permission_key IN('work_management.view','work_management.create','work_management.execute');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p WHERE r.code='reviewer' AND p.permission_key IN('work_management.view','work_management.execute','work_management.review','work_management.approve','work_management.analyze','work_management.export');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p WHERE r.code='read_only' AND p.permission_key IN('work_management.view','work_management.analyze');

SET @wm_preparer=(SELECT id FROM users WHERE status='active' ORDER BY id LIMIT 1);
SET @wm_reviewer=(SELECT id FROM users WHERE status='active' AND (@wm_preparer IS NULL OR id<>@wm_preparer) ORDER BY id LIMIT 1);
SET @wm_approver=(SELECT id FROM users WHERE status='active' AND (@wm_preparer IS NULL OR id<>@wm_preparer) AND (@wm_reviewer IS NULL OR id<>@wm_reviewer) ORDER BY id LIMIT 1);

INSERT IGNORE INTO enterprise_work_routing_rules
(rule_code,rule_name,source_module,work_type,entity_id,assigned_role_code,shared_service_entity_code,priority_override,sla_minutes,reviewer_role_code,approver_role_code,status,sort_order,evidence_note,prepared_by,reviewed_by,approved_by,effective_from,locked_at)
VALUES
('ROUTE-PO-OVERDUE','Past-due purchase orders','purchase_orders','commitment_exception',NULL,'procurement_manager','GRUBER-PROCUREMENT','critical',240,'reviewer','executive','active',10,'Route supplier commitment failures to Shared Procurement.',@wm_preparer,@wm_reviewer,@wm_approver,CURRENT_DATE,NOW()),
('ROUTE-PROCESS-STEP','Active process steps','process_mapping','process_step',NULL,'procurement_manager',NULL,NULL,480,'reviewer',NULL,'active',20,'Follow the Section 25 lane entity and step permission.',@wm_preparer,@wm_reviewer,@wm_approver,CURRENT_DATE,NOW()),
('ROUTE-PROCESS-EXCEPTION','Process control exceptions','process_mapping','process_exception',NULL,'reviewer',NULL,'high',120,'reviewer',NULL,'active',30,'Route blocked process exceptions to an independent reviewer.',@wm_preparer,@wm_reviewer,@wm_approver,CURRENT_DATE,NOW()),
('ROUTE-APPROVAL','Approval decisions','approvals','approval',NULL,'reviewer',NULL,'high',480,'reviewer',NULL,'active',40,'Route pending decisions to the assigned independent reviewer.',@wm_preparer,@wm_reviewer,@wm_approver,CURRENT_DATE,NOW()),
('ROUTE-AP-EXCEPTION','Accounts payable exceptions','accounts_payable','financial_exception',NULL,'reviewer','GRUBER-ACCOUNTS-PAYABLE','critical',240,'reviewer','executive','active',50,'Route invoice and payment exceptions to Shared Finance.',@wm_preparer,@wm_reviewer,@wm_approver,CURRENT_DATE,NOW()),
('ROUTE-INVENTORY','Inventory exceptions','inventory','inventory_exception',NULL,'data_contributor',NULL,'high',360,'reviewer',NULL,'active',60,'Keep inventory execution with the operating company.',@wm_preparer,@wm_reviewer,@wm_approver,CURRENT_DATE,NOW()),
('ROUTE-CONTRACT','Contract renewal work','contracts','contract_renewal',NULL,'procurement_manager','GRUBER-PROCUREMENT','medium',1440,'reviewer','executive','active',70,'Route renewal planning to Shared Procurement.',@wm_preparer,@wm_reviewer,@wm_approver,CURRENT_DATE,NOW()),
('ROUTE-SUPPLIER-RESPONSE','Supplier response follow-up','supplier_portal','supplier_response',NULL,'procurement_manager','GRUBER-PROCUREMENT','high',300,'reviewer',NULL,'active',80,'Keep external supplier identities outside the internal queue.',@wm_preparer,@wm_reviewer,@wm_approver,CURRENT_DATE,NOW());

INSERT IGNORE INTO enterprise_escalation_policies
(policy_code,policy_name,priority,warning_minutes,level1_minutes,level2_minutes,level3_minutes,backup_role_code,executive_role_code,status,evidence_note,created_by,reviewed_by,approved_by)
VALUES
('PRIORITY-CRITICAL','Critical work escalation','critical',120,0,240,720,'procurement_manager','executive','active','Critical work escalates immediately and then to executive review.',@wm_preparer,@wm_reviewer,@wm_approver),
('PRIORITY-HIGH','High-priority work escalation','high',240,120,480,1440,'procurement_manager','executive','active','High-priority work follows a staged escalation path.',@wm_preparer,@wm_reviewer,@wm_approver),
('PRIORITY-MEDIUM','Medium-priority work escalation','medium',480,240,1440,2880,'company_administrator','executive','active','Medium work escalates after an operating review window.',@wm_preparer,@wm_reviewer,@wm_approver),
('PRIORITY-LOW','Low-priority work escalation','low',1440,1440,4320,10080,'company_administrator','executive','active','Low-priority work remains visible without premature escalation.',@wm_preparer,@wm_reviewer,@wm_approver);

INSERT IGNORE INTO enterprise_automation_rules(rule_code,rule_name,trigger_event,source_module,entity_id,status,created_by)
VALUES
('AUTO-OVERDUE-COMMITMENT','Create overdue commitment work','purchase_order.overdue','purchase_orders',NULL,'active',@wm_preparer),
('AUTO-PROCESS-EXCEPTION','Create process exception review','process.exception_opened','process_mapping',NULL,'active',@wm_preparer),
('AUTO-INVOICE-MATCH','Route invoice-match exceptions','invoice.match_exception','accounts_payable',NULL,'active',@wm_preparer),
('AUTO-SLA-ESCALATION','Escalate overdue work','work_item.overdue','work_management',NULL,'active',@wm_preparer);

SET @auto_po=(SELECT id FROM enterprise_automation_rules WHERE rule_code='AUTO-OVERDUE-COMMITMENT');
SET @auto_process=(SELECT id FROM enterprise_automation_rules WHERE rule_code='AUTO-PROCESS-EXCEPTION');
SET @auto_invoice=(SELECT id FROM enterprise_automation_rules WHERE rule_code='AUTO-INVOICE-MATCH');
SET @auto_sla=(SELECT id FROM enterprise_automation_rules WHERE rule_code='AUTO-SLA-ESCALATION');

INSERT IGNORE INTO enterprise_automation_rule_versions
(rule_id,version_number,status,condition_json,action_json,idempotency_window_seconds,prepared_by,reviewed_by,approved_by,review_note,approval_note,evidence_note,published_at,locked_at)
VALUES
(@auto_po,'1.0','published','{"field":"status","operator":"equals","value":"past_due"}','{"action":"create_work_item","work_type":"commitment_exception","priority":"critical","assigned_role_code":"procurement_manager","shared_service_entity_code":"GRUBER-PROCUREMENT","sla_minutes":240}',86400,@wm_preparer,@wm_reviewer,@wm_approver,'Rule logic and scope independently reviewed.','Approved for supervised execution.','Published starting automation rule.',NOW(),NOW()),
(@auto_process,'1.0','published','{"field":"status","operator":"in","value":["open","blocked"]}','{"action":"create_work_item","work_type":"process_exception","priority":"high","assigned_role_code":"reviewer","sla_minutes":120}',86400,@wm_preparer,@wm_reviewer,@wm_approver,'Rule logic and scope independently reviewed.','Approved for supervised execution.','Published starting automation rule.',NOW(),NOW()),
(@auto_invoice,'1.0','published','{"field":"match_status","operator":"in","value":["exception","blocked","variance"]}','{"action":"create_work_item","work_type":"financial_exception","priority":"critical","assigned_role_code":"reviewer","shared_service_entity_code":"GRUBER-ACCOUNTS-PAYABLE","sla_minutes":240}',86400,@wm_preparer,@wm_reviewer,@wm_approver,'Rule logic and scope independently reviewed.','Approved for supervised execution.','Published starting automation rule.',NOW(),NOW()),
(@auto_sla,'1.0','published','{"field":"overdue_minutes","operator":"greater_than","value":0}','{"action":"escalate_work_item","policy":"PRIORITY-DEFAULT"}',3600,@wm_preparer,@wm_reviewer,@wm_approver,'Rule logic and scope independently reviewed.','Approved for supervised execution.','Published starting automation rule.',NOW(),NOW());

UPDATE enterprise_automation_rules r SET active_version_id=(SELECT v.id FROM enterprise_automation_rule_versions v WHERE v.rule_id=r.id AND v.version_number='1.0' LIMIT 1) WHERE r.active_version_id IS NULL;
SET @proc_entity=(SELECT id FROM business_entities WHERE entity_code='GRUBER-PROCUREMENT' LIMIT 1);
SET @finance_entity=(SELECT id FROM business_entities WHERE entity_code='GRUBER-ACCOUNTS-PAYABLE' LIMIT 1);

INSERT IGNORE INTO enterprise_work_items
(work_number,source_key,source_module,source_type,source_id,entity_id,company_id,process_instance_id,step_instance_id,work_type,title,description,priority,status,assigned_entity_id,assigned_user_id,assigned_role_code,reviewer_id,approver_id,required_permission,evidence_required,due_at,warning_at,escalation_level,automation_rule_id,created_by,last_activity_at,lock_version)
SELECT CONCAT('WORK-PROC-',si.id),SHA2(CONCAT('process_mapping|process_step|',si.id,'|process_step|',i.entity_id),256),'process_mapping','process_step',si.id,i.entity_id,e.company_id,i.id,si.id,'process_step',CONCAT(s.step_name,' · ',i.instance_number),'Active process step synchronized into the enterprise work queue without changing the canonical process instance.',CASE WHEN si.status='exception' THEN 'critical' ELSE 'high' END,CASE WHEN si.status='exception' THEN 'blocked' ELSE 'open' END,COALESCE(si.assigned_entity_id,i.entity_id),si.assigned_user_id,COALESCE(l.role_code,'data_contributor'),NULL,NULL,s.required_permission,s.evidence_required,COALESCE(si.due_at,i.due_at,DATE_ADD(NOW(),INTERVAL 8 HOUR)),COALESCE(DATE_SUB(si.due_at,INTERVAL 2 HOUR),DATE_ADD(NOW(),INTERVAL 6 HOUR)),0,NULL,COALESCE(i.started_by,@wm_preparer),NOW(),1
FROM business_process_step_instances si JOIN business_process_instances i ON i.id=si.instance_id JOIN business_process_steps s ON s.id=si.step_id JOIN business_process_lanes l ON l.id=s.lane_id JOIN business_entities e ON e.id=i.entity_id
WHERE si.status IN('active','exception') AND e.company_id IS NOT NULL;

INSERT IGNORE INTO enterprise_work_items
(work_number,source_key,source_module,source_type,source_id,entity_id,company_id,work_type,title,description,priority,status,assigned_entity_id,assigned_user_id,assigned_role_code,reviewer_id,approver_id,required_permission,evidence_required,due_at,warning_at,escalation_level,automation_rule_id,created_by,last_activity_at,lock_version)
SELECT CONCAT('WORK-PO-',po.id),SHA2(CONCAT('purchase_orders|purchase_order|',po.id,'|commitment_exception|',b.entity_id),256),'purchase_orders','purchase_order',po.id,b.entity_id,po.company_id,'commitment_exception',CONCAT('Escalate ',po.po_number),'The purchase commitment is past due or partially received and requires supplier recovery evidence.','critical','open',COALESCE(@proc_entity,b.entity_id),po.buyer_user_id,'procurement_manager',@wm_reviewer,@wm_approver,'purchase_orders.edit',1,COALESCE(CONCAT(po.expected_date,' 17:00:00'),DATE_ADD(NOW(),INTERVAL 4 HOUR)),COALESCE(CONCAT(po.expected_date,' 15:00:00'),DATE_ADD(NOW(),INTERVAL 2 HOUR)),0,@auto_po,COALESCE(po.buyer_user_id,@wm_preparer),NOW(),1
FROM purchase_orders po JOIN business_entity_company_bindings b ON b.company_id=po.company_id AND b.status='active'
WHERE po.status IN('past_due','partially_received');

INSERT IGNORE INTO enterprise_work_items
(work_number,source_key,source_module,source_type,source_id,entity_id,company_id,work_type,title,description,priority,status,assigned_entity_id,assigned_user_id,assigned_role_code,reviewer_id,approver_id,required_permission,evidence_required,due_at,warning_at,escalation_level,automation_rule_id,created_by,last_activity_at,lock_version)
SELECT CONCAT('WORK-AP-',x.id),SHA2(CONCAT('accounts_payable|invoice_match_exception|',x.id,'|financial_exception|',b.entity_id),256),'accounts_payable','invoice_match_exception',x.id,b.entity_id,si.company_id,'financial_exception',CONCAT('Clear invoice exception ',x.exception_number),'Invoice and receipt evidence must reconcile before payment proceeds.',x.severity,'blocked',COALESCE(@finance_entity,b.entity_id),x.owner_id,'reviewer',x.reviewer_id,NULL,'accounts_payable.review',1,CONCAT(x.due_date,' 17:00:00'),CONCAT(x.due_date,' 15:00:00'),0,@auto_invoice,COALESCE(x.owner_id,@wm_preparer),NOW(),1
FROM procurement_invoice_exceptions x JOIN supplier_invoices si ON si.id=x.invoice_id JOIN business_entity_company_bindings b ON b.company_id=si.company_id AND b.status='active'
WHERE x.status NOT IN('resolved','closed');

INSERT INTO schema_migrations(version,description,checksum_sha256)
VALUES('5.5-section26','Enterprise work management, exception command center, SLA escalation, supervised automation rules, and operational intelligence',NULL)
ON DUPLICATE KEY UPDATE description=VALUES(description);
