SET NAMES utf8mb4;
SET time_zone='+00:00';

CREATE TABLE IF NOT EXISTS business_processes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  process_code VARCHAR(80) NOT NULL UNIQUE,
  template_code VARCHAR(80) NOT NULL,
  process_name VARCHAR(190) NOT NULL,
  purpose TEXT NOT NULL,
  category_code VARCHAR(80) NOT NULL,
  value_chain VARCHAR(500) NOT NULL,
  owner_entity_id BIGINT UNSIGNED NULL,
  is_template TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(32) NOT NULL DEFAULT 'draft',
  active_version_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_process_status (status,category_code),
  KEY idx_process_owner (owner_entity_id,status),
  CONSTRAINT fk_process_owner_entity FOREIGN KEY(owner_entity_id) REFERENCES business_entities(id),
  CONSTRAINT fk_process_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_versions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  process_id BIGINT UNSIGNED NOT NULL,
  version_number VARCHAR(40) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'draft',
  effective_from DATE NOT NULL,
  effective_to DATE NULL,
  prepared_by BIGINT UNSIGNED NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  approved_by BIGINT UNSIGNED NULL,
  manifest_hash CHAR(64) NOT NULL,
  evidence_note TEXT NOT NULL,
  published_at DATETIME NULL,
  locked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_process_version(process_id,version_number),
  KEY idx_process_version_status(process_id,status),
  CONSTRAINT fk_process_version_process FOREIGN KEY(process_id) REFERENCES business_processes(id) ON DELETE CASCADE,
  CONSTRAINT fk_process_version_preparer FOREIGN KEY(prepared_by) REFERENCES users(id),
  CONSTRAINT fk_process_version_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id),
  CONSTRAINT fk_process_version_approver FOREIGN KEY(approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_lanes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version_id BIGINT UNSIGNED NOT NULL,
  lane_code VARCHAR(80) NOT NULL,
  lane_name VARCHAR(190) NOT NULL,
  participant_type VARCHAR(40) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  role_code VARCHAR(80) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  color_token VARCHAR(40) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_process_lane(version_id,lane_code),
  KEY idx_process_lane_order(version_id,sort_order),
  CONSTRAINT fk_process_lane_version FOREIGN KEY(version_id) REFERENCES business_process_versions(id) ON DELETE CASCADE,
  CONSTRAINT fk_process_lane_entity FOREIGN KEY(entity_id) REFERENCES business_entities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_steps (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version_id BIGINT UNSIGNED NOT NULL,
  lane_id BIGINT UNSIGNED NOT NULL,
  step_code VARCHAR(80) NOT NULL,
  step_name VARCHAR(190) NOT NULL,
  step_type VARCHAR(40) NOT NULL,
  description TEXT NOT NULL,
  position_x INT NOT NULL DEFAULT 200,
  position_y INT NOT NULL DEFAULT 80,
  node_width INT UNSIGNED NOT NULL DEFAULT 150,
  node_height INT UNSIGNED NOT NULL DEFAULT 72,
  required_permission VARCHAR(120) NOT NULL,
  sla_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  evidence_required TINYINT(1) NOT NULL DEFAULT 0,
  canonical_record_type VARCHAR(120) NOT NULL,
  integration_event VARCHAR(160) NOT NULL,
  automation_mode VARCHAR(40) NOT NULL DEFAULT 'human_supervised',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_process_step(version_id,step_code),
  KEY idx_process_step_lane(lane_id,sort_order),
  KEY idx_process_step_type(version_id,step_type),
  CONSTRAINT fk_process_step_version FOREIGN KEY(version_id) REFERENCES business_process_versions(id) ON DELETE CASCADE,
  CONSTRAINT fk_process_step_lane FOREIGN KEY(lane_id) REFERENCES business_process_lanes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_transitions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version_id BIGINT UNSIGNED NOT NULL,
  from_step_id BIGINT UNSIGNED NOT NULL,
  to_step_id BIGINT UNSIGNED NOT NULL,
  transition_code VARCHAR(80) NOT NULL,
  condition_label VARCHAR(190) NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  is_exception_path TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_process_transition(version_id,transition_code),
  KEY idx_process_transition_from(from_step_id,status),
  CONSTRAINT fk_process_transition_version FOREIGN KEY(version_id) REFERENCES business_process_versions(id) ON DELETE CASCADE,
  CONSTRAINT fk_process_transition_from FOREIGN KEY(from_step_id) REFERENCES business_process_steps(id) ON DELETE CASCADE,
  CONSTRAINT fk_process_transition_to FOREIGN KEY(to_step_id) REFERENCES business_process_steps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_controls (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  step_id BIGINT UNSIGNED NOT NULL,
  control_type VARCHAR(60) NOT NULL,
  rule_code VARCHAR(100) NOT NULL,
  control_description TEXT NOT NULL,
  severity VARCHAR(20) NOT NULL DEFAULT 'medium',
  separation_role VARCHAR(80) NOT NULL,
  evidence_type VARCHAR(80) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_process_control(step_id,rule_code),
  KEY idx_process_control_severity(severity,status),
  CONSTRAINT fk_process_control_step FOREIGN KEY(step_id) REFERENCES business_process_steps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_integrations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  step_id BIGINT UNSIGNED NOT NULL,
  connection_id BIGINT UNSIGNED NULL,
  direction VARCHAR(20) NOT NULL,
  event_type VARCHAR(160) NOT NULL,
  record_type VARCHAR(120) NOT NULL,
  sync_mode VARCHAR(40) NOT NULL,
  required_acknowledgment TINYINT(1) NOT NULL DEFAULT 0,
  timeout_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_process_integration(step_id,event_type,direction),
  KEY idx_process_integration_step(step_id,status),
  KEY idx_process_integration_event(event_type,status),
  CONSTRAINT fk_process_integration_step FOREIGN KEY(step_id) REFERENCES business_process_steps(id) ON DELETE CASCADE,
  CONSTRAINT fk_process_integration_connection FOREIGN KEY(connection_id) REFERENCES integration_connections(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  process_id BIGINT UNSIGNED NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  assignment_type VARCHAR(40) NOT NULL,
  module_code VARCHAR(80) NOT NULL,
  inheritance_mode VARCHAR(40) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  effective_from DATE NOT NULL,
  effective_to DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_process_assignment(process_id,entity_id,module_code),
  KEY idx_process_assignment_entity(entity_id,status),
  CONSTRAINT fk_process_assignment_process FOREIGN KEY(process_id) REFERENCES business_processes(id) ON DELETE CASCADE,
  CONSTRAINT fk_process_assignment_entity FOREIGN KEY(entity_id) REFERENCES business_entities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_instances (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  process_id BIGINT UNSIGNED NOT NULL,
  version_id BIGINT UNSIGNED NOT NULL,
  instance_number VARCHAR(100) NOT NULL UNIQUE,
  entity_id BIGINT UNSIGNED NOT NULL,
  canonical_record_type VARCHAR(120) NOT NULL,
  canonical_record_id BIGINT UNSIGNED NOT NULL,
  instance_title VARCHAR(255) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'in_progress',
  current_step_id BIGINT UNSIGNED NULL,
  started_by BIGINT UNSIGNED NOT NULL,
  started_at DATETIME NOT NULL,
  due_at DATETIME NULL,
  completed_at DATETIME NULL,
  cycle_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
  process_data_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_process_instance_status(process_id,status,due_at),
  KEY idx_process_instance_entity(entity_id,status),
  KEY idx_process_instance_record(canonical_record_type,canonical_record_id),
  CONSTRAINT fk_process_instance_process FOREIGN KEY(process_id) REFERENCES business_processes(id),
  CONSTRAINT fk_process_instance_version FOREIGN KEY(version_id) REFERENCES business_process_versions(id),
  CONSTRAINT fk_process_instance_entity FOREIGN KEY(entity_id) REFERENCES business_entities(id),
  CONSTRAINT fk_process_instance_current_step FOREIGN KEY(current_step_id) REFERENCES business_process_steps(id),
  CONSTRAINT fk_process_instance_starter FOREIGN KEY(started_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_step_instances (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  instance_id BIGINT UNSIGNED NOT NULL,
  step_id BIGINT UNSIGNED NOT NULL,
  assigned_entity_id BIGINT UNSIGNED NULL,
  assigned_user_id BIGINT UNSIGNED NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  started_at DATETIME NULL,
  due_at DATETIME NULL,
  completed_at DATETIME NULL,
  elapsed_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
  evidence_note TEXT NOT NULL,
  output_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_process_step_instance(instance_id,step_id),
  KEY idx_process_step_instance_status(status,due_at),
  CONSTRAINT fk_process_step_instance_instance FOREIGN KEY(instance_id) REFERENCES business_process_instances(id) ON DELETE CASCADE,
  CONSTRAINT fk_process_step_instance_step FOREIGN KEY(step_id) REFERENCES business_process_steps(id),
  CONSTRAINT fk_process_step_instance_entity FOREIGN KEY(assigned_entity_id) REFERENCES business_entities(id),
  CONSTRAINT fk_process_step_instance_user FOREIGN KEY(assigned_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_exceptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  instance_id BIGINT UNSIGNED NOT NULL,
  step_instance_id BIGINT UNSIGNED NOT NULL,
  exception_type VARCHAR(80) NOT NULL,
  severity VARCHAR(20) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'open',
  owner_id BIGINT UNSIGNED NOT NULL,
  reviewer_id BIGINT UNSIGNED NOT NULL,
  exception_description TEXT NOT NULL,
  resolution_note TEXT NOT NULL,
  opened_at DATETIME NOT NULL,
  resolved_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_process_exception_status(status,severity,opened_at),
  CONSTRAINT fk_process_exception_instance FOREIGN KEY(instance_id) REFERENCES business_process_instances(id) ON DELETE CASCADE,
  CONSTRAINT fk_process_exception_step FOREIGN KEY(step_instance_id) REFERENCES business_process_step_instances(id),
  CONSTRAINT fk_process_exception_owner FOREIGN KEY(owner_id) REFERENCES users(id),
  CONSTRAINT fk_process_exception_reviewer FOREIGN KEY(reviewer_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_process_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  process_id BIGINT UNSIGNED NULL,
  version_id BIGINT UNSIGNED NULL,
  instance_id BIGINT UNSIGNED NULL,
  step_id BIGINT UNSIGNED NULL,
  event_type VARCHAR(100) NOT NULL,
  from_status VARCHAR(32) NULL,
  to_status VARCHAR(32) NULL,
  severity VARCHAR(20) NOT NULL DEFAULT 'medium',
  evidence_note TEXT NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_process_event_process(process_id,created_at),
  KEY idx_process_event_instance(instance_id,created_at),
  KEY idx_process_event_type(event_type,created_at),
  CONSTRAINT fk_process_event_process FOREIGN KEY(process_id) REFERENCES business_processes(id),
  CONSTRAINT fk_process_event_version FOREIGN KEY(version_id) REFERENCES business_process_versions(id),
  CONSTRAINT fk_process_event_instance FOREIGN KEY(instance_id) REFERENCES business_process_instances(id),
  CONSTRAINT fk_process_event_step FOREIGN KEY(step_id) REFERENCES business_process_steps(id),
  CONSTRAINT fk_process_event_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions(permission_key,area,action,description) VALUES
('process_mapping.view','process_mapping','view','View visual process maps, instances, controls, and analytics'),
('process_mapping.edit','process_mapping','edit','Create draft process maps, steps, transitions, and layouts'),
('process_mapping.publish','process_mapping','publish','Publish independently reviewed and approved process versions'),
('process_mapping.execute','process_mapping','execute','Start and advance governed process instances'),
('process_mapping.analyze','process_mapping','analyze','View cycle time, bottleneck, exception, and compliance intelligence'),
('process_mapping.export','process_mapping','export','Export the governed process catalog'),
('process_mapping.administer','process_mapping','administer','Administer process templates, assignments, controls, and integration mappings');

SET @system_admin=(SELECT id FROM users WHERE status='active' ORDER BY id LIMIT 1);
SET @reviewer=(SELECT id FROM users WHERE status='active' AND (@system_admin IS NULL OR id<>@system_admin) ORDER BY id LIMIT 1);
SET @approver=(SELECT id FROM users WHERE status='active' AND (@system_admin IS NULL OR id<>@system_admin) AND (@reviewer IS NULL OR id<>@reviewer) ORDER BY id LIMIT 1);
SET @root_entity=(SELECT id FROM business_entities WHERE entity_code='GRUBER-ENTERPRISE' ORDER BY id LIMIT 1);
SET @request_entity=(SELECT id FROM business_entities WHERE entity_code='COMP-GPS' ORDER BY id LIMIT 1);
SET @proc_entity=(SELECT id FROM business_entities WHERE entity_code='GRUBER-PROCUREMENT' ORDER BY id LIMIT 1);
SET @finance_entity=(SELECT id FROM business_entities WHERE entity_code='GRUBER-ACCOUNTS-PAYABLE' ORDER BY id LIMIT 1);

INSERT IGNORE INTO business_processes(process_code,template_code,process_name,purpose,category_code,value_chain,owner_entity_id,is_template,status,created_by)
VALUES('PROC-P2P','procure_to_pay','Procure-to-Pay','Coordinate demand, shared procurement, supplier collaboration, receiving, invoice matching, payment, and reconciliation.','source_to_pay','Demand → Source → Purchase → Receive → Pay → Reconcile',@root_entity,1,'published',@system_admin);

SET @p2p=(SELECT id FROM business_processes WHERE process_code='PROC-P2P' LIMIT 1);
INSERT IGNORE INTO business_process_versions(process_id,version_number,status,effective_from,prepared_by,reviewed_by,approved_by,manifest_hash,evidence_note,published_at,locked_at)
VALUES(@p2p,'1.0','published',CURRENT_DATE,@system_admin,@reviewer,@approver,SHA2('procure_to_pay|1.0',256),'Initial governed visual procure-to-pay reference process.',NOW(),NOW());
SET @p2pv=(SELECT id FROM business_process_versions WHERE process_id=@p2p AND version_number='1.0' LIMIT 1);
UPDATE business_processes SET active_version_id=@p2pv WHERE id=@p2p AND active_version_id IS NULL;

INSERT IGNORE INTO business_process_lanes(version_id,lane_code,lane_name,participant_type,entity_id,role_code,sort_order,color_token,status) VALUES
(@p2pv,'requesting_business','Requesting Business','entity',@request_entity,'requester',1,'lane-1','active'),
(@p2pv,'shared_procurement','Shared Procurement','entity',@proc_entity,'procurement_manager',2,'lane-2','active'),
(@p2pv,'supplier','Supplier','external',NULL,'supplier',3,'lane-3','active'),
(@p2pv,'receiving_business','Receiving Business','entity',@request_entity,'receiver',4,'lane-4','active'),
(@p2pv,'shared_finance','Shared Finance / AP','entity',@finance_entity,'reviewer',5,'lane-5','active');

SET @lane_req=(SELECT id FROM business_process_lanes WHERE version_id=@p2pv AND lane_code='requesting_business');
SET @lane_proc=(SELECT id FROM business_process_lanes WHERE version_id=@p2pv AND lane_code='shared_procurement');
SET @lane_sup=(SELECT id FROM business_process_lanes WHERE version_id=@p2pv AND lane_code='supplier');
SET @lane_rec=(SELECT id FROM business_process_lanes WHERE version_id=@p2pv AND lane_code='receiving_business');
SET @lane_fin=(SELECT id FROM business_process_lanes WHERE version_id=@p2pv AND lane_code='shared_finance');

INSERT IGNORE INTO business_process_steps(version_id,lane_id,step_code,step_name,step_type,description,position_x,position_y,node_width,node_height,required_permission,sla_minutes,evidence_required,canonical_record_type,integration_event,automation_mode,sort_order,status) VALUES
(@p2pv,@lane_req,'start','Purchase need identified','start','Start of governed procure-to-pay process.',220,70,150,72,'platform.view',0,0,'purchase_requisition','','human_supervised',1,'active'),
(@p2pv,@lane_req,'create_request','Create purchase request','task','Create the canonical demand record.',380,70,150,72,'purchase_orders.create',480,1,'purchase_requisition','','human_supervised',2,'active'),
(@p2pv,@lane_req,'budget_review','Budget validation','approval','Validate budget before purchasing.',570,70,150,72,'approvals.review',480,1,'purchase_requisition','','human_supervised',3,'active'),
(@p2pv,@lane_req,'approved_gateway','Approved?','gateway','Route approved or returned demand.',760,70,150,72,'approvals.approve',120,1,'purchase_requisition','','human_supervised',4,'active'),
(@p2pv,@lane_proc,'source_supplier','Validate supplier and contract','task','Validate supplier, contract, and sourcing evidence.',920,230,150,72,'suppliers.review',960,1,'supplier','','human_supervised',5,'active'),
(@p2pv,@lane_proc,'issue_po','Issue purchase order','approval','Approve and issue the canonical purchase order.',1110,230,150,72,'purchase_orders.approve',480,1,'purchase_order','purchase_order.issued','human_supervised',6,'active'),
(@p2pv,@lane_sup,'supplier_ack','Acknowledge purchase order','external','Supplier acknowledges the issued order.',1110,390,150,72,'supplier_portal.view',1440,1,'supplier_purchase_order_response','purchase_order.acknowledged','human_supervised',7,'active'),
(@p2pv,@lane_sup,'submit_asn','Submit advance shipment notice','integration','Supplier submits shipment evidence.',920,390,150,72,'supplier_portal.view',2880,1,'supplier_shipment_notice','shipment_notice.submitted','adapter_gated',8,'active'),
(@p2pv,@lane_rec,'receive_goods','Receive and inspect goods','task','Receive and inspect against the purchase order.',730,550,150,72,'inventory.edit',480,1,'purchase_order_receipt','receipt.completed','human_supervised',9,'active'),
(@p2pv,@lane_fin,'invoice_match','Three-way invoice match','control','Validate purchase order, receipt, and invoice.',920,710,150,72,'accounts_payable.review',480,1,'supplier_invoice','invoice.matched','human_supervised',10,'active'),
(@p2pv,@lane_fin,'payment_batch','Schedule and approve payment','approval','Create and independently approve payment.',1110,710,150,72,'accounts_payable.approve',1440,1,'ap_payment_batch','payment_batch.approved','human_supervised',11,'active'),
(@p2pv,@lane_fin,'settle_reconcile','Settle and reconcile','integration','Record settlement and independent reconciliation.',1300,710,150,72,'accounts_payable.reconcile',1440,1,'ap_reconciliation','payment.reconciled','adapter_gated',12,'active'),
(@p2pv,@lane_req,'return_request','Return for correction','exception','Return demand for correction with evidence.',760,120,150,72,'purchase_orders.edit',240,1,'purchase_requisition','','human_supervised',13,'active'),
(@p2pv,@lane_fin,'end','Process completed','end','Governed procure-to-pay instance completed.',1490,710,150,72,'platform.view',0,0,'ap_reconciliation','','human_supervised',14,'active');

INSERT IGNORE INTO business_process_transitions(version_id,from_step_id,to_step_id,transition_code,condition_label,is_default,is_exception_path,sort_order,status)
SELECT @p2pv,f.id,t.id,x.transition_code,x.condition_label,x.is_default,x.is_exception_path,x.sort_order,'active'
FROM (
  SELECT 'start' from_code,'create_request' to_code,'T1' transition_code,'Begin' condition_label,1 is_default,0 is_exception_path,1 sort_order UNION ALL
  SELECT 'create_request','budget_review','T2','Submitted',1,0,2 UNION ALL
  SELECT 'budget_review','approved_gateway','T3','Reviewed',1,0,3 UNION ALL
  SELECT 'approved_gateway','source_supplier','T4','Approved',1,0,4 UNION ALL
  SELECT 'approved_gateway','return_request','T5','Changes required',0,1,5 UNION ALL
  SELECT 'return_request','create_request','T6','Resubmit',1,0,6 UNION ALL
  SELECT 'source_supplier','issue_po','T7','Supplier validated',1,0,7 UNION ALL
  SELECT 'issue_po','supplier_ack','T8','PO sent',1,0,8 UNION ALL
  SELECT 'supplier_ack','submit_asn','T9','Acknowledged',1,0,9 UNION ALL
  SELECT 'submit_asn','receive_goods','T10','Shipment received',1,0,10 UNION ALL
  SELECT 'receive_goods','invoice_match','T11','Receipt posted',1,0,11 UNION ALL
  SELECT 'invoice_match','payment_batch','T12','Match cleared',1,0,12 UNION ALL
  SELECT 'payment_batch','settle_reconcile','T13','Released',1,0,13 UNION ALL
  SELECT 'settle_reconcile','end','T14','Reconciled',1,0,14
) x
JOIN business_process_steps f ON f.version_id=@p2pv AND f.step_code=x.from_code
JOIN business_process_steps t ON t.version_id=@p2pv AND t.step_code=x.to_code;

INSERT IGNORE INTO business_process_controls(step_id,control_type,rule_code,control_description,severity,separation_role,evidence_type,status)
SELECT id,IF(step_type='approval','approval','evidence'),CONCAT('CTRL-',step_code),'Require governed evidence and authorization before the process advances.',IF(step_type='approval','high','medium'),IF(step_type='approval','preparer',''),'record_reference','active'
FROM business_process_steps
WHERE version_id=@p2pv AND (evidence_required=1 OR step_type IN('approval','control'));

INSERT IGNORE INTO business_process_integrations(step_id,connection_id,direction,event_type,record_type,sync_mode,required_acknowledgment,timeout_minutes,status)
SELECT id,NULL,'outbound',integration_event,canonical_record_type,'event',1,120,'active'
FROM business_process_steps
WHERE version_id=@p2pv AND integration_event<>'';

INSERT IGNORE INTO business_process_assignments(process_id,entity_id,assignment_type,module_code,inheritance_mode,status,effective_from)
SELECT @p2p,b.entity_id,'applicable','source_to_pay','template','active',CURRENT_DATE
FROM business_entity_company_bindings b;

INSERT INTO business_process_events(process_id,version_id,event_type,from_status,to_status,severity,evidence_note,created_by)
SELECT @p2p,@p2pv,'process_template_seeded',NULL,'published','medium','Section 25 seeded the complete visual procure-to-pay reference process without changing canonical transaction relationships.',@system_admin
WHERE NOT EXISTS (
  SELECT 1 FROM business_process_events
  WHERE process_id=@p2p AND version_id=@p2pv AND event_type='process_template_seeded'
);

INSERT IGNORE INTO schema_migrations(version,description)
VALUES('5.4-section25','Section 25 enterprise process mapping, visual orchestration, live instances, controls, integrations, exceptions, and process intelligence');
