-- Gruber Procurement Intelligence Platform
-- Phase 2: Production Data Integration & Operational Hardening
-- Version 3.0.0
-- Apply this file after gruber_ai_procurement_single_install_v2.sql when upgrading an existing v2 database.

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(500) NOT NULL,
    checksum_sha256 CHAR(64) NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='roles' AND COLUMN_NAME='status');
SET @sql := IF(@column_exists=0,'ALTER TABLE `roles` ADD COLUMN `status` ENUM(''active'',''archived'') NOT NULL DEFAULT ''active'' AFTER description','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='suppliers' AND COLUMN_NAME='owner_user_id');
SET @sql := IF(@column_exists=0,'ALTER TABLE `suppliers` ADD COLUMN `owner_user_id` BIGINT UNSIGNED NULL AFTER created_by','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='suppliers' AND COLUMN_NAME='review_status');
SET @sql := IF(@column_exists=0,'ALTER TABLE `suppliers` ADD COLUMN `review_status` ENUM(''draft'',''submitted'',''changes_requested'',''validated'',''approved'',''archived'') NOT NULL DEFAULT ''draft'' AFTER owner_user_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items' AND COLUMN_NAME='owner_user_id');
SET @sql := IF(@column_exists=0,'ALTER TABLE `items` ADD COLUMN `owner_user_id` BIGINT UNSIGNED NULL AFTER created_by','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items' AND COLUMN_NAME='review_status');
SET @sql := IF(@column_exists=0,'ALTER TABLE `items` ADD COLUMN `review_status` ENUM(''draft'',''submitted'',''changes_requested'',''validated'',''approved'',''archived'') NOT NULL DEFAULT ''draft'' AFTER owner_user_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items' AND COLUMN_NAME='catalog_status');
SET @sql := IF(@column_exists=0,'ALTER TABLE `items` ADD COLUMN `catalog_status` ENUM(''active'',''draft'',''inactive'') NOT NULL DEFAULT ''active'' AFTER active','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='purchase_orders' AND COLUMN_NAME='review_status');
SET @sql := IF(@column_exists=0,'ALTER TABLE `purchase_orders` ADD COLUMN `review_status` ENUM(''draft'',''submitted'',''changes_requested'',''validated'',''approved'',''archived'') NOT NULL DEFAULT ''draft'' AFTER status','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='savings_opportunities' AND COLUMN_NAME='confidence_pct');
SET @sql := IF(@column_exists=0,'ALTER TABLE `savings_opportunities` ADD COLUMN `confidence_pct` TINYINT UNSIGNED NOT NULL DEFAULT 50 AFTER expected_annual_savings','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='savings_opportunities' AND COLUMN_NAME='review_status');
SET @sql := IF(@column_exists=0,'ALTER TABLE `savings_opportunities` ADD COLUMN `review_status` ENUM(''draft'',''submitted'',''changes_requested'',''validated'',''approved'',''archived'') NOT NULL DEFAULT ''draft'' AFTER status','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='savings_opportunities' AND COLUMN_NAME='pipeline_stage');
SET @sql := IF(@column_exists=0,'ALTER TABLE `savings_opportunities` ADD COLUMN `pipeline_stage` ENUM(''draft'',''submitted'',''review'',''validated'',''approved'') NOT NULL DEFAULT ''draft'' AFTER review_status','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='supplier_scorecards' AND COLUMN_NAME='review_status');
SET @sql := IF(@column_exists=0,'ALTER TABLE `supplier_scorecards` ADD COLUMN `review_status` ENUM(''draft'',''submitted'',''changes_requested'',''validated'',''approved'',''archived'') NOT NULL DEFAULT ''draft'' AFTER grade','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='discovery_assignments' AND COLUMN_NAME='priority');
SET @sql := IF(@column_exists=0,'ALTER TABLE `discovery_assignments` ADD COLUMN `priority` ENUM(''low'',''medium'',''high'',''critical'') NOT NULL DEFAULT ''medium'' AFTER completion_pct','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := 'ALTER TABLE `discovery_assignments` MODIFY COLUMN `status` ENUM(''draft'',''not_started'',''assigned'',''in_progress'',''submitted'',''changes_requested'',''validated'',''approved'') NOT NULL DEFAULT ''draft''';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='workflow_approvals' AND COLUMN_NAME='title');
SET @sql := IF(@column_exists=0,'ALTER TABLE `workflow_approvals` ADD COLUMN `title` VARCHAR(240) NULL AFTER entity_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='workflow_approvals' AND COLUMN_NAME='due_at');
SET @sql := IF(@column_exists=0,'ALTER TABLE `workflow_approvals` ADD COLUMN `due_at` TIMESTAMP NULL AFTER requested_at','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='audit_logs' AND COLUMN_NAME='module');
SET @sql := IF(@column_exists=0,'ALTER TABLE `audit_logs` ADD COLUMN `module` VARCHAR(80) NOT NULL DEFAULT ''Platform'' AFTER company_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='imports' AND COLUMN_NAME='file_checksum_sha256');
SET @sql := IF(@column_exists=0,'ALTER TABLE `imports` ADD COLUMN `file_checksum_sha256` CHAR(64) NULL AFTER stored_path','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='imports' AND COLUMN_NAME='mime_type');
SET @sql := IF(@column_exists=0,'ALTER TABLE `imports` ADD COLUMN `mime_type` VARCHAR(160) NULL AFTER file_checksum_sha256','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='import_receipts' AND COLUMN_NAME='records_created');
SET @sql := IF(@column_exists=0,'ALTER TABLE `import_receipts` ADD COLUMN `records_created` INT UNSIGNED NOT NULL DEFAULT 0 AFTER rejected_rows','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='import_receipts' AND COLUMN_NAME='records_updated');
SET @sql := IF(@column_exists=0,'ALTER TABLE `import_receipts` ADD COLUMN `records_updated` INT UNSIGNED NOT NULL DEFAULT 0 AFTER records_created','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS item_companies (
    item_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (item_id,company_id),
    INDEX idx_item_companies_company (company_id,item_id),
    CONSTRAINT fk_item_companies_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_companies_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO item_companies(item_id,company_id,is_primary)
SELECT id,company_id,true FROM items WHERE company_id IS NOT NULL;

INSERT IGNORE INTO item_companies(item_id,company_id,is_primary)
SELECT i.id,c.id,false FROM items i CROSS JOIN companies c WHERE i.company_id IS NULL;

CREATE TABLE IF NOT EXISTS company_admin_profiles (
    company_id BIGINT UNSIGNED PRIMARY KEY,
    status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
    primary_contact VARCHAR(160) NULL,
    contact_email VARCHAR(190) NULL,
    contact_phone VARCHAR(64) NULL,
    data_owner_user_id BIGINT UNSIGNED NULL,
    procurement_owner_user_id BIGINT UNSIGNED NULL,
    accounting_reviewer_user_id BIGINT UNSIGNED NULL,
    completion_pct TINYINT UNSIGNED NOT NULL DEFAULT 0,
    module_settings JSON NULL,
    retention_days INT UNSIGNED NOT NULL DEFAULT 2555,
    company_settings JSON NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_company_admin_profile_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_admin_data_owner FOREIGN KEY (data_owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_company_admin_procurement_owner FOREIGN KEY (procurement_owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_company_admin_accounting_reviewer FOREIGN KEY (accounting_reviewer_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;


SET @column_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='company_admin_profiles' AND COLUMN_NAME='contact_phone');
SET @sql := IF(@column_exists=0,'ALTER TABLE `company_admin_profiles` ADD COLUMN `contact_phone` VARCHAR(64) NULL AFTER contact_email','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := 'ALTER TABLE `company_admin_profiles` MODIFY COLUMN `status` ENUM(''active'',''inactive'',''archived'') NOT NULL DEFAULT ''active''';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS import_staging_rows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id BIGINT UNSIGNED NOT NULL,
    source_row_number INT UNSIGNED NOT NULL,
    row_data JSON NOT NULL,
    normalized_data JSON NULL,
    row_status ENUM('staged','valid','error','committed','skipped') NOT NULL DEFAULT 'staged',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_import_staging_row (import_id,source_row_number),
    INDEX idx_import_staging_status (import_id,row_status),
    CONSTRAINT fk_import_staging_import FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS import_column_mappings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id BIGINT UNSIGNED NOT NULL,
    source_column VARCHAR(190) NOT NULL,
    target_field VARCHAR(190) NULL,
    transform_rule JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_import_column_mapping (import_id,source_column),
    CONSTRAINT fk_import_mapping_import FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Foreign keys for Phase 2 ownership fields. Ignore duplicate-name errors when a hosting panel already added equivalent keys.
SET @fk_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='suppliers' AND CONSTRAINT_NAME='fk_suppliers_owner');
SET @sql := IF(@fk_exists=0,'ALTER TABLE suppliers ADD CONSTRAINT fk_suppliers_owner FOREIGN KEY(owner_user_id) REFERENCES users(id) ON DELETE SET NULL','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @fk_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='items' AND CONSTRAINT_NAME='fk_items_owner');
SET @sql := IF(@fk_exists=0,'ALTER TABLE items ADD CONSTRAINT fk_items_owner FOREIGN KEY(owner_user_id) REFERENCES users(id) ON DELETE SET NULL','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO company_admin_profiles (company_id,status,completion_pct,module_settings,retention_days,company_settings)
SELECT id,'active',0,JSON_OBJECT('discovery',true,'suppliers',true,'items',true,'purchase_orders',true,'inventory',true,'savings',true,'scorecards',true),2555,JSON_OBJECT()
FROM companies;

INSERT INTO system_settings(setting_key,setting_value,description,is_secret)
VALUES
('schema_version',JSON_QUOTE('3.0.0-phase2'),'Current application schema version',false),
('data_environment_policy',JSON_OBJECT('demo_available',true,'production_requires_authentication',true,'cross_environment_copy',false),'Environment separation policy',false),
('import_transaction_policy',JSON_OBJECT('rollback_on_failure',true,'row_limit',25000,'allowed_extensions',JSON_ARRAY('csv','xlsx')),'Production import policy',false)
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),description=VALUES(description);


-- Seed default permissions only for system roles that do not yet have any grants.

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN ('platform.view','platform.administer','users.view','users.create','users.edit','users.delete','users.assign','users.administer','roles.view','roles.create','roles.edit','roles.delete','roles.assign','roles.administer','companies.view','companies.create','companies.edit','companies.delete','companies.assign','companies.administer','discovery.view','discovery.create','discovery.edit','discovery.delete','discovery.assign','discovery.submit','discovery.review','discovery.approve','discovery.export','suppliers.view','suppliers.create','suppliers.edit','suppliers.delete','suppliers.assign','suppliers.submit','suppliers.review','suppliers.approve','suppliers.export','items.view','items.create','items.edit','items.delete','items.assign','items.submit','items.review','items.approve','items.export','purchase_orders.view','purchase_orders.create','purchase_orders.edit','purchase_orders.delete','purchase_orders.assign','purchase_orders.submit','purchase_orders.review','purchase_orders.approve','purchase_orders.export','inventory.view','inventory.create','inventory.edit','inventory.delete','inventory.assign','inventory.submit','inventory.review','inventory.approve','inventory.export','savings.view','savings.create','savings.edit','savings.delete','savings.assign','savings.submit','savings.review','savings.approve','savings.export','scorecards.view','scorecards.create','scorecards.edit','scorecards.delete','scorecards.assign','scorecards.submit','scorecards.review','scorecards.approve','scorecards.export','imports.view','imports.create','imports.edit','imports.delete','imports.submit','imports.review','imports.approve','imports.export','imports.administer','approvals.view','approvals.submit','approvals.review','approvals.approve','approvals.administer','reports.view','reports.export','audit.view','audit.export','audit.administer','agent.view','agent.administer','settings.view','settings.edit','settings.administer','security.view','security.administer')
WHERE r.code='system_administrator' AND NOT EXISTS (SELECT 1 FROM role_permissions existing WHERE existing.role_id=r.id);

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN ('platform.view','companies.view','discovery.view','suppliers.view','items.view','purchase_orders.view','inventory.view','savings.view','scorecards.view','imports.view','approvals.view','reports.view','reports.export','audit.view','agent.view','settings.view')
WHERE r.code='executive' AND NOT EXISTS (SELECT 1 FROM role_permissions existing WHERE existing.role_id=r.id);

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN ('platform.view','users.view','users.create','users.edit','users.assign','roles.view','roles.assign','companies.view','companies.edit','discovery.view','discovery.create','discovery.edit','discovery.assign','discovery.submit','discovery.review','discovery.approve','discovery.export','suppliers.view','suppliers.create','suppliers.edit','suppliers.assign','suppliers.submit','suppliers.review','suppliers.approve','suppliers.export','items.view','items.create','items.edit','items.assign','items.submit','items.review','items.approve','items.export','purchase_orders.view','purchase_orders.create','purchase_orders.edit','purchase_orders.assign','purchase_orders.submit','purchase_orders.review','purchase_orders.approve','purchase_orders.export','inventory.view','inventory.create','inventory.edit','inventory.assign','inventory.submit','inventory.review','inventory.approve','inventory.export','savings.view','savings.create','savings.edit','savings.assign','savings.submit','savings.review','savings.approve','savings.export','scorecards.view','scorecards.create','scorecards.edit','scorecards.submit','scorecards.review','scorecards.approve','scorecards.export','imports.view','imports.create','imports.edit','imports.submit','imports.review','imports.approve','imports.export','approvals.view','approvals.submit','approvals.review','approvals.approve','reports.view','reports.export','audit.view','agent.view','settings.view')
WHERE r.code='company_administrator' AND NOT EXISTS (SELECT 1 FROM role_permissions existing WHERE existing.role_id=r.id);

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN ('platform.view','companies.view','discovery.view','discovery.edit','discovery.assign','discovery.submit','discovery.review','discovery.approve','suppliers.view','suppliers.create','suppliers.edit','suppliers.assign','suppliers.submit','suppliers.review','suppliers.approve','suppliers.export','items.view','items.create','items.edit','items.assign','items.submit','items.review','items.approve','items.export','purchase_orders.view','purchase_orders.create','purchase_orders.edit','purchase_orders.assign','purchase_orders.submit','purchase_orders.review','purchase_orders.approve','purchase_orders.export','inventory.view','inventory.edit','inventory.assign','inventory.review','inventory.approve','inventory.export','savings.view','savings.create','savings.edit','savings.assign','savings.submit','savings.review','savings.approve','savings.export','scorecards.view','scorecards.create','scorecards.edit','scorecards.submit','scorecards.review','scorecards.approve','scorecards.export','imports.view','imports.create','imports.edit','imports.submit','imports.review','imports.approve','approvals.view','approvals.submit','approvals.review','approvals.approve','reports.view','reports.export','audit.view','agent.view')
WHERE r.code='procurement_manager' AND NOT EXISTS (SELECT 1 FROM role_permissions existing WHERE existing.role_id=r.id);

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN ('platform.view','companies.view','discovery.view','discovery.create','discovery.edit','discovery.submit','suppliers.view','suppliers.create','suppliers.edit','suppliers.submit','items.view','items.create','items.edit','items.submit','purchase_orders.view','purchase_orders.create','purchase_orders.edit','purchase_orders.submit','inventory.view','inventory.create','inventory.edit','inventory.submit','savings.view','savings.create','savings.edit','savings.submit','scorecards.view','scorecards.edit','scorecards.submit','imports.view','imports.create','imports.edit','imports.submit','approvals.view','reports.view','agent.view')
WHERE r.code='data_contributor' AND NOT EXISTS (SELECT 1 FROM role_permissions existing WHERE existing.role_id=r.id);

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN ('platform.view','companies.view','discovery.view','discovery.review','discovery.approve','suppliers.view','suppliers.review','suppliers.approve','items.view','items.review','items.approve','purchase_orders.view','purchase_orders.review','purchase_orders.approve','inventory.view','inventory.review','inventory.approve','savings.view','savings.review','savings.approve','scorecards.view','scorecards.review','scorecards.approve','imports.view','imports.review','imports.approve','approvals.view','approvals.review','approvals.approve','reports.view','audit.view','agent.view')
WHERE r.code='reviewer' AND NOT EXISTS (SELECT 1 FROM role_permissions existing WHERE existing.role_id=r.id);

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.permission_key IN ('platform.view','companies.view','discovery.view','suppliers.view','items.view','purchase_orders.view','inventory.view','savings.view','scorecards.view','imports.view','approvals.view','reports.view','agent.view','settings.view')
WHERE r.code='read_only' AND NOT EXISTS (SELECT 1 FROM role_permissions existing WHERE existing.role_id=r.id);

INSERT IGNORE INTO schema_migrations(version,description,checksum_sha256)
VALUES('3.0.0-phase2','Production repository adapter, environment control, workflow persistence, import staging, and operational hardening',NULL);

SET FOREIGN_KEY_CHECKS = 1;
